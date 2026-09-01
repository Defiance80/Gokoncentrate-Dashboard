<?php

namespace Modules\Magazine\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Magazine\Models\Magazine;
use Modules\Magazine\Models\MagazineIssue;

class MagazineController extends Controller
{
    /**
     * List magazine series (minimal fields).
     * GET /api/magazines
     */
    public function index(Request $request): JsonResponse
    {
        $query = Magazine::query()
            ->whereNull('deleted_at');

        $status = $request->query('status', 'published');
        if ($status) {
            $query->where('status', $status);
        }

        $list = $query->orderBy('title')
            ->get(['id', 'title', 'slug', 'cover_image_url', 'status', 'description']);

        return response()->json([
            'status' => true,
            'data' => $list,
        ]);
    }

    /**
     * List issues for a series by magazine slug.
     * GET /api/magazines/{slug}/issues?status=published
     */
    public function issues(Request $request, string $slug): JsonResponse
    {
        $magazine = Magazine::where('slug', $slug)->whereNull('deleted_at')->first();
        if (! $magazine) {
            return response()->json(['status' => false, 'message' => 'Magazine not found'], 404);
        }

        $status = $request->query('status', 'published');
        $query = $magazine->issues()->whereNull('magazine_issues.deleted_at')->where('magazine_issues.status', $status);

        $issues = $query->orderByDesc('release_date')
            ->get()
            ->map(function (MagazineIssue $issue) {
                $print = $issue->printConfig;
                return [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'slug' => $issue->slug,
                    'issue_number' => $issue->issue_number,
                    'release_date' => $issue->release_date?->format('Y-m-d'),
                    'cover_image_url' => $issue->cover_image_url,
                    'summary' => $issue->summary,
                    'print' => [
                        'enabled' => $print ? $print->print_enabled : false,
                        'cta_label' => $print ? $print->cta_label : 'Order Print Copy',
                    ],
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $issues,
        ]);
    }

    /**
     * Issue detail by slug (or id). Includes assets, access, and print config (safe fields only).
     * GET /api/magazine-issues/{slug}
     */
    public function show(string $slugOrId): JsonResponse
    {
        $issue = MagazineIssue::whereNull('magazine_issues.deleted_at')
            ->where('magazine_issues.status', 'published')
            ->where(function ($q) use ($slugOrId) {
                $q->where('magazine_issues.slug', $slugOrId)
                    ->orWhere('magazine_issues.id', (int) $slugOrId);
            })
            ->with(['magazine', 'assets', 'printConfig'])
            ->first();

        if (! $issue) {
            return response()->json(['status' => false, 'message' => 'Issue not found'], 404);
        }

        $print = $issue->printConfig;
        $printPayload = [
            'enabled' => $print ? $print->print_enabled : false,
            'cta_label' => $print ? $print->cta_label : 'Order Print Copy',
        ];
        if ($print && $print->print_enabled) {
            $printPayload['magcloud_product_url'] = $print->magcloud_product_url;
            $printPayload['magcloud_viewer_url'] = $print->magcloud_viewer_url;
            $printPayload['redirect_url'] = url('/r/magazine/' . $issue->id . '/print');
        }

        $data = [
            'id' => $issue->id,
            'magazine_id' => $issue->magazine_id,
            'title' => $issue->title,
            'slug' => $issue->slug,
            'issue_number' => $issue->issue_number,
            'release_date' => $issue->release_date?->format('Y-m-d'),
            'cover_image_url' => $issue->cover_image_url,
            'trailer_video_id' => $issue->trailer_video_id,
            'summary' => $issue->summary,
            'visibility' => $issue->visibility,
            'assets' => $issue->assets->map(fn ($a) => [
                'id' => $a->id,
                'asset_type' => $a->asset_type,
                'asset_id' => $a->asset_id,
                'title' => $a->title,
                'sort_order' => $a->sort_order,
            ]),
            'print' => $printPayload,
        ];

        return response()->json([
            'status' => true,
            'issue' => $data,
        ]);
    }
}
