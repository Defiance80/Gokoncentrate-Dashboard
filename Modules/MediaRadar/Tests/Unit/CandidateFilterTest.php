<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\CandidateFilter;
use Modules\MediaRadar\Support\RuleCriteria;
use PHPUnit\Framework\TestCase;

class CandidateFilterTest extends TestCase
{
    private function candidate(array $overrides = []): NormalizedCandidate
    {
        return new NormalizedCandidate(
            provider: $overrides['provider'] ?? 'youtube',
            providerVideoId: $overrides['providerVideoId'] ?? 'abc123',
            providerCreatorId: $overrides['providerCreatorId'] ?? 'UC1',
            creatorName: $overrides['creatorName'] ?? 'Example Network',
            originalTitle: $overrides['originalTitle'] ?? 'Black founders building AI infrastructure',
            originalDescription: $overrides['originalDescription'] ?? 'A conversation with technology founders.',
            publishedAt: $overrides['publishedAt'] ?? gmdate('c', time() - 86400),
            durationSeconds: $overrides['durationSeconds'] ?? 900,
            language: $overrides['language'] ?? 'en',
            // array_key_exists so a test can deliberately pass a null height.
            height: array_key_exists('height', $overrides) ? $overrides['height'] : 1080,
            viewCount: $overrides['viewCount'] ?? 5000,
            embeddable: $overrides['embeddable'] ?? true,
        );
    }

    public function test_a_matching_candidate_passes(): void
    {
        $this->assertTrue((new CandidateFilter())->passes($this->candidate(), new RuleCriteria()));
    }

    public function test_it_rejects_videos_that_cannot_be_embedded(): void
    {
        $filter = new CandidateFilter();

        $this->assertSame(
            'embedding_disabled',
            $filter->reject($this->candidate(['embeddable' => false]), new RuleCriteria())
        );
    }

    public function test_it_applies_the_length_window(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(minDurationSeconds: 240, maxDurationSeconds: 2700);

        $this->assertSame('too_short', $filter->reject($this->candidate(['durationSeconds' => 120]), $criteria));
        $this->assertSame('too_long', $filter->reject($this->candidate(['durationSeconds' => 5400]), $criteria));
        $this->assertTrue($filter->passes($this->candidate(['durationSeconds' => 900]), $criteria));
    }

    public function test_it_applies_the_release_year_window(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(releaseYearFrom: 2024, releaseYearTo: 2025);

        $this->assertSame(
            'released_before_window',
            $filter->reject($this->candidate(['publishedAt' => '2019-05-01T00:00:00Z']), $criteria)
        );
        $this->assertSame(
            'released_after_window',
            $filter->reject($this->candidate(['publishedAt' => '2026-05-01T00:00:00Z']), $criteria)
        );
        $this->assertTrue($filter->passes($this->candidate(['publishedAt' => '2024-06-01T00:00:00Z']), $criteria));
    }

    public function test_it_applies_the_published_within_window(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(publishedWithinDays: 14);

        $old = gmdate('c', time() - (40 * 86400));

        $this->assertSame('published_too_long_ago', $filter->reject($this->candidate(['publishedAt' => $old]), $criteria));
    }

    public function test_minimum_quality_is_enforced_when_the_platform_reports_a_resolution(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(minQuality: '1080p');

        $this->assertSame('below_minimum_quality', $filter->reject($this->candidate(['height' => 720]), $criteria));
        $this->assertTrue($filter->passes($this->candidate(['height' => 2160]), $criteria));
    }

    public function test_unknown_resolution_passes_unless_strict_mode_is_on(): void
    {
        $filter = new CandidateFilter();
        $candidate = $this->candidate(['height' => null]);

        $this->assertTrue($filter->passes($candidate, new RuleCriteria(minQuality: '4K')));
        $this->assertSame(
            'below_minimum_quality',
            $filter->reject($candidate, new RuleCriteria(minQuality: '4K', qualityStrict: true))
        );
    }

    public function test_excluded_terms_drop_a_candidate(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(excludedTerms: ['reaction', 'get rich quick']);

        $this->assertSame(
            'excluded_term',
            $filter->reject($this->candidate(['originalTitle' => 'My REACTION to the news']), $criteria)
        );
    }

    public function test_blocked_creators_are_dropped(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(blockedCreatorIds: ['UC1']);

        $this->assertSame('blocked_creator', $filter->reject($this->candidate(), $criteria));
    }

    public function test_minimum_views_are_enforced(): void
    {
        $filter = new CandidateFilter();

        $this->assertSame(
            'below_minimum_views',
            $filter->reject($this->candidate(['viewCount' => 100]), new RuleCriteria(minViewCount: 1000))
        );
    }

    public function test_language_is_compared_on_the_two_letter_prefix(): void
    {
        $filter = new CandidateFilter();

        $this->assertTrue($filter->passes($this->candidate(['language' => 'en-US']), new RuleCriteria(language: 'en')));
        $this->assertSame(
            'wrong_language',
            $filter->reject($this->candidate(['language' => 'fr']), new RuleCriteria(language: 'en'))
        );
    }

    public function test_it_reports_matched_keywords_and_actors(): void
    {
        $filter = new CandidateFilter();
        $criteria = new RuleCriteria(
            keywords: ['black founders', 'crypto'],
            actors: ['Example Network'],
        );

        $candidate = $this->candidate();

        $this->assertSame(['black founders', 'example network'], $filter->matchedTerms($candidate, $criteria));
        $this->assertSame(['Example Network'], $filter->matchedActors($candidate, $criteria));
    }
}
