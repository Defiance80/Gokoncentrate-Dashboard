<?php

namespace Modules\MediaRadar\Support;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Deterministic editorial score, built from the model in the Media Radar
 * specification. It always produces a score, so a candidate is reviewable even
 * when the AI layer is disabled or unavailable.
 *
 *   Editorial relevance   0-30
 *   Source quality        0-15
 *   Production quality    0-15
 *   Recency               0-10
 *   Audience interest     0-10
 *   Originality           0-10
 *   Brand fit             0-10
 *                        ----
 *                         100
 */
final class EditorialScorer
{
    public const MAX_RELEVANCE = 30;
    public const MAX_SOURCE_QUALITY = 15;
    public const MAX_PRODUCTION_QUALITY = 15;
    public const MAX_RECENCY = 10;
    public const MAX_AUDIENCE = 10;
    public const MAX_ORIGINALITY = 10;
    public const MAX_BRAND_FIT = 10;

    /**
     * @param  list<string>  $matchedTerms
     * @return array<string, int>
     */
    public function score(
        NormalizedCandidate $candidate,
        RuleCriteria $criteria,
        array $matchedTerms = [],
        bool $trustedSource = false,
        ?float $sourceApprovalRate = null,
        ?int $nowTimestamp = null
    ): array {
        $breakdown = [
            'relevance_score' => $this->relevance($candidate, $criteria, $matchedTerms),
            'source_quality_score' => $this->sourceQuality($trustedSource, $sourceApprovalRate),
            'production_quality_score' => $this->productionQuality($candidate),
            'recency_score' => $this->recency($candidate, $nowTimestamp),
            'audience_interest_score' => $this->audienceInterest($candidate),
            'originality_score' => $this->originality($candidate),
            'brand_fit_score' => $this->brandFit($candidate, $criteria),
        ];

        $breakdown['editorial_score'] = min(100, array_sum($breakdown));

        return $breakdown;
    }

    /**
     * @param  list<string>  $matchedTerms
     */
    private function relevance(NormalizedCandidate $candidate, RuleCriteria $criteria, array $matchedTerms): int
    {
        $expected = count($criteria->keywords) + count($criteria->actors);

        if ($expected === 0) {
            // Nothing to match against: the provider's own relevance ranking
            // is the only signal, so award the mid point rather than zero.
            return (int) round(self::MAX_RELEVANCE * 0.6);
        }

        $ratio = min(1.0, count($matchedTerms) / max(1, min($expected, 4)));

        // A rule with no keyword hits still earns a floor, because the term was
        // matched by the provider's search before it ever reached this point.
        return (int) round(self::MAX_RELEVANCE * (0.4 + (0.6 * $ratio)));
    }

    private function sourceQuality(bool $trusted, ?float $approvalRate): int
    {
        if ($approvalRate !== null) {
            return (int) round(self::MAX_SOURCE_QUALITY * min(1.0, $approvalRate / 100));
        }

        return $trusted ? self::MAX_SOURCE_QUALITY : (int) round(self::MAX_SOURCE_QUALITY * 0.5);
    }

    private function productionQuality(NormalizedCandidate $candidate): int
    {
        $points = 0;

        $height = $candidate->height ?? 0;

        if ($height >= 2160) {
            $points += 9;
        } elseif ($height >= 1080) {
            $points += 8;
        } elseif ($height >= 720) {
            $points += 6;
        } elseif ($height > 0) {
            $points += 3;
        } else {
            $points += 4;
        }

        $duration = $candidate->durationSeconds ?? 0;

        if ($duration >= 480) {
            $points += 4;
        } elseif ($duration >= 180) {
            $points += 3;
        } elseif ($duration > 0) {
            $points += 1;
        }

        if (mb_strlen((string) $candidate->originalDescription) >= 200) {
            $points += 2;
        }

        return min(self::MAX_PRODUCTION_QUALITY, $points);
    }

    private function recency(NormalizedCandidate $candidate, ?int $nowTimestamp): int
    {
        if (empty($candidate->publishedAt)) {
            return (int) round(self::MAX_RECENCY * 0.4);
        }

        $published = strtotime($candidate->publishedAt);

        if ($published === false) {
            return (int) round(self::MAX_RECENCY * 0.4);
        }

        $ageDays = max(0, (int) floor((($nowTimestamp ?? time()) - $published) / 86400));

        return match (true) {
            $ageDays <= 2 => 10,
            $ageDays <= 7 => 9,
            $ageDays <= 30 => 7,
            $ageDays <= 90 => 5,
            $ageDays <= 365 => 3,
            $ageDays <= 1095 => 2,
            default => 1,
        };
    }

    private function audienceInterest(NormalizedCandidate $candidate): int
    {
        $views = $candidate->viewCount ?? 0;

        $base = match (true) {
            $views >= 1000000 => 8,
            $views >= 100000 => 7,
            $views >= 10000 => 5,
            $views >= 1000 => 3,
            $views > 0 => 2,
            default => 3,
        };

        // Engagement ratio rewards videos people actually respond to.
        if ($views > 0 && ($candidate->likeCount ?? 0) > 0) {
            $ratio = $candidate->likeCount / $views;

            if ($ratio >= 0.04) {
                $base += 2;
            } elseif ($ratio >= 0.02) {
                $base += 1;
            }
        }

        return min(self::MAX_AUDIENCE, $base);
    }

    private function originality(NormalizedCandidate $candidate): int
    {
        $title = mb_strtolower((string) $candidate->originalTitle);
        $points = self::MAX_ORIGINALITY;

        foreach (['reaction', 'compilation', 'shorts', 'clip', 're-upload', 'reupload', 'part 2 of'] as $marker) {
            if ($marker !== '' && str_contains($title, $marker)) {
                $points -= 3;
            }
        }

        if (preg_match('/[!?]{2,}|\b(you won\'t believe|shocking|gone wrong)\b/iu', $title)) {
            $points -= 3;
        }

        return max(0, min(self::MAX_ORIGINALITY, $points));
    }

    private function brandFit(NormalizedCandidate $candidate, RuleCriteria $criteria): int
    {
        $points = (int) round(self::MAX_BRAND_FIT * 0.6);

        if ($candidate->providerCreatorId !== null
            && in_array($candidate->providerCreatorId, $criteria->preferredCreatorIds, true)) {
            $points += 4;
        }

        if (($candidate->durationSeconds ?? 0) >= 240) {
            $points += 1;
        }

        if (mb_strlen((string) $candidate->originalTitle) < 12) {
            $points -= 2;
        }

        return max(0, min(self::MAX_BRAND_FIT, $points));
    }

    /**
     * 90-100 Priority, 75-89 Recommended, 60-74 Secondary, below that Low.
     */
    public static function band(int $score): string
    {
        return match (true) {
            $score >= 90 => 'priority',
            $score >= 75 => 'recommended',
            $score >= 60 => 'secondary',
            default => 'low',
        };
    }
}
