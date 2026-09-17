<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Carbon;
use Modules\MediaRadar\Jobs\PublishCandidateJob;
use Modules\MediaRadar\Models\MediaBlockedSource;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Support\CandidateStatus;

/**
 * Approve, reject, schedule and archive.
 *
 * This is also where the auto/manual approval switch is honoured. Manual is the
 * default: nothing reaches the public app without a decision being recorded.
 */
class EditorialWorkflowService
{
    public function __construct(
        private CandidateService $candidates,
        private PublishingBridgeService $publishing,
    ) {
    }

    /**
     * Called at the end of analysis. Returns true when the candidate was
     * approved without a human.
     */
    public function applyAutoApproval(MediaCandidate $candidate, ?MediaDiscoveryRule $rule = null): bool
    {
        $settings = MediaRadarSetting::getInstance();
        $rule = $rule ?? $candidate->rules()->first();

        if (! $this->autoApprovalAllowed($candidate, $settings, $rule)) {
            return false;
        }

        $this->approve($candidate, null, $settings->auto_publish_after_approval, 'auto_approved');

        return true;
    }

    private function autoApprovalAllowed(MediaCandidate $candidate, MediaRadarSetting $settings, ?MediaDiscoveryRule $rule): bool
    {
        if ($candidate->status !== CandidateStatus::READY_FOR_REVIEW) {
            return false;
        }

        if (! $candidate->embeddable) {
            return false;
        }

        $enabled = $rule ? $rule->autoApproveEnabled($settings) : (bool) $settings->auto_approve;

        if (! $enabled) {
            return false;
        }

        $threshold = (int) ($rule->minimum_editorial_score ?? $settings->auto_approve_min_score);

        if ((int) $candidate->editorial_score < max($threshold, (int) $settings->auto_approve_min_score)) {
            return false;
        }

        // A creator that has been blocked is never auto-approved, whatever the
        // rule says.
        return ! $this->isBlockedCreator($candidate);
    }

    public function approve(
        MediaCandidate $candidate,
        ?int $editorId = null,
        bool $publishNow = false,
        string $decision = 'approved',
        ?string $notes = null
    ): bool {
        $previous = (string) $candidate->status;

        if (! $this->candidates->transition($candidate, CandidateStatus::APPROVED, [
            'approved_by' => $editorId,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ])) {
            return false;
        }

        $this->candidates->recordDecision($candidate, $decision, $previous, CandidateStatus::APPROVED, $editorId, null, $notes);
        $this->bumpTrustedSource($candidate, 'approval_count');

        if ($publishNow) {
            // Publish synchronously so a manual "Accept & publish" goes live
            // immediately, even when no queue worker is running (shared hosting).
            $candidate->refresh();
            $this->publishing->publish($candidate, $editorId);
        }

        return true;
    }

    public function reject(
        MediaCandidate $candidate,
        ?int $editorId,
        ?string $reason = null,
        ?string $notes = null,
        bool $blockCreator = false
    ): bool {
        $previous = (string) $candidate->status;

        if (! $this->candidates->transition($candidate, CandidateStatus::REJECTED, [
            'rejected_by' => $editorId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])) {
            return false;
        }

        $this->candidates->recordDecision($candidate, 'rejected', $previous, CandidateStatus::REJECTED, $editorId, $reason, $notes);
        $this->bumpTrustedSource($candidate, 'rejection_count');

        if ($blockCreator) {
            $this->blockCreator($candidate, $editorId, $reason);
        }

        return true;
    }

    public function schedule(MediaCandidate $candidate, Carbon $when, ?int $editorId = null): bool
    {
        $previous = (string) $candidate->status;

        if (! $this->candidates->transition($candidate, CandidateStatus::SCHEDULED, ['scheduled_for' => $when])) {
            return false;
        }

        $this->candidates->recordDecision($candidate, 'scheduled', $previous, CandidateStatus::SCHEDULED, $editorId);

        return true;
    }

    public function archive(MediaCandidate $candidate, ?int $editorId = null): bool
    {
        $previous = (string) $candidate->status;

        if (! $this->candidates->transition($candidate, CandidateStatus::ARCHIVED)) {
            return false;
        }

        $this->candidates->recordDecision($candidate, 'archived', $previous, CandidateStatus::ARCHIVED, $editorId);

        return true;
    }

    /**
     * Publish immediately, used by the "Publish now" action and by the
     * scheduled publish job.
     */
    public function publishNow(MediaCandidate $candidate, ?int $editorId = null): bool
    {
        if ($candidate->status === CandidateStatus::READY_FOR_REVIEW) {
            $this->approve($candidate, $editorId);
            $candidate->refresh();
        }

        if (! in_array($candidate->status, [CandidateStatus::APPROVED, CandidateStatus::SCHEDULED], true)) {
            return false;
        }

        $this->publishing->publish($candidate, $editorId);

        return true;
    }

    public function trustCreator(MediaCandidate $candidate, ?int $editorId = null): ?MediaTrustedSource
    {
        if (empty($candidate->provider_creator_id)) {
            return null;
        }

        return MediaTrustedSource::updateOrCreate(
            [
                'publication_id' => $candidate->publication_id,
                'provider' => $candidate->provider,
                'provider_creator_id' => $candidate->provider_creator_id,
            ],
            [
                'creator_name' => $candidate->creator_name,
                'creator_url' => $candidate->creator_url,
                'enabled' => true,
                'default_genre_id' => $candidate->genre_id,
                'publishing_mode' => 'approval_required',
                'updated_by' => $editorId,
            ]
        );
    }

    public function blockCreator(MediaCandidate $candidate, ?int $editorId = null, ?string $reason = null): ?MediaBlockedSource
    {
        if (empty($candidate->provider_creator_id)) {
            return null;
        }

        MediaTrustedSource::query()
            ->where('publication_id', $candidate->publication_id)
            ->where('provider', $candidate->provider)
            ->where('provider_creator_id', $candidate->provider_creator_id)
            ->update(['enabled' => false]);

        return MediaBlockedSource::updateOrCreate(
            [
                'publication_id' => $candidate->publication_id,
                'provider' => $candidate->provider,
                'provider_creator_id' => $candidate->provider_creator_id,
            ],
            [
                'creator_name' => $candidate->creator_name,
                'reason' => $reason,
                'blocked_by' => $editorId,
            ]
        );
    }

    public function isBlockedCreator(MediaCandidate $candidate): bool
    {
        if (empty($candidate->provider_creator_id)) {
            return false;
        }

        return MediaBlockedSource::query()
            ->where('publication_id', $candidate->publication_id)
            ->where('provider', $candidate->provider)
            ->where('provider_creator_id', $candidate->provider_creator_id)
            ->exists();
    }

    private function bumpTrustedSource(MediaCandidate $candidate, string $column): void
    {
        if (empty($candidate->provider_creator_id)) {
            return;
        }

        MediaTrustedSource::query()
            ->where('publication_id', $candidate->publication_id)
            ->where('provider', $candidate->provider)
            ->where('provider_creator_id', $candidate->provider_creator_id)
            ->increment($column);
    }
}
