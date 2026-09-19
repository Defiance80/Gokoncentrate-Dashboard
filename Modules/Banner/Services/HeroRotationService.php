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

            $slide->update([
                'title'           => $pick->name,
                'type'            => $pick->type,
                'type_id'         => $pick->id,
                'type_name'       => $pick->name,
                'last_rotated_at' => now(),
            ]);
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
                DB::raw('COUNT(v.id) as view_count')
            )
            ->groupBy('e.id', 'e.name', 'e.type', 'e.poster_url')
            ->havingRaw('COUNT(v.id) >= ?', [$minViews])
            ->orderByDesc('view_count')
            ->limit(40)
            ->get();
    }

    /**
     * Is this title's artwork good enough for a full-bleed hero slot?
     *
     * Checked on disk rather than trusted, because a stored path can outlive
     * the file and a missing hero image is the most visible failure there is.
     */
    private function artworkIsAdequate(object $candidate, int $minWidth): bool
    {
        if (empty($candidate->poster_url)) {
            return false;
        }

        $path = storage_path('app/public/movie/image/' . $candidate->poster_url);
        if (! is_file($path)) {
            return false;
        }

        $size = @getimagesize($path);
        if (! $size || $size[0] < $minWidth) {
            return false;
        }

        return ($size[0] / max($size[1], 1)) >= self::MIN_ASPECT;
    }
}
