<?php

namespace Modules\Genres\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/**
 * A genre as it appears in a browsing rail or the genre list.
 *
 * Differs from GenresResource in one way: the artwork is the poster of the
 * newest title in that genre, rather than a static image uploaded against
 * the genre itself. A rail that shows what is actually inside stays current
 * on its own as the catalogue changes.
 *
 * GenresResource is still used for the genre tags on an individual title,
 * where none of this applies.
 */
class GenreSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id ?? null,
            'name'         => $this->name ?? null,
            'poster_image' => $this->artwork(),
            'status'       => $this->status ?? null,
        ];
    }

    /** Newest title's poster, falling back to the genre's own image. */
    private function artwork(): ?string
    {
        $latest = self::latestTitle((int) $this->id);

        if ($latest && filled($latest->poster_url)) {
            // Posters resolve from a folder named after the content type.
            return setBaseUrlWithFileName($latest->poster_url, 'image', $latest->type ?: 'movie');
        }

        return setBaseUrlWithFileName($this->file_url, 'image', 'genres');
    }

    private static function latestTitle(int $genreId)
    {
        return DB::table('entertainment_gener_mapping as m')
            ->join('entertainments as e', 'e.id', '=', 'm.entertainment_id')
            ->where('m.genre_id', $genreId)
            ->whereNull('m.deleted_at')
            ->whereNull('e.deleted_at')
            ->where('e.status', 1)
            ->where('e.is_veemag', 0)
            ->whereNotNull('e.poster_url')
            ->where('e.poster_url', '!=', '')
            ->orderByDesc('e.release_date')
            ->orderByDesc('e.id')
            ->first(['e.poster_url', 'e.type']);
    }

    /**
     * Genre ids that currently hold at least one live title.
     *
     * Used to keep empty genres out of the browsing rails - an open
     * category with nothing behind it reads as a broken app.
     */
    public static function idsWithContent(): array
    {
        return DB::table('entertainment_gener_mapping as m')
            ->join('entertainments as e', 'e.id', '=', 'm.entertainment_id')
            ->whereNull('m.deleted_at')
            ->whereNull('e.deleted_at')
            ->where('e.status', 1)
            ->where('e.is_veemag', 0)
            ->distinct()
            ->pluck('m.genre_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
