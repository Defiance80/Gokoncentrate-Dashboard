<?php

namespace Modules\MediaRadar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\MediaRadar\Jobs\Concerns\MediaRadarJob;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Services\DiscoveryService;
use Modules\MediaRadar\Services\ProviderHealthService;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Searches one platform for one rule.
 *
 * A failure here is confined to this run: other rules, other platforms and
 * already-discovered candidates are untouched.
 */
class ExecuteDiscoveryRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use MediaRadarJob;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $runId)
    {
        $this->onQueue($this->queueName());
    }

    public function handle(DiscoveryService $discovery, ProviderHealthService $health): void
    {
        $run = MediaDiscoveryRun::find($this->runId);

        if ($run === null) {
            return;
        }

        if ($health->isCoolingDown($run->provider)) {
            $run->markFailed(ucfirst($run->provider).' is in a rate-limit cooldown; the run was skipped.');

            return;
        }

        try {
            $discovery->executeRun($run);
        } catch (ProviderException $e) {
            // Permanent problems (bad credentials, exhausted quota) are recorded
            // on the run and not retried in a loop.
            if (! $e->retryable) {
                Log::warning('[MediaRadar] Run '.$run->id.' stopped: '.$e->getMessage());

                return;
            }

            $this->release($e->retryAfterSeconds ?? 300);
        }
    }

    public function failed(\Throwable $e): void
    {
        $run = MediaDiscoveryRun::find($this->runId);

        if ($run !== null && $run->status !== MediaDiscoveryRun::STATUS_FAILED) {
            $run->markFailed($e->getMessage());
        }
    }
}
