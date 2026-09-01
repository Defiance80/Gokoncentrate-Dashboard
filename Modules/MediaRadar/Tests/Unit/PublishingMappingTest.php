<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\CoverArtService;
use Modules\MediaRadar\Services\DeduplicationService;
use Modules\MediaRadar\Services\PublishingBridgeService;
use PHPUnit\Framework\TestCase;

/**
 * The mapping from an approved candidate onto the existing movie model.
 *
 * The published record must keep the video hosted by the provider: only the
 * playback URL and the upload-type constant are stored.
 */
class PublishingMappingTest extends TestCase
{
    private function bridge(): PublishingBridgeService
    {
        return new PublishingBridgeService(
            new CandidateService(new DeduplicationService(), new CoverArtService())
        );
    }

    private function candidate(array $overrides = []): MediaCandidate
    {
        $candidate = new MediaCandidate();

        $candidate->forceFill(array_merge([
            'id' => 10,
            'publication_id' => 1,
            'provider' => 'youtube',
            'provider_video_id' => 'dQw4w9WgXcQ',
            'provider_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'original_title' => 'Original provider title',
            'original_description' => 'Original provider description',
            'editorial_title' => 'The Lost Black Settlements of Southern California',
            'editorial_description' => 'An editorial description written for GoKoncentrate.',
            'editorial_summary' => 'Short summary.',
            'poster_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg',
            'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg',
            'duration_seconds' => 1122,
            'language' => 'en',
            'genre_id' => 4,
        ], $overrides));

        return $candidate;
    }

    private function settings(array $overrides = []): MediaRadarSetting
    {
        $settings = new MediaRadarSetting();

        $settings->forceFill(array_merge([
            'publication_id' => 1,
            'default_movie_access' => 'free',
            'default_plan_id' => null,
            'default_is_restricted' => false,
            'default_publish_status' => true,
        ], $overrides));

        return $settings;
    }

    public function test_it_maps_a_candidate_onto_the_movie_fields(): void
    {
        $payload = $this->bridge()->moviePayload($this->candidate(), $this->settings(), null, 'the-lost-black-settlements');

        $this->assertSame('The Lost Black Settlements of Southern California', $payload['name']);
        $this->assertSame('the-lost-black-settlements', $payload['slug']);
        $this->assertSame('movie', $payload['type']);
        $this->assertSame('An editorial description written for GoKoncentrate.', $payload['description']);
        $this->assertSame('en', $payload['language']);
        $this->assertSame('00:19', $payload['duration']);
    }

    public function test_the_video_stays_hosted_by_the_platform(): void
    {
        $payload = $this->bridge()->moviePayload($this->candidate(), $this->settings(), null, 'slug');

        $this->assertSame('YouTube', $payload['video_upload_type']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $payload['video_url_input']);
        $this->assertSame(0, $payload['download_status']);
        $this->assertSame(0, $payload['enable_download_quality']);
    }

    public function test_vimeo_candidates_use_the_vimeo_upload_type(): void
    {
        $candidate = $this->candidate([
            'provider' => 'vimeo',
            'provider_video_id' => '123456789',
            'provider_url' => 'https://vimeo.com/123456789',
        ]);

        $payload = $this->bridge()->moviePayload($candidate, $this->settings(), null, 'slug');

        $this->assertSame('Vimeo', $payload['video_upload_type']);
        $this->assertSame('https://vimeo.com/123456789', $payload['video_url_input']);
    }

    public function test_cover_art_links_are_carried_over_untouched(): void
    {
        $payload = $this->bridge()->moviePayload($this->candidate(), $this->settings(), null, 'slug');

        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', $payload['poster_url']);
        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', $payload['thumbnail_url']);
    }

    public function test_settings_control_whether_the_published_movie_goes_live(): void
    {
        $live = $this->bridge()->moviePayload($this->candidate(), $this->settings(), null, 'slug');
        $draft = $this->bridge()->moviePayload($this->candidate(), $this->settings(['default_publish_status' => false]), null, 'slug');

        $this->assertSame(1, $live['status']);
        $this->assertSame(0, $draft['status']);
    }

    public function test_it_falls_back_to_the_original_title_when_nothing_was_edited(): void
    {
        $candidate = $this->candidate(['editorial_title' => null, 'editorial_description' => null]);

        $payload = $this->bridge()->moviePayload($candidate, $this->settings(), null, 'slug');

        $this->assertSame('Original provider title', $payload['name']);
        $this->assertSame('Original provider description', $payload['description']);
    }

    public function test_upload_type_is_derived_from_the_provider(): void
    {
        $bridge = $this->bridge();

        $this->assertSame('YouTube', $bridge->uploadType('youtube'));
        $this->assertSame('Vimeo', $bridge->uploadType('vimeo'));
        $this->assertSame('Vimeo', $bridge->uploadType('VIMEO'));
    }
}
