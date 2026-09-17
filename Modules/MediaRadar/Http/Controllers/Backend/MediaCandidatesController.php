<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Http\Requests\MediaCandidateRequest;
use Modules\MediaRadar\Jobs\AnalyzeCandidateJob;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\CoverArtService;
use Modules\MediaRadar\Services\EditorialWorkflowService;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\RejectionReason;
use Yajra\DataTables\DataTables;

/**
 * The Media Radar approval queue.
 */
class MediaCandidatesController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(
        private CandidateService $candidates,
        private EditorialWorkflowService $workflow,
    ) {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.candidates',
            'media-radar-candidates',
            'ph ph-film-strip'
        );
    }

    public function index(Request $request): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $module_action = 'List';

        return view('mediaradar::backend.candidates.index', [
            'module_action' => $module_action,
            'filter' => $request->only(['status', 'provider', 'genre_id', 'rule_id', 'band']),
            'genres' => Genres::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'rules' => MediaDiscoveryRule::orderBy('name')->pluck('name', 'id'),
            'statuses' => CandidateStatus::all(),
        ]);
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $query = MediaCandidate::query()->with('genre');

        $this->applyFilters($query, $request);

        return $datatable->eloquent($query)
            ->addColumn('check', fn ($data) => '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$data->id.'" name="datatable_ids[]" value="'.$data->id.'" onclick="dataTableRowCheck('.$data->id.', this)">')
            ->editColumn('poster_url', function ($data) {
                $thumbnail = CoverArtService::displayUrl($data->thumbnail_url ?: $data->poster_url);

                return view('components.media-item', [
                    'thumbnail' => $thumbnail,
                    'name' => $data->displayTitle(),
                    'type' => 'video',
                ])->render();
            })
            // editColumn on the real columns keeps DataTables search and
            // ordering working against the database.
            ->editColumn('original_title', fn ($data) => e($data->displayTitle()))
            ->editColumn('creator_name', fn ($data) => e($data->creator_name ?: '-'))
            ->editColumn('provider', fn ($data) => '<span class="badge bg-secondary-subtle text-uppercase">'.e($data->provider).'</span>')
            ->addColumn('genre', fn ($data) => e(optional($data->genre)->name ?: '-'))
            ->addColumn('duration', fn ($data) => $data->durationLabel())
            ->editColumn('editorial_score', fn ($data) => $this->scoreBadge($data))
            ->editColumn('status', fn ($data) => '<span class="badge bg-primary-subtle">'.e(CandidateStatus::label((string) $data->status)).'</span>')
            ->editColumn('discovered_at', fn ($data) => optional($data->discovered_at)->diffForHumans() ?: '-')
            ->addColumn('action', fn ($data) => view('mediaradar::backend.candidates.action', compact('data'))->render())
            ->rawColumns(['check', 'poster_url', 'provider', 'editorial_score', 'status', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('filter.column_status')) {
            $query->where('status', $request->input('filter.column_status'));
        } elseif (! $request->filled('filter.show_all')) {
            $query->whereNotIn('status', [CandidateStatus::ARCHIVED, CandidateStatus::INVALID]);
        }

        if ($request->filled('filter.provider')) {
            $query->where('provider', $request->input('filter.provider'));
        }

        if ($request->filled('filter.genre_id')) {
            $query->where('genre_id', (int) $request->input('filter.genre_id'));
        }

        if ($request->filled('filter.rule_id')) {
            $ruleId = (int) $request->input('filter.rule_id');
            $query->whereHas('ruleMatches', fn ($q) => $q->where('rule_id', $ruleId));
        }

        if ($request->filled('filter.band')) {
            match ($request->input('filter.band')) {
                'priority' => $query->where('editorial_score', '>=', 90),
                'recommended' => $query->whereBetween('editorial_score', [75, 89]),
                'secondary' => $query->whereBetween('editorial_score', [60, 74]),
                'low' => $query->where('editorial_score', '<', 60),
                default => null,
            };
        }

        if ($request->filled('filter.search')) {
            $term = '%'.$request->input('filter.search').'%';

            // Local search only: browsing the queue never spends provider quota.
            $query->where(function ($q) use ($term) {
                $q->where('original_title', 'like', $term)
                    ->orWhere('editorial_title', 'like', $term)
                    ->orWhere('creator_name', 'like', $term)
                    ->orWhere('original_description', 'like', $term);
            });
        }
    }

    private function scoreBadge(MediaCandidate $candidate): string
    {
        if ($candidate->editorial_score === null) {
            return '<span class="badge bg-secondary-subtle">-</span>';
        }

        $class = match ($candidate->scoreBand()) {
            'priority' => 'bg-success-subtle',
            'recommended' => 'bg-primary-subtle',
            'secondary' => 'bg-warning-subtle',
            default => 'bg-danger-subtle',
        };

        return '<span class="badge '.$class.'" title="'.e($candidate->scoreBandLabel()).'">'.(int) $candidate->editorial_score.'</span>';
    }

    public function show(MediaCandidate $candidate): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $candidate->load(['genre', 'analysis', 'decisions.editor', 'ruleMatches.rule', 'publishedMovie']);

        return view('mediaradar::backend.candidates.show', [
            'module_action' => 'Detail',
            'candidate' => $candidate,
            'genres' => Genres::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'rejectionReasons' => RejectionReason::OPTIONS,
            'siblings' => MediaCandidate::where('provider', $candidate->provider)
                ->where('provider_creator_id', $candidate->provider_creator_id)
                ->where('id', '!=', $candidate->id)
                ->latest('discovered_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function update(MediaCandidateRequest $request, MediaCandidate $candidate)
    {
        abort_if(! auth()->user()->can('edit_media_radar'), 403);

        $previous = (string) $candidate->status;
        $candidate->fill($request->validated())->save();

        $this->candidates->recordDecision(
            $candidate,
            'edited',
            $previous,
            (string) $candidate->status,
            auth()->id()
        );

        return redirect()
            ->route('backend.media-radar-candidates.show', $candidate->id)
            ->with('success', __('mediaradar::mediaradar.candidate_updated'));
    }

    public function approve(Request $request, MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('approve_media_radar'), 403);

        $publishNow = $request->boolean('publish_now');
        $done = $this->workflow->approve($candidate, auth()->id(), $publishNow, 'approved', $request->input('notes'));

        return $this->respond($done, __('mediaradar::mediaradar.candidate_approved'));
    }

    public function reject(Request $request, MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('approve_media_radar'), 403);

        $reason = $request->input('rejection_reason');

        $done = $this->workflow->reject(
            $candidate,
            auth()->id(),
            RejectionReason::isValid($reason) ? $reason : 'other',
            $request->input('notes'),
            $request->boolean('block_creator')
        );

        return $this->respond($done, __('mediaradar::mediaradar.candidate_rejected'));
    }

    public function schedule(Request $request, MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('approve_media_radar'), 403);

        $request->validate(['scheduled_for' => ['required', 'date', 'after:now']]);

        if ($candidate->status === CandidateStatus::READY_FOR_REVIEW) {
            $this->workflow->approve($candidate, auth()->id());
            $candidate->refresh();
        }

        $done = $this->workflow->schedule($candidate, Carbon::parse($request->input('scheduled_for')), auth()->id());

        return $this->respond($done, __('mediaradar::mediaradar.candidate_scheduled'));
    }

    public function publish(MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('approve_media_radar'), 403);

        $done = $this->workflow->publishNow($candidate, auth()->id());

        return $this->respond($done, __('mediaradar::mediaradar.candidate_published'));
    }

    public function archive(MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('edit_media_radar'), 403);

        return $this->respond(
            $this->workflow->archive($candidate, auth()->id()),
            __('mediaradar::mediaradar.candidate_archived')
        );
    }

    public function analyze(MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('edit_media_radar'), 403);

        // Run inline (no queue worker on shared hosting) so the candidate reaches
        // READY_FOR_REVIEW right away and can be accepted.
        try {
            AnalyzeCandidateJob::dispatchSync($candidate->id, optional($candidate->rules()->first())->id);
        } catch (\Throwable $e) {
            // ANALYSIS_ERROR is still reviewable — the admin can accept manually.
        }

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.analysis_queued'),
        ]);
    }

    public function trust(MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $source = $this->workflow->trustCreator($candidate, auth()->id());

        return $this->respond($source !== null, __('mediaradar::mediaradar.creator_trusted'));
    }

    public function block(Request $request, MediaCandidate $candidate): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $blocked = $this->workflow->blockCreator($candidate, auth()->id(), $request->input('reason'));

        return $this->respond($blocked !== null, __('mediaradar::mediaradar.creator_blocked'));
    }

    public function bulk_action(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->can('approve_media_radar'), 403);

        // The shared quick-action form posts the selected rows as ?rowIds=1,2,3
        $ids = array_filter(explode(',', (string) $request->input('rowIds')));
        $action = (string) $request->input('action_type');
        $affected = 0;

        foreach (MediaCandidate::whereIn('id', $ids)->get() as $candidate) {
            $done = match ($action) {
                'approve' => $this->workflow->approve($candidate, auth()->id()),
                'reject' => $this->workflow->reject($candidate, auth()->id(), 'other'),
                'archive' => $this->workflow->archive($candidate, auth()->id()),
                default => false,
            };

            $affected += $done ? 1 : 0;
        }

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.bulk_applied', ['count' => $affected]),
        ]);
    }

    private function respond(bool $done, string $message): JsonResponse
    {
        return response()->json([
            'status' => $done,
            'message' => $done ? $message : __('mediaradar::mediaradar.invalid_transition'),
        ], $done ? 200 : 422);
    }
}
