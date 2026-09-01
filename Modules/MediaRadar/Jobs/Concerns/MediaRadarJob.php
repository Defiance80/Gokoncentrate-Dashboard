<?php

namespace Modules\MediaRadar\Jobs\Concerns;

/**
 * Shared retry policy for every Media Radar job.
 *
 * Attempt 1 is immediate, then 1 minute, 5 minutes, 30 minutes and 2 hours,
 * each with jitter so a provider outage does not produce a thundering herd
 * when the queue drains.
 */
trait MediaRadarJob
{
    public function queueName(): string
    {
        return (string) config('mediaradar.queue', 'default');
    }

    public function tries(): int
    {
        return (int) config('mediaradar.retry.max_attempts', 5);
    }

    /**
     * @return list<int> seconds
     */
    public function backoff(): array
    {
        $minutes = (array) config('mediaradar.retry.backoff', [1, 5, 30, 120]);

        return array_map(
            static fn ($m): int => ((int) $m * 60) + random_int(0, 30),
            $minutes
        );
    }
}
