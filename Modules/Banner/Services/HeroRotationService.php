<?php

namespace Modules\Banner\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Banner\Models\Banner;

/**
 * Refreshes the home hero slider from what people are actually watching.
 *
 * Three rules, in order of authority:
 *  1. A locked slide is never touched. Curation always beats the algorithm.
 *  2. A replacement must clear a minimum view count. Below that, view data is
 *     noise rather than a signal, and a hero slot is too prominent to fill on
 *     noise.
 *  3. A replacement must have artwork good enough for a full-bleed hero. A
 *     sharp title with a poor image is still a bad slide.
 *
 * If no candidate satisfies all three, the existing slide stays — the user's
 * rule: "if nothing adequate, keep the slide".
 */
class HeroRotationService
{
    /** Minimum views before a title may claim a hero slot. */
    private const DEFAULT_MIN_VIEWS = 25;

    /** A hero image is full-bleed and wide; anything smaller looks broken. */
    private const DEFAULT_MIN_WIDTH = 1280;

    /** Accept roughly 16:9 and wider; portrait posters do not work here. */
    private const MIN_ASPECT = 1.4;

    /**
     * Run a rotation pass.
     *
     * @param  bool  $dryRun  Report what would change without writing.
     * @return array{replaced:int,skipped:int,locked:int,considered:int,notes:list<string>}
     */
    public function rotate(bool $dryRun = false): array
    {
        $minViews = (int) (GetSettingValue('hero_rotate_min_views') ?: self::DEFAULT_MIN_VIEWS);
        $minWidth = (int) (GetSettingValue('hero_rotate_min_width') ?: self::DEFAULT_MIN_WIDTH);

        $notes = [];
        $replaced = 0;
        $skipped = 0;

        $slides = Banner::where('banner_for', 'home')->whereNull('deleted_at')->orderBy('id')->get();
        $locked = $slides->where('is_locked', 1)->count();
        $slots = $slides->where('is_locked', 0)->where('auto_managed', 1);

        if ($slots->isEmpty()) {
            $notes[] = 'No auto-managed, unlocked slides to rotate.';

            return compact('replaced', 'skipped', 'locked', 'notes') + ['considered' => 0];
        }

        $candidates = $this->trendingCandidates($minViews);
        $considered = $candidates->count();

        if ($candidates->isEmpty()) {
            $notes[] = "No title has reached {$minViews} views, so every slide was kept.";

            return compact('replaced', 'skipped', 'locked', 'considered', 'notes');
        }

        // Never show the same title twice, and never displace a locked slide's title.
        $alreadyOnSlider = $slides->pluck('type_id')->filter()->map(fn ($v) => (int) $v)->all();

        foreach ($slots as $slide) {
            $pick = $candidates->first(function ($c) use ($alreadyOnSlider, $minWidth) {
                return ! in_array((int) $c->id, $alreadyOnSlider, true)
                    && $this->artworkIsAdequate($c, $minWidth);
            });

            if (! $pick) {
                $skipped++;
                $notes[] = "Slide #{$slide->id} kept: no candidate with adequate hero artwork.";
                continue;
            }

            $notes[] = "Slide #{$slide->id} -> {$pick->name} ({$pick->view_count} views).";
            $alreadyOnSlider[] = (int) $pick->id;
            $replaced++;

            if ($dryRun) {
                continue;
            }

            // The slide renders banners.file_url, so the artwork has to travel
            // with the title. Otherwise the slot would show the previous
            // title's art under a new name.
            $update = [
                'title'           => $pick->name,
                'type'            => $pick->type,
                'type_id'         => $pick->id,
                'type_name'       => $pick->name,
                'last_rotated_at' => now(),
            ];
            if ($file = $this->installHeroArtwork($pick, $minWidth)) {
                $update['file_url'] = $file;
            }
            $slide->update($update);
        }

        Log::info('Hero rotation complete', compact('replaced', 'skipped', 'locked', 'dryRun'));

        return compact('replaced', 'skipped', 'locked', 'considered', 'notes');
    }

    /**
     * Published titles ordered by view count, floor applied.
     *
     * Views live in `entertainment_views` (one row per view). Rows with a
     * non-positive entertainment_id are junk and are excluded.
     */
    public function trendingCandidates(int $minViews)
    {
        return DB::table('entertainment_views as v')
            ->join('entertainments as e', 'e.id', '=', 'v.entertainment_id')
            ->whereNull('e.deleted_at')
            ->whereNull('v.deleted_at')
            ->where('e.status', 1)
            ->where('v.entertainment_id', '>', 0)
            ->select(
                'e.id',
                'e.name',
                'e.type',
                'e.poster_url',
                'e.poster_tv_url',
                'e.thumbnail_url',
                DB::raw('COUNT(v.id) as view_count')
            )
            ->groupBy('e.id', 'e.name', 'e.type', 'e.poster_url', 'e.poster_tv_url', 'e.thumbnail_url')
            ->havingRaw('COUNT(v.id) >= ?', [$minViews])
            ->orderByDesc('view_count')
            ->limit(40)
            ->get();
    }

    /** Does this title have artwork good enough for a full-bleed hero slot? */
    private function artworkIsAdequate(object $candidate, int $minWidth): bool
    {
        return $this->heroArtworkPath($candidate, $minWidth) !== null;
    }

    /**
     * Absolute path to a landscape image usable as hero art, or null.
     *
     * Deliberately does NOT consider poster_url: that is the 2:3 library
     * poster and can never fill a 16:9 hero. Only the landscape fields are
     * eligible, and only when the file is actually on disk at a usable size —
     * a stored path can outlive its file, and a broken hero is the most
     * visible failure on the site.
     */
    private function heroArtworkPath(object $candidate, int $minWidth): ?string
    {
        foreach (['poster_tv_url', 'thumbnail_url'] as $field) {
            $value = $candidate->$field ?? null;
            if (empty($value) || preg_match('~^https?://~i', (string) $value)) {
                continue; // remote artwork is not ours to serve from the hero
            }

            $path = storage_path('app/public/movie/image/' . $value);
            if (! is_file($path)) {
                continue;
            }

            $size = @getimagesize($path);
            if (! $size || $size[0] < $minWidth) {
                continue;
            }
            if (($size[0] / max($size[1], 1)) < self::MIN_ASPECT) {
                continue;
            }

            return $path;
        }

        return null;
    }

    /**
     * Copy the chosen artwork into the banner folder and return its filename.
     *
     * A new filename every time: overwriting in place leaves browsers and the
     * CDN serving the previous image.
     */
    private function installHeroArtwork(object $candidate, int $minWidth): ?string
    {
        $source = $this->heroArtworkPath($candidate, $minWidth);
        if (! $source) {
            return null;
        }

        $ext = pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg';
        $name = 'hero-auto-' . $candidate->id . '-' . now()->format('YmdHis') . '.' . $ext;
        $target = storage_path('app/public/banner/' . $name);

        if (! is_dir(dirname($target))) {
            @mkdir(dirname($target), 0755, true);
        }

        if (! @copy($source, $target)) {
            Log::warning('Hero rotation: could not install artwork', ['source' => $source]);

            return null;
        }
        @chmod($target, 0644);

        return $name;
    }
}
