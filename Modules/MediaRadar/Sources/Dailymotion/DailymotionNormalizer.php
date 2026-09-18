<?php

namespace Modules\MediaRadar\Sources\Dailymotion;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Turns a Dailymotion API video resource into a NormalizedCandidate.
 * providerUrl is the watch URL; the player's decryptVideoUrl helper maps it to
 * the Dailymotion embed iframe (the existing "embedded" player path).
 */
class DailymotionNormalizer
{
    public const PROVIDER = 'dailymotion';

    /**
     * @param  array<string, mixed>  $item
     */
    public function normalize(array $item): ?NormalizedCandidate
    {
        $id = $item['id'] ?? null;
        if (empty($id)) {
            return null;
        }

        $thumb = $item['thumbnail_720_url'] ?? ($item['thumbnail_480_url'] ?? null);

        return new NormalizedCandidate(
            provider: self::PROVIDER,
            providerVideoId: (string) $id,
            providerUrl: $item['url'] ?? ('https://www.dailymotion.com/video/'.$id),
            providerCreatorId: $item['owner.id'] ?? null,
            creatorName: $item['owner.screenname'] ?? null,
            creatorUrl: isset($item['owner.id']) ? 'https://www.dailymotion.com/'.$item['owner.screenname'] : null,
            originalTitle: $item['title'] ?? null,
            originalDescription: isset($item['description']) ? strip_tags((string) $item['description']) : null,
            publishedAt: isset($item['created_time']) ? date('c', (int) $item['created_time']) : null,
            durationSeconds: isset($item['duration']) ? (int) $item['duration'] : null,
            language: $item['language'] ?? null,
            height: null,
            qualityVerified: false,
            viewCount: isset($item['views_total']) ? (int) $item['views_total'] : null,
            likeCount: null,
            commentCount: null,
            embeddable: array_key_exists('embeddable', $item) ? (bool) $item['embeddable'] : true,
            thumbnailUrl: $thumb,
            thumbnailCandidates: array_values(array_filter([$item['thumbnail_720_url'] ?? null, $item['thumbnail_480_url'] ?? null])),
            tags: $this->extractTags($item['tags'] ?? []),
        );
    }

    /**
     * @param  mixed  $tags
     * @return list<string>
     */
    private function extractTags($tags): array
    {
        if (! is_array($tags)) {
            return [];
        }
        return array_values(array_filter(array_map(fn ($t) => is_string($t) ? $t : null, $tags)));
    }
}
