<?php

namespace Modules\MediaRadar\Sources\Vimeo;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Turns a Vimeo API video resource into a NormalizedCandidate.
 */
class VimeoNormalizer
{
    public const PROVIDER = 'vimeo';

    /**
     * @param  array<string, mixed>  $item
     */
    public function normalize(array $item): ?NormalizedCandidate
    {
        $videoId = $this->extractId($item['uri'] ?? null);

        if ($videoId === null) {
            return null;
        }

        $user = $item['user'] ?? [];
        $pictures = $this->pictureUrls($item['pictures'] ?? []);
        $height = isset($item['height']) ? (int) $item['height'] : null;

        return new NormalizedCandidate(
            provider: self::PROVIDER,
            providerVideoId: $videoId,
            providerUrl: $item['link'] ?? ('https://vimeo.com/'.$videoId),
            providerCreatorId: $this->extractId($user['uri'] ?? null),
            creatorName: $user['name'] ?? null,
            creatorUrl: $user['link'] ?? null,
            originalTitle: $item['name'] ?? null,
            originalDescription: $item['description'] ?? null,
            publishedAt: $item['release_time'] ?? ($item['created_time'] ?? null),
            durationSeconds: isset($item['duration']) ? (int) $item['duration'] : null,
            language: $item['language'] ?? null,
            height: $height,
            // Vimeo reports the real pixel height of the source.
            qualityVerified: $height !== null,
            viewCount: isset($item['stats']['plays']) ? (int) $item['stats']['plays'] : null,
            likeCount: isset($item['metadata']['connections']['likes']['total'])
                ? (int) $item['metadata']['connections']['likes']['total']
                : null,
            commentCount: isset($item['metadata']['connections']['comments']['total'])
                ? (int) $item['metadata']['connections']['comments']['total']
                : null,
            embeddable: $this->resolveEmbeddable($item),
            thumbnailUrl: $pictures[0] ?? null,
            thumbnailCandidates: $pictures,
            tags: $this->extractTags($item['tags'] ?? []),
        );
    }

    public function extractId(?string $uri): ?string
    {
        if (empty($uri)) {
            return null;
        }

        if (preg_match('#(\d+)$#', $uri, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Vimeo's picture sizes, largest first.
     *
     * @param  array<string, mixed>  $pictures
     * @return list<string>
     */
    public function pictureUrls(array $pictures): array
    {
        $sizes = $pictures['sizes'] ?? [];

        usort($sizes, static fn ($a, $b) => ((int) ($b['width'] ?? 0)) <=> ((int) ($a['width'] ?? 0)));

        $urls = [];

        foreach ($sizes as $size) {
            if (! empty($size['link'])) {
                // Strip the transient signature query so the link stays stable.
                $urls[] = strtok($size['link'], '?');
            }
        }

        if (empty($urls) && ! empty($pictures['base_link'])) {
            $urls[] = $pictures['base_link'];
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveEmbeddable(array $item): bool
    {
        $embed = $item['privacy']['embed'] ?? null;

        if ($embed === null) {
            return true;
        }

        return in_array($embed, ['public', 'whitelist'], true);
    }

    /**
     * @param  array<int, mixed>  $tags
     * @return list<string>
     */
    private function extractTags(array $tags): array
    {
        $result = [];

        foreach ($tags as $tag) {
            if (is_array($tag) && ! empty($tag['tag'])) {
                $result[] = (string) $tag['tag'];
            } elseif (is_string($tag) && $tag !== '') {
                $result[] = $tag;
            }
        }

        return array_values(array_unique($result));
    }
}
