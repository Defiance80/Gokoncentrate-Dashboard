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
        $data['description'] = $issue->description;
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
            ];
        })->values();

        return response()->json([
            'status'  => true,
            'message' => 'VeeMag issue retrieved successfully.',
            'data'    => $data,
        ]);
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
