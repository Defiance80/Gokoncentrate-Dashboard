<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\DB;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaCandidateRuleMatch;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaEditorialDecision;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\Quality;

/**
 * Owns candidate persistence and the state machine.
 *
 * Candidates are always written before analysis, so an AI outage can never
 * lose discovered content.
 */
class CandidateService
{
    public function __construct(
        private DeduplicationService $deduplication,
        private CoverArtService $coverArt,
    ) {
    }

    /**
     * Create or refresh the candidate for a normalized provider result.
     *
     * @return array{candidate: MediaCandidate, created: bool}
     */
    public function store(
        NormalizedCandidate $normalized,
        MediaRadarSetting $settings,
        ?MediaDiscoveryRule $rule = null,
        ?int $runId = null,
        array $matchedTerms = [],
        array $matchedActors = []
    ): array {
        $publicationId = (int) $settings->publication_id;
        $existing = $this->deduplication->existing($normalized, $publicationId);

        $candidate = $existing ?? new MediaCandidate();
        $created = $existing === null;

        $attributes = $this->attributesFrom($normalized, $publicationId);

        if ($created) {
            $attributes['status'] = CandidateStatus::DISCOVERED;
            $attributes['discovered_at'] = now();
            $attributes = array_merge($attributes, $this->coverArt->resolve($normalized, $settings));
            $attributes['genre_id'] = $rule?->genre_id;
            $attributes['secondary_genre_ids'] = $rule?->arrayValue('secondary_genre_ids') ?: null;
        } else {
            // Refresh volatile metrics only. Editorial fields an editor may have
            // touched, and the original provider identity, are left alone.
            $attributes = array_intersect_key($attributes, array_flip([
                'view_count', 'like_count', 'comment_count', 'embeddable',
                'duration_seconds', 'height', 'quality_label', 'quality_verified',
                'creator_name', 'creator_url', 'provider_url',
            ]));
        }

        $attributes['last_checked_at'] = now();
        $attributes['matched_actor_names'] = $matchedActors ?: ($candidate->matched_actor_names ?? null);

        $candidate->fill($attributes);
        $candidate->save();

        if ($created) {
            $this->transition($candidate, CandidateStatus::NORMALIZED);
            $this->transition($candidate, CandidateStatus::ENRICHED);
            $this->transition($candidate, CandidateStatus::DEDUPLICATED);
        }

        if ($rule !== null) {
            $this->attachRuleMatch($candidate, $rule, $runId, $matchedTerms);
        }

        return ['candidate' => $candidate, 'created' => $created];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(NormalizedCandidate $normalized, int $publicationId): array
    {
        return [
            'publication_id' => $publicationId,
            'provider' => $normalized->provider,
            'provider_video_id' => $normalized->providerVideoId,
            'provider_url' => $normalized->providerUrl,
            'provider_creator_id' => $normalized->providerCreatorId,
            'creator_name' => $normalized->creatorName,
            'creator_url' => $normalized->creatorUrl,
            'original_title' => $normalized->originalTitle,
            'original_description' => $normalized->originalDescription,
            'published_at' => $normalized->publishedAt ?: null,
            'duration_seconds' => $normalized->durationSeconds,
            'language' => $normalized->language,
            'height' => $normalized->height,
            'quality_label' => Quality::labelForHeight($normalized->height),
            'quality_verified' => $normalized->qualityVerified,
            'view_count' => $normalized->viewCount,
            'like_count' => $normalized->likeCount,
            'comment_count' => $normalized->commentCount,
            'embeddable' => $normalized->embeddable,
        ];
    }

    /**
     * A video matched by several rules keeps one candidate row and gains one
     * match row per rule.
     */
    public function attachRuleMatch(
        MediaCandidate $candidate,
        MediaDiscoveryRule $rule,
        ?int $runId = null,
        array $matchedTerms = []
    ): void {
        MediaCandidateRuleMatch::updateOrCreate(
            ['candidate_id' => $candidate->id, 'rule_id' => $rule->id],
            [
                'run_id' => $runId,
                'matched_at' => now(),
                'matched_terms' => $matchedTerms,
                'match_metadata_json' => [
                    'rule_name' => $rule->name,
                    'providers' => $rule->providerSlugs(),
                ],
            ]
        );
    }

    /**
     * Move a candidate through the state machine. Invalid transitions are
     * refused rather than silently applied.
     */
    public function transition(MediaCandidate $candidate, string $status, array $extra = []): bool
    {
        if (! $candidate->canTransitionTo($status)) {
            return false;
        }

        $candidate->fill(array_merge($extra, ['status' => $status]));
        $candidate->save();

        return true;
    }

    public function markEmbedUnavailable(MediaCandidate $candidate): void
    {
        $this->transition($candidate, CandidateStatus::EMBED_UNAVAILABLE, [
            'embeddable' => false,
            'error_message' => 'The provider reports that embedding is disabled for this video.',
        ]);
    }

    public function markProviderError(MediaCandidate $candidate, string $message): void
    {
        $this->transition($candidate, CandidateStatus::PROVIDER_ERROR, [
            'error_message' => mb_substr($message, 0, 1000),
            'failed_attempts' => (int) $candidate->failed_attempts + 1,
        ]);
    }

    public function markAnalysisError(MediaCandidate $candidate, string $message): void
    {
        $this->transition($candidate, CandidateStatus::ANALYSIS_ERROR, [
            'error_message' => mb_substr($message, 0, 1000),
            'failed_attempts' => (int) $candidate->failed_attempts + 1,
        ]);
    }

    /**
     * Editorial decisions are always recorded, whoever made them, so the audit
     * trail covers automated approvals as well as human ones.
     */
    public function recordDecision(
        MediaCandidate $candidate,
        string $decision,
        string $previousStatus,
        string $newStatus,
        ?int $editorId = null,
        ?string $rejectionReason = null,
        ?string $notes = null
    ): MediaEditorialDecision {
        return MediaEditorialDecision::create([
            'candidate_id' => $candidate->id,
            'editor_id' => $editorId,
            'decision' => $decision,
            'rejection_reason' => $rejectionReason,
            'notes' => $notes,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardCounters(int $publicationId): array
    {
        $rows = MediaCandidate::query()
            ->where('publication_id', $publicationId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $ready = (int) ($rows[CandidateStatus::READY_FOR_REVIEW] ?? 0);

        return [
            'new' => $ready,
            'priority' => (int) MediaCandidate::query()
                ->where('publication_id', $publicationId)
                ->where('status', CandidateStatus::READY_FOR_REVIEW)
                ->where('editorial_score', '>=', 90)
                ->count(),
            'recommended' => (int) MediaCandidate::query()
                ->where('publication_id', $publicationId)
                ->where('status', CandidateStatus::READY_FOR_REVIEW)
                ->whereBetween('editorial_score', [75, 89])
                ->count(),
            'reviewed_today' => (int) MediaEditorialDecision::query()
                ->whereDate('created_at', today())
                ->count(),
            'scheduled' => (int) ($rows[CandidateStatus::SCHEDULED] ?? 0),
            'published' => (int) ($rows[CandidateStatus::PUBLISHED] ?? 0),
            'rejected' => (int) ($rows[CandidateStatus::REJECTED] ?? 0),
            'errors' => (int) ($rows[CandidateStatus::PROVIDER_ERROR] ?? 0)
                + (int) ($rows[CandidateStatus::ANALYSIS_ERROR] ?? 0),
        ];
    }
}
