<?php

namespace Modules\MediaRadar\Support;

/**
 * Candidate state machine.
 *
 * Pure PHP on purpose: it is unit tested without booting Laravel.
 */
final class CandidateStatus
{
    public const DISCOVERED = 'DISCOVERED';
    public const NORMALIZED = 'NORMALIZED';
    public const ENRICHED = 'ENRICHED';
    public const DEDUPLICATED = 'DEDUPLICATED';
    public const ANALYZING = 'ANALYZING';
    public const READY_FOR_REVIEW = 'READY_FOR_REVIEW';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';
    public const SCHEDULED = 'SCHEDULED';
    public const PUBLISHED = 'PUBLISHED';

    public const PROVIDER_ERROR = 'PROVIDER_ERROR';
    public const ANALYSIS_ERROR = 'ANALYSIS_ERROR';
    public const INVALID = 'INVALID';
    public const EMBED_UNAVAILABLE = 'EMBED_UNAVAILABLE';
    public const ARCHIVED = 'ARCHIVED';

    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        self::DISCOVERED => [self::NORMALIZED, self::INVALID, self::PROVIDER_ERROR, self::ARCHIVED],
        self::NORMALIZED => [self::ENRICHED, self::INVALID, self::EMBED_UNAVAILABLE, self::PROVIDER_ERROR, self::ARCHIVED],
        self::ENRICHED => [self::DEDUPLICATED, self::INVALID, self::EMBED_UNAVAILABLE, self::PROVIDER_ERROR, self::ARCHIVED],
        self::DEDUPLICATED => [self::ANALYZING, self::READY_FOR_REVIEW, self::ARCHIVED],
        self::ANALYZING => [self::READY_FOR_REVIEW, self::ANALYSIS_ERROR, self::ARCHIVED],
        self::READY_FOR_REVIEW => [self::APPROVED, self::REJECTED, self::ANALYZING, self::ARCHIVED],
        self::APPROVED => [self::SCHEDULED, self::PUBLISHED, self::REJECTED, self::ARCHIVED],
        self::SCHEDULED => [self::PUBLISHED, self::APPROVED, self::REJECTED, self::ARCHIVED],
        self::PUBLISHED => [self::ARCHIVED],
        self::REJECTED => [self::READY_FOR_REVIEW, self::ARCHIVED],
        self::PROVIDER_ERROR => [self::NORMALIZED, self::ENRICHED, self::ARCHIVED],
        self::ANALYSIS_ERROR => [self::ANALYZING, self::READY_FOR_REVIEW, self::ARCHIVED],
        self::EMBED_UNAVAILABLE => [self::ARCHIVED, self::ENRICHED],
        self::INVALID => [self::ARCHIVED],
        self::ARCHIVED => [self::READY_FOR_REVIEW],
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Statuses an editor is expected to act on.
     */
    public static function reviewable(): array
    {
        return [self::READY_FOR_REVIEW, self::ANALYSIS_ERROR];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, [self::PUBLISHED, self::INVALID, self::ARCHIVED], true);
    }

    public static function label(string $status): string
    {
        // DEDUPLICATED is a screening stage, not a "this is a duplicate" flag —
        // label it clearly so editors don't read queued items as duplicates.
        if ($status === self::DEDUPLICATED) {
            return 'Screened';
        }

        return ucwords(strtolower(str_replace('_', ' ', $status)));
    }
}
