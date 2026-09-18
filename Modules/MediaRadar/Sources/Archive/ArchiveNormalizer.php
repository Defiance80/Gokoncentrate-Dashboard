<?php

namespace Modules\MediaRadar\Sources\Archive;

use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Turns an Internet Archive search doc (+ resolved MP4 URL) into a
 * NormalizedCandidate. providerUrl is the direct MP4 so the published record
 * streams through the standard external-URL player path.
 */
class ArchiveNormalizer
{
    public const PROVIDER = 'archive';

    /**
     * @param  array<string, mixed>  $doc
     */
    public function normalize(array $doc, string $mp4Url): ?NormalizedCandidate
    {
        $identifier = $doc['identifier'] ?? null;
        if (empty($identifier)) {
            return null;
        }

        $title = $doc['title'] ?? $identifier;
        if (is_array($title)) {
            $title = reset($title);
        }

        $description = $doc['description'] ?? null;
        if (is_array($description)) {
            $description = implode(' ', array_filter($description, 'is_string'));
        }

        $creator = $doc['creator'] ?? null;
        if (is_array($creator)) {
            $creator = reset($creator);
        }

        $year = $doc['year'] ?? null;
        $publishedAt = $year ? ((int) $year).'-01-01' : null;

        $thumb = 'https://archive.org/services/img/'.$identifier;

        return new NormalizedCandidate(
            provider: self::PROVIDER,
            providerVideoId: (string) $identifier,
            providerUrl: $mp4Url, // direct MP4 → plays via the external-URL path
            providerCreatorId: is_string($creator) ? \Illuminate\Support\Str::slug($creator) : null,
            creatorName: is_string($creator) ? $creator : null,
            creatorUrl: 'https://archive.org/details/'.$identifier,
            originalTitle: (string) $title,
            originalDescription: is_string($description) ? strip_tags($description) : null,
            publishedAt: $publishedAt,
            durationSeconds: $this->parseRuntime($doc['runtime'] ?? null),
            language: null,
            height: null,
            qualityVerified: false,
            viewCount: isset($doc['downloads']) ? (int) $doc['downloads'] : null,
            likeCount: null,
            commentCount: null,
            embeddable: true,
            thumbnailUrl: $thumb,
            thumbnailCandidates: [$thumb],
            tags: $this->extractTags($doc['subject'] ?? []),
        );
    }

    private function parseRuntime($runtime): ?int
    {
        if (empty($runtime)) {
            return null;
        }
        if (is_array($runtime)) {
            $runtime = reset($runtime);
        }
        // Formats seen: "1:23:45", "45:00", or plain seconds.
        if (is_numeric($runtime)) {
            return (int) $runtime;
        }
        $parts = array_reverse(explode(':', (string) $runtime));
        $seconds = 0;
        foreach ($parts as $i => $p) {
            $seconds += ((int) $p) * (60 ** $i);
        }
        return $seconds ?: null;
    }

    /**
     * @param  mixed  $subject
     * @return list<string>
     */
    private function extractTags($subject): array
    {
        if (is_string($subject)) {
            $subject = preg_split('/[;,]/', $subject);
        }
        if (! is_array($subject)) {
            return [];
        }
        return array_values(array_filter(array_map(fn ($s) => is_string($s) ? trim($s) : null, $subject)));
    }
}
