<?php

namespace Modules\MediaRadar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MediaRadar\Jobs\Concerns\MediaRadarJob;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\EditorialAnalysisService;
use Modules\MediaRadar\Services\EditorialWorkflowService;
use Modules\MediaRadar\Support\CandidateStatus;

/**
 * Scores a candidate, fills in the suggested title, description and tags, and
 * applies the auto-approval switch.
 *
 * The candidate row already exists before this runs, so a failure never loses
 * discovered content: the candidate simply sits in ANALYSIS_ERROR until it is
 * retried from the admin.
 */
class AnalyzeCandidateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use MediaRadarJob;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $candidateId,
        public ?int $ruleId = null,
    ) {
        $this->onQueue($this->queueName());
    }

    public function handle(
        EditorialAnalysisService $analysis,
        EditorialWorkflowService $workflow,
        CandidateService $candidates
    ): void {
        $candidate = MediaCandidate::find($this->candidateId);

        if ($candidate === null) {
            return;
        }

        if (! $candidate->embeddable) {
            $candidates->markEmbedUnavailable($candidate);

            return;
        }

        $candidates->transition($candidate, CandidateStatus::ANALYZING);

        $rule = $this->ruleId ? MediaDiscoveryRule::find($this->ruleId) : null;

        $analysis->analyze($candidate, $rule);

        $candidate->refresh();

        $workflow->applyAutoApproval($candidate, $rule);
    }

    public function failed(\Throwable $e): void
    {
        $candidate = MediaCandidate::find($this->candidateId);

        if ($candidate !== null) {
            app(CandidateService::class)->markAnalysisError($candidate, $e->getMessage());
        }
    }
}
