<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Rewrites content slugs into clean, readable URLs and records the old ones.
 *
 * Live data carries slugs such as
 *   koncentrate-magazine:-volume-1
 *   koncentrate-magazine-–-volume-1-cover-feature:-dr.-sebi-–-the-untold-story,-life-&-legacy-...
 * - colons, ampersands, commas and en dashes, so they were never put through
 * Str::slug. They read badly in a search result, and the website's whole job is
 * to be a search result.
 *
 * Every replaced slug is written to slug_history first, so the old URL keeps
 * working as a 301. Dry run by default: nothing changes without --apply.
 */
class NormalizeSlugs extends Command
{
    protected $signature = 'gk:normalize-slugs
                            {--apply : Write the changes. Without this the command only reports.}
                            {--max-length=70 : Longest slug to produce, cut on a word boundary.}
                            {--retitle : Also rewrite tidy slugs that no longer match their title.}';

    protected $description = 'Clean up content slugs, keeping the old ones working as redirects';

    /** @var array<string, array{table: string, type: string}> */
    private array $targets = [
        'Films & series' => ['table' => 'entertainments', 'type' => 'entertainment'],
        'Episodes'       => ['table' => 'episodes',       'type' => 'episode'],
        'Videos'         => ['table' => 'videos',         'type' => 'video'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $maxLength = max(20, (int) $this->option('max-length'));
        $retitle = (bool) $this->option('retitle');

        // Only a real run needs the history table; previewing should work
        // before the migration has been run, so the change can be read first.
        if ($apply && !Schema::hasTable('slug_history')) {
            $this->error('slug_history is missing - run php artisan migrate --force first.');
            return self::FAILURE;
        }

        $this->line($apply ? '*** APPLYING ***' : '*** DRY RUN - nothing will be written ***');

        $totalChanged = 0;

        foreach ($this->targets as $label => $target) {
            if (!Schema::hasTable($target['table'])) {
                $this->line("  skipping {$label} - no {$target['table']} table");
                continue;
            }

            $rows = DB::table($target['table'])
                ->select('id', 'name', 'slug')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            // Slugs already in use, so a cleaned slug never collides with one.
            $taken = $rows->pluck('slug')->filter()->map(fn ($s) => (string) $s)->flip();

            $changed = 0;
            $this->newLine();
            $this->line("== {$label} ({$rows->count()} rows)");

            foreach ($rows as $row) {
                if (empty($row->name)) {
                    continue;
                }

                $clean = $this->cleanSlug($row->name, $maxLength);
                if ($clean === '') {
                    $this->warn("  #{$row->id} produced an empty slug from \"{$row->name}\" - left alone");
                    continue;
                }

                $current = (string) ($row->slug ?? '');
                if ($clean === $current) {
                    continue;
                }

                // A slug that is already tidy is left alone, even when the title
                // has since changed. It may already be indexed or shared, and
                // churning a working URL for a cosmetic match costs more than
                // the match is worth. --retitle opts into that.
                if (!$retitle && $this->isTidy($current, $maxLength)) {
                    continue;
                }

                // Keep it unique. The row's own slug is not a collision.
                $candidate = $clean;
                $n = 2;
                while ($taken->has($candidate) && $candidate !== $current) {
                    $candidate = $clean . '-' . $n;
                    $n++;
                }

                if ($candidate === $current) {
                    continue;
                }

                $changed++;
                $totalChanged++;
                $this->line("  #{$row->id}");
                $this->line("     from: {$current}");
                $this->line("       to: {$candidate}");

                if (!$apply) {
                    continue;
                }

                DB::transaction(function () use ($target, $row, $candidate, $current) {
                    // Record the old slug before overwriting it. updateOrInsert
                    // keeps the (type, old_slug) unique index happy if a slug
                    // has been retired once already.
                    if ($current !== '') {
                        DB::table('slug_history')->updateOrInsert(
                            ['entity_type' => $target['type'], 'old_slug' => $current],
                            [
                                'entity_id' => $row->id,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }

                    DB::table($target['table'])
                        ->where('id', $row->id)
                        ->update(['slug' => $candidate]);
                });

                // A freed-up slug must not be handed to a later row in this run.
                $taken->forget($current);
                $taken->put($candidate, true);
            }

            $this->line("  {$changed} to change");
        }

        $this->newLine();
        if ($apply) {
            $this->info("Done. {$totalChanged} slugs rewritten; the old ones now 301 to the new.");
            $this->line('Clear the caches:  php artisan optimize:clear');
        } else {
            $this->info("{$totalChanged} slugs would change. Re-run with --apply to write them.");
        }

        return self::SUCCESS;
    }

    /**
     * Is this slug already a good URL? Lowercase words joined by single
     * hyphens, no leading or trailing hyphen, and short enough to survive a
     * search result without being truncated.
     */
    private function isTidy(string $slug, int $maxLength): bool
    {
        if ($slug === '' || strlen($slug) > $maxLength) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Str::slug does the transliteration and the stripping; the rest is length.
     * A slug that runs to 200 characters is truncated in every search result,
     * so cut it at a word boundary rather than mid-word.
     */
    private function cleanSlug(string $name, int $maxLength): string
    {
        $slug = Str::slug($name);

        if ($slug === '' || strlen($slug) <= $maxLength) {
            return $slug;
        }

        $cut = substr($slug, 0, $maxLength);
        $lastDash = strrpos($cut, '-');

        // Only fall back on a hard cut if there is no sensible word boundary.
        if ($lastDash !== false && $lastDash >= (int) ($maxLength * 0.5)) {
            $cut = substr($cut, 0, $lastDash);
        }

        return trim($cut, '-');
    }
}
