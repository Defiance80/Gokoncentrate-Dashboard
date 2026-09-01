<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Sources\Vimeo\VimeoNormalizer;
use PHPUnit\Framework\TestCase;

class VimeoNormalizerTest extends TestCase
{
    private function item(array $overrides = []): array
    {
        return array_replace_recursive([
            'uri' => '/videos/123456789',
            'name' => 'Independent Filmmaker Spotlight',
            'description' => 'A short documentary.',
            'duration' => 742,
            'created_time' => '2026-07-01T09:00:00+00:00',
            'release_time' => '2026-07-02T09:00:00+00:00',
            'link' => 'https://vimeo.com/123456789',
            'language' => 'en',
            'width' => 1920,
            'height' => 1080,
            'privacy' => ['embed' => 'public'],
            'pictures' => [
                'sizes' => [
                    ['width' => 640, 'height' => 360, 'link' => 'https://i.vimeocdn.com/video/1-abc_640x360?r=pad'],
                    ['width' => 1280, 'height' => 720, 'link' => 'https://i.vimeocdn.com/video/1-abc_1280x720?r=pad'],
                ],
            ],
            'user' => ['uri' => '/users/987654', 'name' => 'Studio Example', 'link' => 'https://vimeo.com/studioexample'],
            'stats' => ['plays' => 4200],
            'metadata' => ['connections' => ['likes' => ['total' => 180], 'comments' => ['total' => 12]]],
            'tags' => [['tag' => 'documentary'], ['tag' => 'short']],
        ], $overrides);
    }

    public function test_it_normalizes_a_video_resource(): void
    {
        $candidate = (new VimeoNormalizer())->normalize($this->item());

        $this->assertNotNull($candidate);
        $this->assertSame('vimeo', $candidate->provider);
        $this->assertSame('123456789', $candidate->providerVideoId);
        $this->assertSame('987654', $candidate->providerCreatorId);
        $this->assertSame('Studio Example', $candidate->creatorName);
        $this->assertSame(742, $candidate->durationSeconds);
        $this->assertSame(4200, $candidate->viewCount);
        $this->assertSame(180, $candidate->likeCount);
        $this->assertSame(['documentary', 'short'], $candidate->tags);
    }

    public function test_vimeo_reports_a_real_resolution(): void
    {
        $candidate = (new VimeoNormalizer())->normalize($this->item());

        $this->assertSame(1080, $candidate->height);
        $this->assertTrue($candidate->qualityVerified);
    }

    public function test_it_uses_the_release_time_when_present(): void
    {
        $candidate = (new VimeoNormalizer())->normalize($this->item());

        $this->assertSame('2026-07-02T09:00:00+00:00', $candidate->publishedAt);
        $this->assertSame(2026, $candidate->publishedYear());
    }

    public function test_it_sorts_artwork_largest_first_and_strips_signatures(): void
    {
        $candidate = (new VimeoNormalizer())->normalize($this->item());

        $this->assertSame('https://i.vimeocdn.com/video/1-abc_1280x720', $candidate->thumbnailUrl);
        $this->assertSame(
            ['https://i.vimeocdn.com/video/1-abc_1280x720', 'https://i.vimeocdn.com/video/1-abc_640x360'],
            $candidate->thumbnailCandidates
        );
    }

    public function test_private_embed_is_not_embeddable(): void
    {
        $candidate = (new VimeoNormalizer())->normalize($this->item(['privacy' => ['embed' => 'private']]));

        $this->assertFalse($candidate->embeddable);
    }

    public function test_it_rejects_an_item_without_a_uri(): void
    {
        $this->assertNull((new VimeoNormalizer())->normalize(['name' => 'No uri']));
    }
}
