<?php

namespace Modules\MediaRadar\Console;

use Illuminate\Console\Command;
use Modules\MediaRadar\Jobs\RunDiscoveryRuleJob;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Services\DiscoveryService;

/**
 * Scheduler entry point. Discovery never runs from an HTTP request.
 */
class RunDiscoveryCommand extends Command
{
    protected $signature = 'media-radar:discover
                            {--rule= : Run one rule by id, ignoring its schedule}
                            {--sync : Execute the runs inline instead of queueing them}';

    protected $description = 'Queue the Media Radar discovery rules that are due to run';

    public function handle(DiscoveryService $discovery): int
    {
        if (! config('mediaradar.enabled', true)) {
            $this->warn('Media Radar is disabled in configuration.');

            return self::SUCCESS;
        }

        $settings = MediaRadarSetting::getInstance();

        if (! $settings->enabled) {
            $this->warn('Media Radar is switched off in the dashboard settings.');

            return self::SUCCESS;
        }

        $rules = $this->option('rule')
            ? MediaDiscoveryRule::where('id', (int) $this->option('rule'))->get()
            : $discovery->dueRules();

        if ($rules->isEmpty()) {
            $this->info('No discovery rules are due.');

            return self::SUCCESS;
        }

        foreach ($rules as $rule) {
            $trigger = $this->option('rule') ? 'manual' : 'schedule';

            if ($this->option('sync')) {
                RunDiscoveryRuleJob::dispatchSync($rule->id, $trigger);
            } else {
                RunDiscoveryRuleJob::dispatch($rule->id, $trigger);
            }

            $this->line(sprintf('Queued rule #%d "%s" for %s.', $rule->id, $rule->name, implode(', ', $rule->providerSlugs())));
        }

        $this->info($rules->count().' discovery rule(s) queued.');

        return self::SUCCESS;
    }
}
