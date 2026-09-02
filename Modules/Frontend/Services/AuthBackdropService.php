<?php

namespace Modules\Frontend\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Artwork for the signed-out screens.
 *
 * The sign-in page is now the site's splash page, so it shows a wall of real
 * poster art from the catalogue behind the form: visitors see what the app
 * actually looks like before they have an account.
 *
 * Every failure path returns an empty list so the sign-in screen still renders
 * (it falls back to the static banner) even if the database is unreachable.
 */
class AuthBackdropService
{
    public const CACHE_KEY = 'frontend:auth_backdrop_posters';

    public const CACHE_TTL = 60 * 60 * 6;

    /**
     * Poster URLs ready to drop straight into an <img src>.
     *
     * @return list<string>
     */
    public function posters(int $limit = 28): array
    {
        try {
            $posters = Cache::remember(
                self::CACHE_KEY . ':' . $limit,
                self::CACHE_TTL,
                fn () => $this->fetch($limit)
            );

            return is_array($posters) ? $posters : [];
        } catch (\Throwable $e) {
            Log::warning('[Frontend] Auth backdrop unavailable: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function fetch(int $limit): array
    {
        $urls = [];

        // Movies and TV shows carry the portrait artwork, so they lead.
        foreach ($this->rows('entertainments', $limit) as $row) {
            $urls[] = setBaseUrlWithFileName($row->poster_url, 'image', 'movie');
        }

        // Top up from standalone videos if the catalogue is still small.
        if (count($urls) < $limit) {
            foreach ($this->rows('videos', $limit - count($urls)) as $row) {
                $urls[] = setBaseUrlWithFileName($row->poster_url, 'image', 'video');
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * Query builder rather than Eloquent: this runs for signed-out visitors on
     * every cache miss, and it must not be affected by model scopes or by the
     * Entertainment model's unusual constructor.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function rows(string $table, int $limit)
    {
        return DB::table($table)
            ->select('poster_url')
            ->whereNotNull('poster_url')
            ->where('poster_url', '!=', '')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->when($table === 'entertainments', fn ($q) => $q->where('is_restricted', 0))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public static function flush(): void
    {
        for ($limit = 1; $limit <= 40; $limit++) {
            Cache::forget(self::CACHE_KEY . ':' . $limit);
        }
    }
}
