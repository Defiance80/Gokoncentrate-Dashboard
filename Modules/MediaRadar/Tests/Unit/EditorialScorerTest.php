<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\EditorialScorer;
use Modules\MediaRadar\Support\RuleCriteria;
use PHPUnit\Framework\TestCase;

class EditorialScorerTest extends TestCase
{
    private function candidate(array $overrides = []): NormalizedCandidate
    {
        return new NormalizedCandidate(
            provider: 'youtube',
            providerVideoId: 'abc123',
            providerCreatorId: $overrides['providerCreatorId'] ?? 'UC1',
            creatorName: 'Example Network',
            originalTitle: $overrides['originalTitle'] ?? 'Black founders building the next generation of AI infrastructure',
            originalDescription: $overrides['originalDescription'] ?? str_repeat('An in-depth discussion. ', 20),
            publishedAt: $overrides['publishedAt'] ?? gmdate('c', time() - 86400),
            durationSeconds: $overrides['durationSeconds'] ?? 1800,
            height: $overrides['height'] ?? 1080,
            viewCount: $overrides['viewCount'] ?? 250000,
            likeCount: $overrides['likeCount'] ?? 9000,
        );
    }

    public function test_the_score_never_exceeds_one_hundred(): void
    {
        $breakdown = (new EditorialScorer())->score(
            $this->candidate(),
            new RuleCriteria(keywords: ['black founders'], preferredCreatorIds: ['UC1']),
            ['black founders'],
            true,
            100.0
        );

        $this->assertLessThanOrEqual(100, $breakdown['editorial_score']);
        $this->assertGreaterThan(80, $breakdown['editorial_score']);
    }

    public function test_every_component_stays_within_its_ceiling(): void
    {
        $breakdown = (new EditorialScorer())->score($this->candidate(), new RuleCriteria());

        $this->assertLessThanOrEqual(EditorialScorer::MAX_RELEVANCE, $breakdown['relevance_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_SOURCE_QUALITY, $breakdown['source_quality_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_PRODUCTION_QUALITY, $breakdown['production_quality_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_RECENCY, $breakdown['recency_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_AUDIENCE, $breakdown['audience_interest_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_ORIGINALITY, $breakdown['originality_score']);
        $this->assertLessThanOrEqual(EditorialScorer::MAX_BRAND_FIT, $breakdown['brand_fit_score']);
    }

    public function test_a_trusted_creator_scores_higher_than_an_unknown_one(): void
    {
        $scorer = new EditorialScorer();
        $criteria = new RuleCriteria();

        $trusted = $scorer->score($this->candidate(), $criteria, [], true);
        $unknown = $scorer->score($this->candidate(), $criteria, [], false);

        $this->assertGreaterThan($unknown['editorial_score'], $trusted['editorial_score']);
    }

    public function test_recent_videos_score_higher_than_old_ones(): void
    {
        $scorer = new EditorialScorer();
        $criteria = new RuleCriteria();

        $fresh = $scorer->score($this->candidate(['publishedAt' => gmdate('c', time() - 3600)]), $criteria);
        $stale = $scorer->score($this->candidate(['publishedAt' => gmdate('c', time() - (700 * 86400))]), $criteria);

        $this->assertGreaterThan($stale['recency_score'], $fresh['recency_score']);
    }

    public function test_clickbait_titles_lose_originality_points(): void
    {
        $scorer = new EditorialScorer();
        $criteria = new RuleCriteria();

        $plain = $scorer->score($this->candidate(), $criteria);
        $bait = $scorer->score($this->candidate(['originalTitle' => 'You won\'t believe this reaction!!!']), $criteria);

        $this->assertLessThan($plain['originality_score'], $bait['originality_score']);
    }

    public function test_matching_more_keywords_raises_relevance(): void
    {
        $scorer = new EditorialScorer();
        $criteria = new RuleCriteria(keywords: ['black founders', 'ai', 'infrastructure', 'startups']);

        $none = $scorer->score($this->candidate(), $criteria, []);
        $some = $scorer->score($this->candidate(), $criteria, ['black founders', 'ai', 'infrastructure', 'startups']);

        $this->assertGreaterThan($none['relevance_score'], $some['relevance_score']);
    }

    public function test_score_bands_follow_the_specification(): void
    {
        $this->assertSame('priority', EditorialScorer::band(94));
        $this->assertSame('recommended', EditorialScorer::band(81));
        $this->assertSame('secondary', EditorialScorer::band(64));
        $this->assertSame('low', EditorialScorer::band(41));
    }
}
