<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Sources\YouTube\YouTubeNormalizer;
use PHPUnit\Framework\TestCase;

class YouTubeNormalizerTest extends TestCase
{
    private function item(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 'dQw4w9WgXcQ',
            'snippet' => [
                'publishedAt' => '2026-08-20T12:00:00Z',
                'channelId' => 'UC1234567890',
                'channelTitle' => 'Example History Network',
                'title' => 'The Lost Black Settlements of Southern California',
                'description' => 'A documentary about under-covered history.',
                'defaultAudioLanguage' => 'en-US',
                'tags' => ['history', 'california'],
                'thumbnails' => [
                    'high' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'],
                    'maxres' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'],
                ],
            ],
            'contentDetails' => ['duration' => 'PT18M42S', 'definition' => 'hd'],
            'statistics' => ['viewCount' => '120450', 'likeCount' => '5100', 'commentCount' => '310'],
            'status' => ['embeddable' => true],
        ], $overrides);
    }

    public function test_it_normalizes_a_video_item(): void
    {
        $candidate = (new YouTubeNormalizer())->normalize($this->item());

        $this->assertNotNull($candidate);
        $this->assertSame('youtube', $candidate->provider);
        $this->assertSame('dQw4w9WgXcQ', $candidate->providerVideoId);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $candidate->providerUrl);
        $this->assertSame('UC1234567890', $candidate->providerCreatorId);
        $this->assertSame('Example History Network', $candidate->creatorName);
        $this->assertSame(1122, $candidate->durationSeconds);
        $this->assertSame(120450, $candidate->viewCount);
        $this->assertTrue($candidate->embeddable);
        $this->assertSame(['history', 'california'], $candidate->tags);
    }

    public function test_hd_is_treated_as_a_floor_not_an_exact_resolution(): void
    {
        $candidate = (new YouTubeNormalizer())->normalize($this->item());

        $this->assertSame(720, $candidate->height);
        $this->assertFalse($candidate->qualityVerified);
    }

    public function test_it_prefers_the_largest_thumbnail(): void
    {
        $normalizer = new YouTubeNormalizer();
        $candidate = $normalizer->normalize($this->item());

        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', $candidate->thumbnailUrl);
        $this->assertContains('https://i.ytimg.com/vi/dQw4w9WgXcQ/sddefault.jpg', $candidate->thumbnailCandidates);
    }

    public function test_it_reads_the_embeddable_flag(): void
    {
        $candidate = (new YouTubeNormalizer())->normalize($this->item(['status' => ['embeddable' => false]]));

        $this->assertFalse($candidate->embeddable);
    }

    public function test_it_accepts_a_search_result_shape(): void
    {
        $candidate = (new YouTubeNormalizer())->normalize([
            'id' => ['videoId' => 'abc123'],
            'snippet' => ['title' => 'Something'],
        ]);

        $this->assertNotNull($candidate);
        $this->assertSame('abc123', $candidate->providerVideoId);
    }

    public function test_it_rejects_an_item_without_an_id(): void
    {
        $this->assertNull((new YouTubeNormalizer())->normalize(['snippet' => ['title' => 'No id']]));
    }

    public function test_dedupe_key_combines_provider_and_video_id(): void
    {
        $candidate = (new YouTubeNormalizer())->normalize($this->item());

        $this->assertSame('youtube:dQw4w9WgXcQ', $candidate->dedupeKey());
    }
}
