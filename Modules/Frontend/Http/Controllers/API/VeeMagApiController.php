<?php

namespace Modules\Frontend\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VeeMagIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Mobile API for the VeeMag platform (published issues -> sections).
 * Mirrors the web VeeMag viewer so the Flutter app shows the same 21 issues.
 */
class VeeMagApiController extends Controller
{
    /** List of published VeeMag issues (gallery covers). */
    public function index(): JsonResponse
    {
        $issues = VeeMagIssue::published()->with('publication')
            ->orderByDesc('release_date')->orderByDesc('id')
            ->withCount(['sections'])
            ->get()
            ->map(fn ($i) => $this->listItem($i))
            ->values();

        return response()->json([
            'status'  => true,
            'message' => 'VeeMag issues retrieved successfully.',
            'data'    => $issues,
        ]);
    }

    /** One issue with its ordered, playable sections. */
    public function show(string $slug): JsonResponse
    {
        $issue = VeeMagIssue::published()->with(['publication', 'sections'])
            ->where('slug', $slug)->first();

        if (! $issue) {
            return response()->json(['status' => false, 'message' => 'VeeMag not found.'], 404);
        }

        $data = $this->listItem($issue);
        $data['description'] = $this->plainText($issue->description);
        $data['sections'] = $issue->sections->map(function ($s) {
            $provider = strtolower((string) $s->video_upload_type);
            return [
                'id'          => $s->id,
                'title'       => $s->title,
                'subtitle'    => $s->subtitle,
                'type'        => $s->type,
                'type_label'  => $s->type_label,
                'order_index' => $s->order_index,
                'runtime'     => (int) $s->runtime_seconds,
                'provider'    => $provider,             // youtube | vimeo | local | embedded
                'video_id'    => $this->videoId($s->video_upload_type, $s->video_url_input),
                'src'         => $s->video_upload_type === 'Local'
                    ? setBaseUrlWithFileName($s->video_url_input, 'video', 'veemag')
                    : (string) $s->video_url_input,
                'thumbnail'   => $s->thumbnail_url,
                // Short blurb shown under the player. Falls back to the
                // publisher's own description when no synopsis was generated.
                'synopsis'    => $this->synopsisFor($s),
            ];
        })->values();

        $data['ads'] = $this->adPool();

        return response()->json([
            'status'  => true,
            'message' => 'VeeMag issue retrieved successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Ads available to the reader, split by shape.
     *
     * `flyer` is the full-page unit (one per page, portrait, like a magazine
     * back cover); `banner` is the horizontal unit (two stack in the same
     * space). House ads - our own "advertise with us" creative - are marked
     * with url_type `house` and open the enquiry form instead of a link.
     * The app decides which pages carry which; the server just supplies
     * what is bookable today.
     */
    private function adPool(): array
    {
        $today = \Carbon\Carbon::now()->toDateString();

        $rows = \Modules\Ad\Models\CustomAdsSetting::query()
            ->where('status', 1)
            ->whereIn('placement', ['veemag_flyer', 'veemag_banner'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('id')
            ->get(['id', 'name', 'type', 'placement', 'media', 'redirect_url', 'url_type']);

        $shape = fn ($r) => [
            'id'           => $r->id,
            'name'         => (string) $r->name,
            'type'         => (string) $r->type,
            'url'          => $r->media ? setBaseUrlWithFileName($r->media, $r->type, 'ads') : null,
            'redirect_url' => (string) $r->redirect_url,
            // internal = open in app, external = confirm before leaving,
            // house = open the advertiser enquiry form.
            'url_type'     => (string) ($r->url_type ?: 'internal'),
        ];

        return [
            'flyer'  => $rows->where('placement', 'veemag_flyer')->map($shape)->values(),
            'banner' => $rows->where('placement', 'veemag_banner')->map($shape)->values(),
        ];
    }

    /**
     * Copy is pasted into the dashboard from editors and word processors,
     * which brings HTML with it. The app renders these fields as plain
     * text, so markup reached the screen verbatim - <p> tags and all.
     * Block tags become line breaks so paragraphs survive the strip.
     */
    private function plainText(?string $raw): string
    {
        $text = (string) $raw;
        $text = preg_replace('~<\s*br\s*/?\s*>~i', PHP_EOL, $text) ?? $text;
        $text = preg_replace('~</\s*(p|div|li|h[1-6])\s*>~i', PHP_EOL . PHP_EOL, $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('~[^\S\r\n]+~', ' ', $text) ?? $text;
        $text = preg_replace('~(\r?\n){3,}~', PHP_EOL . PHP_EOL, $text) ?? $text;

        return trim($text);
    }

    /** Prefer the generated synopsis, fall back to a trimmed description. */
    private function synopsisFor($s): string
    {
        if (filled($s->synopsis)) {
            return $this->plainText($s->synopsis);
        }

        $text = $this->plainText($s->description);

        return $text === '' ? '' : Str::limit($text, 500, '...');
    }

    private function listItem(VeeMagIssue $i): array
    {
        return [
            'id'            => $i->id,
            'slug'          => $i->slug,
            'title'         => $i->title,
            'subtitle'      => $i->subtitle,
            'publication'   => optional($i->publication)->title ?? '',
            'issue_label'   => $i->issue_label,
            'cover_url'     => $i->cover_url,
            'hero_url'      => $i->hero_url ?: $i->cover_url,
            'hero_type'     => $i->hero_type,
            'release_date'  => optional($i->release_date)->toDateString(),
            'runtime'       => (int) $i->runtime_seconds,
            'section_count' => (int) ($i->sections_count ?? $i->sections()->count()),
            // Print companion: the app shows a Print button when this is true.
            'print_enabled' => (bool) $i->print_enabled,
            'print_price'   => round($i->print_price_effective, 2),
            'print_currency' => strtoupper((string) GetcurrentCurrency() ?: 'USD'),
        ];
    }

    private function videoId(?string $type, ?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (Str::lower((string) $type) === 'youtube'
            && preg_match('~(?:youtu\.be/|watch\?v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        if (Str::lower((string) $type) === 'vimeo'
            && preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
