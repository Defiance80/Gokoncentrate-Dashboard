<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Support\Duration;
use Modules\MediaRadar\Support\Quality;
use PHPUnit\Framework\TestCase;

class DurationAndQualityTest extends TestCase
{
    public function test_it_parses_iso_8601_durations(): void
    {
        $this->assertSame(1122, Duration::fromIso8601('PT18M42S'));
        $this->assertSame(3600, Duration::fromIso8601('PT1H'));
        $this->assertSame(90061, Duration::fromIso8601('P1DT1H1M1S'));
        $this->assertNull(Duration::fromIso8601(null));
        $this->assertNull(Duration::fromIso8601('not a duration'));
    }

    public function test_it_formats_duration_the_way_the_movie_model_stores_it(): void
    {
        $this->assertSame('00:19', Duration::toHoursMinutes(1122));
        $this->assertSame('02:05', Duration::toHoursMinutes(7500));
        $this->assertNull(Duration::toHoursMinutes(null));
    }

    public function test_it_humanizes_duration_for_the_admin(): void
    {
        $this->assertSame('18:42', Duration::humanize(1122));
        $this->assertSame('2:05:00', Duration::humanize(7500));
        $this->assertSame('-', Duration::humanize(null));
    }

    public function test_it_maps_a_length_window_to_a_youtube_bucket(): void
    {
        $this->assertSame('short', Duration::toYouTubeBucket(null, 200));
        $this->assertSame('medium', Duration::toYouTubeBucket(300, 1000));
        $this->assertSame('long', Duration::toYouTubeBucket(1800, null));
        $this->assertNull(Duration::toYouTubeBucket(null, null));
        // A window spanning several buckets is filtered after normalization.
        $this->assertNull(Duration::toYouTubeBucket(100, 5000));
    }

    public function test_quality_labels_map_to_heights(): void
    {
        $this->assertSame(1080, Quality::heightFor('1080p'));
        $this->assertSame(2160, Quality::heightFor('4K'));
        $this->assertNull(Quality::heightFor(null));
        $this->assertNull(Quality::heightFor('unknown'));
    }

    public function test_heights_map_back_to_the_nearest_label(): void
    {
        $this->assertSame('1080p', Quality::labelForHeight(1080));
        $this->assertSame('720p', Quality::labelForHeight(900));
        $this->assertSame('4K', Quality::labelForHeight(2160));
        $this->assertSame('480p', Quality::labelForHeight(240));
        $this->assertNull(Quality::labelForHeight(null));
    }

    public function test_youtube_definition_is_treated_as_a_floor(): void
    {
        $this->assertSame(720, Quality::heightForYouTubeDefinition('hd'));
        $this->assertSame(480, Quality::heightForYouTubeDefinition('sd'));
        $this->assertNull(Quality::heightForYouTubeDefinition(null));
    }

    public function test_satisfies_handles_missing_and_strict_cases(): void
    {
        $this->assertTrue(Quality::satisfies(1080, '720p'));
        $this->assertFalse(Quality::satisfies(480, '720p'));
        $this->assertTrue(Quality::satisfies(null, '720p'));
        $this->assertFalse(Quality::satisfies(null, '720p', true));
        $this->assertTrue(Quality::satisfies(null, null, true));
    }
}
