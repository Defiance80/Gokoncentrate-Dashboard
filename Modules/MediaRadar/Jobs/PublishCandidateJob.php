<?php

namespace Modules\MediaRadar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MediaRadar\Jobs\Concerns\MediaRadarJob;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Services\PublishingBridgeService;
use Modules\MediaRadar\Support\CandidateStatus;

/**
 * Hands an approved candidate to the existing publishing pipeline.
 */
class PublishCandidateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use MediaRadarJob;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $candidateId,
        public ?int $editorId = null,
    ) {
        $this->onQueue($this->queueName());
    }

    public function handle(PublishingBridgeService $publishing): void
    {
        $candidate = MediaCandidate::find($this->candidateId);

        if ($candidate === null) {
            return;
        }

        if (! in_array($candidate->status, [CandidateStatus::APPROVED, CandidateStatus::SCHEDULED], true)) {
            return;
        }

        if ($candidate->published_entertainment_id) {
            return;
        }

        $publishing->publish($candidate, $this->editorId);
    }

    public function failed(\Throwable $e): void
    {
        $candidate = MediaCandidate::find($this->candidateId);

        if ($candidate !== null) {
            $candidate->forceFill([
                'error_message' => mb_substr('Publishing failed: '.$e->getMessage(), 0, 1000),
                'failed_attempts' => (int) $candidate->failed_attempts + 1,
            ])->save();
        }
    }
}
