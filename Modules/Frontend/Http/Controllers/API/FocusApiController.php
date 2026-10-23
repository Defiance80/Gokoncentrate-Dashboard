<?php

namespace Modules\Frontend\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The Focus feed: everything worth listening to rather than watching.
 *
 * Draws from three places that already exist - podcast episodes, music
 * series episodes, and VeeMag sections - and returns them in one flat,
 * playable list. Focus previously read the Coming Soon endpoint and
 * filtered on a genre named "Focus" that was never created, so it could
 * not have returned anything.
 *
 * Each item carries its own playable source, because the listening queue
 * plays straight from this list without a second lookup.
 */
class FocusApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 30), 60));
        $page = max(1, (int) $request->input('page', 1));

        $items = collect()
            ->concat($this->seriesEpisodes())
            ->concat($this->veemagSections())
            ->sortByDesc('release_date')
            ->values();

        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Focus list retrieved successfully.',
            'data'    => $slice,
            'meta'    => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ]);
    }

    /** Episodes of podcast and music series - the core listening material. */
    private function seriesEpisodes()
    {
        return DB::table('episodes as e')
            ->join('entertainments as s', 's.id', '=', 'e.entertainment_id')
            ->whereNull('e.deleted_at')->where('e.status', 1)
            ->whereNull('s.deleted_at')->where('s.status', 1)
            ->where(function ($q) {
                $q->where('s.is_podcast', 1)->orWhere('s.is_music', 1);
            })
            ->whereNotNull('e.video_url_input')->where('e.video_url_input', '!=', '')
            ->orderByDesc('e.release_date')
            ->get([
                'e.id', 'e.name', 'e.description', 'e.short_desc', 'e.poster_url',
                'e.video_upload_type', 'e.video_url_input', 'e.release_date', 'e.duration',
                's.name as series_name', 's.is_music', 's.id as series_id',
            ])
            ->map(function ($r) {
                return $this->shape(
                    id: (int) $r->id,
                    name: (string) $r->name,
                    description: (string) ($r->short_desc ?: $r->description),
                    source: (string) $r->series_name,
                    kind: $r->is_music ? 'music' : 'podcast',
                    poster: $r->poster_url ? setBaseUrlWithFileName($r->poster_url, 'image', 'episode') : null,
                    uploadType: (string) $r->video_upload_type,
                    url: (string) $r->video_url_input,
                    duration: (string) ($r->duration ?? ''),
                    releaseDate: (string) ($r->release_date ?? ''),
                );
            });
    }

    /** VeeMag sections, which play as talk when the screen is not watched. */
    private function veemagSections()
    {
        return DB::table('veemag_sections as s')
            ->join('veemag_issues as i', 'i.id', '=', 's.issue_id')
            ->whereNull('s.deleted_at')->where('s.status', 1)
            ->whereNull('i.deleted_at')->where('i.status', 'published')
            ->whereNotNull('s.video_url_input')->where('s.video_url_input', '!=', '')
            ->orderByDesc('i.release_date')
            ->limit(60)
            ->get([
                's.id', 's.title', 's.synopsis', 's.description', 's.thumbnail_url',
                's.video_upload_type', 's.video_url_input', 's.runtime_seconds',
                'i.title as issue_title', 'i.release_date', 'i.cover_url',
            ])
            ->map(function ($r) {
                $secs = (int) $r->runtime_seconds;

                return $this->shape(
                    id: 900000 + (int) $r->id,   // kept distinct from episode ids
                    name: (string) $r->title,
                    description: (string) ($r->synopsis ?: $r->description),
                    source: (string) $r->issue_title,
                    kind: 'veemag',
                    poster: $r->thumbnail_url ?: ($r->cover_url ?: null),
                    uploadType: (string) $r->video_upload_type,
                    url: (string) $r->video_url_input,
                    duration: $secs ? sprintf('%02d:%02d', intdiv($secs, 60), $secs % 60) : '',
                    releaseDate: (string) ($r->release_date ?? ''),
                );
            });
    }

    /**
     * Shaped to match what the app's Focus list already parses, with the
     * playable source added so the queue needs no second request.
     */
    private function shape(
        int $id,
        string $name,
        string $description,
        string $source,
        string $kind,
        ?string $poster,
        string $uploadType,
        string $url,
        string $duration,
        string $releaseDate,
    ): array {
        return [
            'id'                => $id,
            'name'              => $name,
            'description'       => trim(strip_tags($description)),
            'type'              => 'episode',
            'focus_kind'        => $kind,       // podcast | music | veemag
            'source_name'       => $source,     // the series or issue it came from
            'thumbnail_image'   => $poster,
            'poster_image'      => $poster,
            'video_upload_type' => $uploadType,
            'video_url_input'   => $url,
            'duration'          => $duration,
            'release_date'      => $releaseDate,
            'language'          => 'en',
            'is_restricted'     => 0,
            'imdb_rating'       => 0,
            'content_rating'    => '',
            'trailer_url'       => '',
            'trailer_url_type'  => '',
            'season_name'       => '',
            'is_remind'         => 0,
            'genres'            => [],
        ];
    }
}
