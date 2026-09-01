<?php

namespace Modules\MediaRadar\Support;

use Modules\MediaRadar\Models\MediaDiscoveryRule;

/**
 * The filtering half of a discovery rule, flattened into plain PHP.
 *
 * Providers apply what their APIs support server side; everything else is
 * enforced here after normalization, which also keeps the rules unit testable
 * without a database.
 */
final class RuleCriteria
{
    public function __construct(
        /** @var list<string> */
        public array $excludedTerms = [],
        /** @var list<string> */
        public array $keywords = [],
        /** @var list<string> */
        public array $actors = [],
        public ?int $minDurationSeconds = null,
        public ?int $maxDurationSeconds = null,
        public ?int $releaseYearFrom = null,
        public ?int $releaseYearTo = null,
        public ?string $minQuality = null,
        public bool $qualityStrict = false,
        public ?string $language = null,
        public ?int $publishedWithinDays = null,
        public ?int $minViewCount = null,
        /** @var list<string> */
        public array $blockedCreatorIds = [],
        /** @var list<string> */
        public array $preferredCreatorIds = [],
        public bool $requireEmbeddable = true,
    ) {
    }

    public static function fromRule(MediaDiscoveryRule $rule): self
    {
        return new self(
            excludedTerms: array_map('strtolower', $rule->arrayValue('excluded_terms')),
            keywords: array_map('strtolower', $rule->arrayValue('keywords')),
            actors: $rule->arrayValue('actors'),
            minDurationSeconds: $rule->min_duration_seconds !== null ? (int) $rule->min_duration_seconds : null,
            maxDurationSeconds: $rule->max_duration_seconds !== null ? (int) $rule->max_duration_seconds : null,
            releaseYearFrom: $rule->release_year_from !== null ? (int) $rule->release_year_from : null,
            releaseYearTo: $rule->release_year_to !== null ? (int) $rule->release_year_to : null,
            minQuality: $rule->min_quality,
            qualityStrict: (bool) $rule->quality_strict,
            language: $rule->language,
            publishedWithinDays: $rule->published_within_days !== null ? (int) $rule->published_within_days : null,
            minViewCount: $rule->min_view_count !== null ? (int) $rule->min_view_count : null,
            blockedCreatorIds: $rule->arrayValue('blocked_creator_ids'),
            preferredCreatorIds: $rule->arrayValue('preferred_creator_ids'),
        );
    }
}
