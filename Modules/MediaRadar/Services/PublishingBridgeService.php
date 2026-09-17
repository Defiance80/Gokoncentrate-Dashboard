<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CastCrew\Models\CastCrew;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use Modules\Entertainment\Models\EntertainmentTalentMapping;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\Duration;

/**
 * Turns an approved candidate into a native GoKoncentrate movie.
 *
 * Media Radar does not own a publishing system: it writes into the existing
 * entertainments model, with the existing genre and talent mappings, so the
 * dashboard, the API and the mobile app treat the result like any other movie.
 *
 * The video itself stays hosted by YouTube or Vimeo: only the provider
 * playback URL is stored, in the video_upload_type / video_url_input pair the
 * platform already uses for third-party sources.
 */
class PublishingBridgeService
{
    public function __construct(private CandidateService $candidates)
    {
    }

    public function publish(MediaCandidate $candidate, ?int $editorId = null): Entertainment
    {
        if ($candidate->published_entertainment_id) {
            $existing = Entertainment::find($candidate->published_entertainment_id);

            if ($existing !== null) {
                return $existing;
            }
        }

        $settings = MediaRadarSetting::getInstance();
        $rule = $candidate->rules()->first();

        $movie = DB::transaction(function () use ($candidate, $settings, $rule) {
            $movie = $this->createMovie($candidate, $settings, $rule);

            $this->attachGenres($candidate, $movie->id, $rule);
            $this->attachActors($candidate, $movie->id);

            return $movie;
        });

        $previousStatus = (string) $candidate->status;

        $this->candidates->transition($candidate, CandidateStatus::PUBLISHED, [
            'published_entertainment_id' => $movie->id,
            'published_at_local' => now(),
            'error_message' => null,
        ]);

        $this->candidates->recordDecision(
            $candidate,
            'published',
            $previousStatus,
            CandidateStatus::PUBLISHED,
            $editorId
        );

        // The dashboard caches movie listings aggressively; the existing
        // EntertainmentService does the same after a create.
        Cache::flush();

        return $movie;
    }

    private function createMovie(MediaCandidate $candidate, MediaRadarSetting $settings, ?MediaDiscoveryRule $rule): Entertainment
    {
        $payload = $this->moviePayload(
            $candidate,
            $settings,
            $rule,
            $this->uniqueSlug($candidate->displayTitle(), $candidate)
        );

        // Entertainment overrides __construct() without forwarding attributes,
        // so the model is filled explicitly rather than through ::create().
        $movie = new Entertainment();
        $movie->forceFill($payload);
        $movie->save();

        // tvshow-engine sections (VeeMag / Media Series / TV Show) need a season +
        // episode carrying the video, otherwise the detail page has nothing to play.
        if (($payload['type'] ?? 'movie') === 'tvshow') {
            $this->createSeasonEpisode($movie, $candidate, $payload);
        }

        return $movie;
    }

    /** Wrap an imported single video as Season 1 / Episode 1 so it is playable. */
    private function createSeasonEpisode(Entertainment $movie, MediaCandidate $candidate, array $payload): void
    {
        $season = new Season();
        $season->forceFill([
            'name' => 'Season 1',
            'slug' => Str::slug($movie->slug . '-season-1'),
            'season_index' => 1,
            'entertainment_id' => $movie->id,
            'poster_url' => $candidate->poster_url,
            'description' => $candidate->displayDescription(),
            'access' => $payload['movie_access'] ?? 'free',
            'plan_id' => $payload['plan_id'] ?? null,
            'status' => 1,
        ])->save();

        $episode = new Episode();
        $episode->forceFill([
            'name' => $payload['name'],
            'slug' => Str::slug($movie->slug . '-episode-1'),
            'entertainment_id' => $movie->id,
            'season_id' => $season->id,
            'episode_number' => 1,
            'poster_url' => $candidate->poster_url,
            'description' => $candidate->displayDescription(),
            'short_desc' => $candidate->editorial_summary,
            'access' => $payload['movie_access'] ?? 'free',
            'plan_id' => $payload['plan_id'] ?? null,
            'is_restricted' => $payload['is_restricted'] ?? 0,
            'video_upload_type' => $payload['video_upload_type'] ?? $this->uploadType($candidate->provider),
            'video_url_input' => $payload['video_url_input'] ?? $candidate->playbackUrl(),
            'enable_quality' => 0,
            'download_status' => 0,
            'enable_download_quality' => 0,
            'duration' => $payload['duration'] ?? null,
            'release_date' => $payload['release_date'] ?? null,
            'status' => 1,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function moviePayload(
        MediaCandidate $candidate,
        MediaRadarSetting $settings,
        ?MediaDiscoveryRule $rule = null,
        ?string $slug = null
    ): array {
        $title = $candidate->displayTitle();

        // Route to the section the editor chose. VeeMags / Media Series (Podcast)
        // / TV Shows use the tvshow engine (with an episode created on publish);
        // Short Films and Music use the movie engine (played directly).
        $section = $candidate->target_section ?? 'short_film';
        $isTvEngine = in_array($section, ['tvshow', 'veemag', 'podcast'], true);

        return [
            'name' => $title,
            'slug' => $slug ?? Str::slug(trim($title)),
            'type' => $isTvEngine ? 'tvshow' : 'movie',
            'is_veemag' => $section === 'veemag' ? 1 : 0,
            'is_podcast' => $section === 'podcast' ? 1 : 0,
            'description' => $candidate->displayDescription(),

            'poster_url' => $candidate->poster_url,
            'thumbnail_url' => $candidate->thumbnail_url,

            // Third-party media is embedded through the provider, never copied.
            'video_upload_type' => $this->uploadType($candidate->provider),
            'video_url_input' => $candidate->playbackUrl(),
            'enable_quality' => 0,
            'download_status' => 0,
            'enable_download_quality' => 0,

            'movie_access' => $rule->destination_movie_access ?? $settings->default_movie_access ?? 'free',
            'plan_id' => $rule->destination_plan_id ?? $settings->default_plan_id,
            'is_restricted' => (bool) ($rule->destination_is_restricted ?? $settings->default_is_restricted),
            'status' => $settings->default_publish_status ? 1 : 0,

            'language' => $candidate->language,
            'duration' => Duration::toHoursMinutes($candidate->duration_seconds),
            'release_date' => optional($candidate->published_at)->toDateString(),

            'created_by' => $candidate->approved_by ?? $candidate->created_by,
            'updated_by' => $candidate->approved_by ?? $candidate->updated_by,
        ];
    }

    public function uploadType(string $provider): string
    {
        // Matches the dashboard's upload_type constants.
        return strtolower($provider) === 'vimeo' ? 'Vimeo' : 'YouTube';
    }

    /**
     * entertainments.slug is used in public URLs, so a clash is resolved with
     * the provider video id rather than by overwriting an existing movie.
     */
    public function uniqueSlug(string $title, MediaCandidate $candidate): string
    {
        $base = Str::slug(trim($title)) ?: 'media-radar-'.$candidate->provider_video_id;
        $slug = $base;

        if (! $this->slugTaken($slug)) {
            return $slug;
        }

        $slug = $base.'-'.Str::slug((string) $candidate->provider_video_id);

        if (! $this->slugTaken($slug)) {
            return $slug;
        }

        return $slug.'-'.$candidate->id;
    }

    /**
     * Queried through the query builder rather than the model: Entertainment
     * overrides __construct() without calling parent::__construct(), so its
     * SoftDeletes scope never boots and withTrashed() is unavailable. Going
     * straight to the table also guarantees soft-deleted movies are counted, so
     * a slug is never reused if that constructor is ever repaired.
     */
    private function slugTaken(string $slug): bool
    {
        return DB::table('entertainments')->where('slug', $slug)->exists();
    }

    private function attachGenres(MediaCandidate $candidate, int $movieId, ?MediaDiscoveryRule $rule): void
    {
        $genreIds = array_filter(array_merge(
            [$candidate->genre_id ?: $rule?->genre_id],
            (array) ($candidate->secondary_genre_ids ?: [])
        ));

        foreach (array_unique($genreIds) as $genreId) {
            EntertainmentGenerMapping::create([
                'entertainment_id' => $movieId,
                'genre_id' => (int) $genreId,
            ]);
        }
    }

    /**
     * Links actors the rule asked for and that the platform already knows.
     * New cast records are never invented from a video title.
     */
    private function attachActors(MediaCandidate $candidate, int $movieId): void
    {
        $names = array_filter((array) ($candidate->matched_actor_names ?: []));

        if ($names === []) {
            return;
        }

        $talentIds = CastCrew::query()
            ->where('type', 'actor')
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $name))]);
                }
            })
            ->pluck('id');

        foreach ($talentIds as $talentId) {
            EntertainmentTalentMapping::create([
                'entertainment_id' => $movieId,
                'talent_id' => (int) $talentId,
            ]);
        }
    }
}
