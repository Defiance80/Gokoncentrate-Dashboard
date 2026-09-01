<?php

namespace Modules\MediaRadar\Support;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Decides whether a normalized candidate satisfies a rule.
 *
 * Returns a reason when it does not, so discovery runs can explain why results
 * were dropped instead of silently losing them.
 */
final class CandidateFilter
{
    public const PASSED = null;

    public function reject(NormalizedCandidate $candidate, RuleCriteria $criteria): ?string
    {
        if ($criteria->requireEmbeddable && ! $candidate->embeddable) {
            return 'embedding_disabled';
        }

        if ($this->isBlockedCreator($candidate, $criteria)) {
            return 'blocked_creator';
        }

        if ($reason = $this->rejectByDuration($candidate, $criteria)) {
            return $reason;
        }

        if ($reason = $this->rejectByDate($candidate, $criteria)) {
            return $reason;
        }

        if (! Quality::satisfies($candidate->height, $criteria->minQuality, $criteria->qualityStrict)) {
            return 'below_minimum_quality';
        }

        if ($criteria->minViewCount !== null
            && $candidate->viewCount !== null
            && $candidate->viewCount < $criteria->minViewCount) {
            return 'below_minimum_views';
        }

        if ($this->matchesExcludedTerm($candidate, $criteria)) {
            return 'excluded_term';
        }

        if ($reason = $this->rejectByLanguage($candidate, $criteria)) {
            return $reason;
        }

        return self::PASSED;
    }

    public function passes(NormalizedCandidate $candidate, RuleCriteria $criteria): bool
    {
        return $this->reject($candidate, $criteria) === self::PASSED;
    }

    /**
     * Terms from the rule that actually appear in the candidate. Recorded on
     * the rule match so an editor can see why a video surfaced.
     *
     * @return list<string>
     */
    public function matchedTerms(NormalizedCandidate $candidate, RuleCriteria $criteria): array
    {
        $haystack = $candidate->searchableText();
        $matched = [];

        foreach (array_merge($criteria->keywords, array_map('strtolower', $criteria->actors)) as $term) {
            if ($term !== '' && str_contains($haystack, $term)) {
                $matched[] = $term;
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * Actor names from the rule that appear in the candidate's text.
     *
     * @return list<string>
     */
    public function matchedActors(NormalizedCandidate $candidate, RuleCriteria $criteria): array
    {
        $haystack = $candidate->searchableText();
        $matched = [];

        foreach ($criteria->actors as $actor) {
            if ($actor !== '' && str_contains($haystack, strtolower($actor))) {
                $matched[] = $actor;
            }
        }

        return array_values(array_unique($matched));
    }

    private function isBlockedCreator(NormalizedCandidate $candidate, RuleCriteria $criteria): bool
    {
        if ($candidate->providerCreatorId === null || $criteria->blockedCreatorIds === []) {
            return false;
        }

        return in_array($candidate->providerCreatorId, $criteria->blockedCreatorIds, true);
    }

    private function rejectByDuration(NormalizedCandidate $candidate, RuleCriteria $criteria): ?string
    {
        if ($candidate->durationSeconds === null) {
            return null;
        }

        if ($criteria->minDurationSeconds !== null && $candidate->durationSeconds < $criteria->minDurationSeconds) {
            return 'too_short';
        }

        if ($criteria->maxDurationSeconds !== null && $candidate->durationSeconds > $criteria->maxDurationSeconds) {
            return 'too_long';
        }

        return null;
    }

    private function rejectByDate(NormalizedCandidate $candidate, RuleCriteria $criteria): ?string
    {
        if (empty($candidate->publishedAt)) {
            return null;
        }

        $timestamp = strtotime($candidate->publishedAt);

        if ($timestamp === false) {
            return null;
        }

        if ($criteria->publishedWithinDays !== null) {
            $cutoff = time() - ($criteria->publishedWithinDays * 86400);

            if ($timestamp < $cutoff) {
                return 'published_too_long_ago';
            }
        }

        $year = (int) date('Y', $timestamp);

        if ($criteria->releaseYearFrom !== null && $year < $criteria->releaseYearFrom) {
            return 'released_before_window';
        }

        if ($criteria->releaseYearTo !== null && $year > $criteria->releaseYearTo) {
            return 'released_after_window';
        }

        return null;
    }

    private function matchesExcludedTerm(NormalizedCandidate $candidate, RuleCriteria $criteria): bool
    {
        if ($criteria->excludedTerms === []) {
            return false;
        }

        $haystack = $candidate->searchableText();

        foreach ($criteria->excludedTerms as $term) {
            if ($term !== '' && str_contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    private function rejectByLanguage(NormalizedCandidate $candidate, RuleCriteria $criteria): ?string
    {
        if (empty($criteria->language) || empty($candidate->language)) {
            return null;
        }

        // Providers report "en", "en-US" or "english" depending on the source.
        $required = strtolower(substr($criteria->language, 0, 2));
        $actual = strtolower(substr($candidate->language, 0, 2));

        return $required === $actual ? null : 'wrong_language';
    }
}
