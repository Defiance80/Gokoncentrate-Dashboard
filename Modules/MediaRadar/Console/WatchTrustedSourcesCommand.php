<?php

namespace Modules\MediaRadar\Console;

use Illuminate\Console\Command;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Services\TrustedSourceService;

class WatchTrustedSourcesCommand extends Command
{
    protected $signature = 'media-radar:watch-sources {--limit=10 : Recent uploads to inspect per creator}';

    protected $description = 'Check recent uploads from trusted YouTube and Vimeo creators';

    public function handle(TrustedSourceService $sources): int
    {
        if (! MediaRadarSetting::getInstance()->enabled) {
            $this->warn('Media Radar is switched off in the dashboard settings.');

            return self::SUCCESS;
        }

        $summary = $sources->refreshAll((int) $this->option('limit'));

        $this->info(sprintf(
            'Checked %d trusted source(s): %d new, %d already known, %d error(s).',
            $summary['checked'],
            $summary['new'],
            $summary['existing'],
            $summary['errors']
        ));

        return self::SUCCESS;
    }
}
