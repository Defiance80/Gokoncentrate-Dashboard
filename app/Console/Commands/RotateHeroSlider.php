<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Banner\Services\HeroRotationService;

/**
 * Weekly refresh of the home hero slider from trending content.
 *
 * Safe to run by hand: locked slides are never touched, and a slide is only
 * replaced when a candidate clears both the view floor and the artwork check.
 */
class RotateHeroSlider extends Command
{
    protected $signature = 'hero:rotate {--dry-run : Report what would change without writing}';

    protected $description = 'Refresh unlocked hero slides from trending content';

    public function handle(HeroRotationService $rotation): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $rotation->rotate($dry);

        $this->info($dry ? 'Hero rotation (dry run)' : 'Hero rotation');
        $this->line("  candidates considered : {$result['considered']}");
        $this->line("  slides replaced       : {$result['replaced']}");
        $this->line("  slides kept           : {$result['skipped']}");
        $this->line("  slides locked         : {$result['locked']}");

        foreach ($result['notes'] as $note) {
            $this->line('  - ' . $note);
        }

        return self::SUCCESS;
    }
}
