<?php

namespace Modules\MediaRadar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\MediaRadar\Jobs\Concerns\MediaRadarJob;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Services\DiscoveryService;

/**
 * Fans one rule out into one run per platform.
 */
class RunDiscoveryRuleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use MediaRadarJob;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $ruleId,
        public string $trigger = 'schedule',
        public ?int $userId = null,
    ) {
        $this->onQueue($this->queueName());
    }

    public function handle(DiscoveryService $discovery): void
    {
        $rule = MediaDiscoveryRule::find($this->ruleId);

        if ($rule === null) {
            Log::info('[MediaRadar] Discovery rule '.$this->ruleId.' no longer exists; nothing queued.');

            return;
        }

        foreach ($discovery->queueRule($rule, $this->trigger, $this->userId) as $runId) {
            ExecuteDiscoveryRunJob::dispatch($runId);
        }
    }
}
