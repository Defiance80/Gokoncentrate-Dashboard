<?php

namespace Modules\MediaRadar\Sources\YouTube;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\Duration;
use Modules\MediaRadar\Support\Quality;

/**
 * Turns a youtube/v3/videos item into a NormalizedCandidate.
 *
 * Framework free so it can be unit tested against recorded fixtures.
 */
class YouTubeNormalizer
{
    public const PROVIDER = 'youtube';

    /**
     * Thumbnail keys ordered from best to worst.
     */
    private const THUMBNAIL_PRIORITY = ['maxres', 'standard', 'high', 'medium', 'default'];

    /**
     * @param  array<string, mixed>  $item
     */
    public function normalize(array $item): ?NormalizedCandidate
    {
        $videoId = $item['id'] ?? null;

        if (is_array($videoId)) {
            $videoId = $videoId['videoId'] ?? null;
        }

        if (empty($videoId)) {
            return null;
        }

        $snippet = $item['snippet'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $status = $item['status'] ?? [];

        $height = Quality::heightForYouTubeDefinition($contentDetails['definition'] ?? null);

        return new NormalizedCandidate(
            provider: self::PROVIDER,
            providerVideoId: (string) $videoId,
            providerUrl: 'https://www.youtube.com/watch?v='.$videoId,
            providerCreatorId: $snippet['channelId'] ?? null,
            creatorName: $snippet['channelTitle'] ?? null,
            creatorUrl: isset($snippet['channelId']) ? 'https://www.youtube.com/channel/'.$snippet['channelId'] : null,
            originalTitle: $snippet['title'] ?? null,
            originalDescription: $snippet['description'] ?? null,
            publishedAt: $snippet['publishedAt'] ?? null,
            durationSeconds: Duration::fromIso8601($contentDetails['duration'] ?? null),
            language: $snippet['defaultAudioLanguage'] ?? ($snippet['defaultLanguage'] ?? null),
            height: $height,
            // "hd" is a floor, not an exact resolution.
            qualityVerified: false,
            viewCount: isset($statistics['viewCount']) ? (int) $statistics['viewCount'] : null,
            likeCount: isset($statistics['likeCount']) ? (int) $statistics['likeCount'] : null,
            commentCount: isset($statistics['commentCount']) ? (int) $statistics['commentCount'] : null,
            embeddable: $this->resolveEmbeddable($status),
            thumbnailUrl: $this->bestThumbnail($snippet['thumbnails'] ?? []),
            thumbnailCandidates: $this->thumbnailCandidates((string) $videoId, $snippet['thumbnails'] ?? []),
            tags: array_values(array_filter((array) ($snippet['tags'] ?? []))),
        );
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function resolveEmbeddable(array $status): bool
    {
        if (array_key_exists('embeddable', $status)) {
            return (bool) $status['embeddable'];
        }

        // Absent status part: assume embeddable and let enrichment correct it.
        return true;
    }

    /**
     * @param  array<string, mixed>  $thumbnails
     */
    public function bestThumbnail(array $thumbnails): ?string
    {
        foreach (self::THUMBNAIL_PRIORITY as $key) {
            if (! empty($thumbnails[$key]['url'])) {
                return $thumbnails[$key]['url'];
            }
        }

        return null;
    }

    /**
     * Ordered list of artwork URLs to try, best first. The i.ytimg.com fallbacks
     * cover videos whose snippet omits the larger sizes.
     *
     * @param  array<string, mixed>  $thumbnails
     * @return list<string>
     */
    public function thumbnailCandidates(string $videoId, array $thumbnails = []): array
    {
        $urls = [];

        foreach (self::THUMBNAIL_PRIORITY as $key) {
            if (! empty($thumbnails[$key]['url'])) {
                $urls[] = $thumbnails[$key]['url'];
            }
        }

        foreach (['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault'] as $name) {
            $urls[] = sprintf('https://i.ytimg.com/vi/%s/%s.jpg', $videoId, $name);
        }

        return array_values(array_unique($urls));
    }
}
