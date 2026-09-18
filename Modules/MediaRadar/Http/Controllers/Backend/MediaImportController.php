<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Jobs\AnalyzeCandidateJob;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\DeduplicationService;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Sources\ProviderManager;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Add a specific YouTube or Vimeo video to the queue by URL.
 *
 * Discovery is search-driven; this is the manual counterpart. Paste a link,
 * the platform pulls the video's metadata through the same provider clients
 * discovery uses, you pick how to categorise it, and it lands in the approval
 * queue exactly like a discovered candidate — including the AI analysis pass.
 */
class MediaImportController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(
        private CandidateService $candidates,
        private ProviderManager $providers,
        private DeduplicationService $deduplication,
    ) {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.import',
            'media-radar-candidates',
            'ph ph-link'
        );
    }

    /** The import form. */
    public function create(): View
    {
        abort_if(! auth()->user()->can('add_media_radar'), 403);

        $hasTier = \Illuminate\Support\Facades\Schema::hasColumn('genres', 'is_primary');

        return view('mediaradar::backend.candidates.import', [
            'genres' => Genres::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'primaryGenres' => Genres::where('status', 1)
                ->when($hasTier, fn ($q) => $q->where('is_primary', 1))
                ->orderBy('name')->pluck('name', 'id'),
            'subGenres' => Genres::where('status', 1)
                ->when($hasTier, fn ($q) => $q->where('is_primary', 0)->where('is_music_genre', 0))
                ->orderBy('name')->pluck('name', 'id'),
            'musicGenres' => \Illuminate\Support\Facades\Schema::hasColumn('genres', 'is_music_genre')
                ? Genres::where('status', 1)->where('is_music_genre', 1)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    /**
     * Fetch metadata for a URL without saving, so the admin can confirm they
     * pasted the right video before importing.
     */
    public function preview(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->can('add_media_radar'), 403);

        $request->validate(['url' => 'required|string|max:2048']);

        try {
            $normalized = $this->fetch($request->input('url'));
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $this->friendlyError($e),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'provider' => $normalized->provider,
                'title' => $normalized->originalTitle,
                'description' => \Illuminate\Support\Str::limit((string) $normalized->originalDescription, 400),
                'creator' => $normalized->creatorName,
                'published_at' => $normalized->publishedAt,
                'duration' => $normalized->durationSeconds,
                'thumbnail' => $normalized->thumbnailUrl,
                'embeddable' => $normalized->embeddable,
            ],
        ]);
    }

    /** Import the video into the approval queue. */
    public function store(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()->can('add_media_radar'), 403);

        $data = $request->validate([
            'url' => 'required|string|max:2048',
            'genre_id' => 'nullable|integer|exists:genres,id',
            'secondary_genre_ids' => 'nullable|array',
            'secondary_genre_ids.*' => 'integer|exists:genres,id',
            'target_section' => 'required|string|in:short_film,tvshow,podcast,music',
            'music_genre_id' => 'nullable|integer|exists:genres,id',
            'editorial_title' => 'nullable|string|max:255',
        ]);

        // Music uses its music sub-genre as the genre; everything else needs a type genre.
        if ($data['target_section'] === 'music') {
            if (empty($data['music_genre_id'])) {
                return back()->withInput()->withErrors(['music_genre_id' => 'Choose a music sub-category.']);
            }
            $data['genre_id'] = $data['music_genre_id'];
        } elseif (empty($data['genre_id'])) {
            return back()->withInput()->withErrors(['genre_id' => 'Choose a genre.']);
        }

        try {
            $normalized = $this->fetch($data['url']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $this->friendlyError($e));
        }

        if (! $normalized->embeddable) {
            return back()->withInput()->with('error', __('mediaradar::mediaradar.import_not_embeddable'));
        }

        $settings = MediaRadarSetting::getInstance();

        // Status-aware duplicate handling:
        //  - already approved/published (in the library) -> it's a real duplicate, do not re-add
        //  - already pending in the review queue          -> auto-reject this duplicate submission
        //  - previously rejected/archived (terminal)      -> allow re-adding (falls through)
        $existing = $this->deduplication->existing($normalized, (int) $settings->publication_id);
        if ($existing !== null) {
            if (in_array($existing->status, [CandidateStatus::APPROVED, CandidateStatus::SCHEDULED, CandidateStatus::PUBLISHED], true)) {
                return redirect()
                    ->route('backend.media-radar-candidates.show', $existing->id)
                    ->with('error', 'This video is already in your library, so it was not added again.');
            }
            if (! CandidateStatus::isTerminal($existing->status)) {
                return redirect()
                    ->route('backend.media-radar-candidates.show', $existing->id)
                    ->with('error', 'This video is already in the review queue — the duplicate submission was rejected. Opening the existing item.');
            }
        }

        $result = $this->candidates->store($normalized, $settings);
        $candidate = $result['candidate'];

        // Apply the admin's categorisation on top of the stored candidate.
        $fill = [
            'genre_id' => $data['genre_id'],
            'secondary_genre_ids' => $data['secondary_genre_ids'] ?? null,
            'editorial_title' => $data['editorial_title'] ?: $candidate->editorial_title,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('media_candidates', 'target_section')) {
            $fill['target_section'] = $data['target_section'];
        }
        $candidate->forceFill($fill)->save();

        // Same as discovery: persisted first, analysed second so an AI outage
        // can never lose the import.
        if ($result['created']) {
            // Analyse inline (no queue worker on shared hosting) so the candidate
            // reaches READY_FOR_REVIEW immediately and the Accept buttons appear.
            try {
                AnalyzeCandidateJob::dispatchSync($candidate->id, null);
            } catch (\Throwable $e) {
                // Analysis failure lands the candidate in ANALYSIS_ERROR, which is
                // still reviewable — the admin can accept it manually.
            }
            $message = __('mediaradar::mediaradar.import_added');
        } else {
            $message = __('mediaradar::mediaradar.import_existing');
        }

        return redirect()
            ->route('backend.media-radar-candidates.show', $candidate->id)
            ->with('success', $message);
    }

    /**
     * Resolve a pasted URL to a normalized candidate via the provider client.
     *
     * @throws \RuntimeException|ProviderException
     */
    private function fetch(string $url): NormalizedCandidate
    {
        [$provider, $videoId] = $this->parseUrl($url);

        if (! $provider || ! $videoId) {
            throw new \RuntimeException(__('mediaradar::mediaradar.import_unrecognised_url'));
        }

        if (! $this->providers->supports($provider)) {
            throw new \RuntimeException(__('mediaradar::mediaradar.import_provider_unavailable'));
        }

        $client = $this->providers->make($provider);

        if (! $client->isConfigured()) {
            throw new \RuntimeException(__('mediaradar::mediaradar.import_provider_unconfigured', ['provider' => ucfirst($provider)]));
        }

        $normalized = $client->getVideo($videoId);

        if (! $normalized) {
            throw new \RuntimeException(__('mediaradar::mediaradar.import_not_found'));
        }

        return $normalized;
    }

    /**
     * Extract [provider, videoId] from a YouTube or Vimeo URL.
     *
     * Handles the common shapes: youtu.be/ID, watch?v=ID, /embed/ID,
     * /shorts/ID, /live/ID, and vimeo.com/ID (with optional hash).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function parseUrl(string $url): array
    {
        $url = trim($url);

        // YouTube
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return ['youtube', $m[1]];
        }

        // Vimeo (numeric id, optional /hash for unlisted)
        if (preg_match('~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d{6,})~i', $url, $m)) {
            return ['vimeo', $m[1]];
        }

        // A bare id typed straight in.
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
            return ['youtube', $url];
        }
        if (preg_match('~^\d{6,}$~', $url)) {
            return ['vimeo', $url];
        }

        return [null, null];
    }

    private function friendlyError(\Throwable $e): string
    {
        if ($e instanceof ProviderException) {
            Log::warning('[MediaRadar] Import provider error: '.$e->getMessage());

            return __('mediaradar::mediaradar.import_provider_error');
        }

        return $e->getMessage() ?: __('mediaradar::mediaradar.import_failed');
    }
}
