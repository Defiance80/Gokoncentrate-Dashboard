<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Services\DeduplicationService;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use PHPUnit\Framework\TestCase;

class DeduplicationTest extends TestCase
{
    private function candidate(string $provider, string $id): NormalizedCandidate
    {
        return new NormalizedCandidate(provider: $provider, providerVideoId: $id);
    }

    public function test_a_video_returned_by_several_queries_collapses_to_one_entry(): void
    {
        $unique = (new DeduplicationService())->unique([
            $this->candidate('youtube', 'dQw4w9WgXcQ'),
            $this->candidate('youtube', 'dQw4w9WgXcQ'),
            $this->candidate('youtube', 'other'),
        ]);

        $this->assertCount(2, $unique);
    }

    public function test_the_same_id_on_different_platforms_is_not_a_duplicate(): void
    {
        $unique = (new DeduplicationService())->unique([
            $this->candidate('youtube', '123456789'),
            $this->candidate('vimeo', '123456789'),
        ]);

        $this->assertCount(2, $unique);
    }

    public function test_the_dedupe_key_is_provider_scoped(): void
    {
        $this->assertSame('youtube:dQw4w9WgXcQ', $this->candidate('youtube', 'dQw4w9WgXcQ')->dedupeKey());
        $this->assertSame('vimeo:123456789', $this->candidate('vimeo', '123456789')->dedupeKey());
    }

    public function test_an_empty_response_produces_an_empty_list(): void
    {
        $this->assertSame([], (new DeduplicationService())->unique([]));
    }
}
