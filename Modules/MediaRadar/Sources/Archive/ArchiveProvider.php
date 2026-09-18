<?php

namespace Modules\MediaRadar\Sources\Archive;

use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\Contracts\MediaProviderInterface;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

class ArchiveProvider implements MediaProviderInterface
{
    private const MAX_QUERIES_PER_RUN = 3;

    public function __construct(
        private ArchiveClient $client,
        private ArchiveNormalizer $normalizer,
    ) {
    }

    public function slug(): string
    {
        return 'archive';
    }

    public function label(): string
    {
        return 'Internet Archive';
    }

    public function uploadTypeConstant(): string
    {
        // Direct MP4; stored/encrypted like other external URLs and played via
        // the standard external-URL path (decryptVideoUrl -> direct video).
        return 'URL';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function requestCount(): int
    {
        return $this->client->requestCount();
    }

    public function playbackUrl(string $providerVideoId): string
    {
        return 'https://archive.org/details/'.$providerVideoId;
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function search(MediaDiscoveryRule $rule): array
    {
        $candidates = [];

        foreach ($this->buildQueries($rule) as $query) {
            foreach ($this->client->searchVideos($query) as $doc) {
                $identifier = $doc['identifier'] ?? null;
                if (empty($identifier)) {
                    continue;
                }

                // Only keep items with a directly-playable MP4.
                $mp4 = $this->client->resolveMp4($identifier);
                if ($mp4 === null) {
                    continue;
                }

                $candidate = $this->normalizer->normalize($doc, $mp4);
                if ($candidate !== null) {
                    $candidates[$candidate->dedupeKey()] = $candidate;
                }
            }
        }

        return array_values($candidates);
    }

    public function getVideo(string $providerVideoId): ?NormalizedCandidate
    {
        $mp4 = $this->client->resolveMp4($providerVideoId);
        if ($mp4 === null) {
            return null;
        }

        $meta = $this->client->metadata($providerVideoId);
        $md = (array) ($meta['metadata'] ?? []);
        $doc = [
            'identifier' => $providerVideoId,
            'title' => $md['title'] ?? $providerVideoId,
            'description' => $md['description'] ?? null,
            'year' => $md['year'] ?? null,
            'creator' => $md['creator'] ?? null,
            'subject' => $md['subject'] ?? [],
        ];

        return $this->normalizer->normalize($doc, $mp4);
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function getRecentCreatorVideos(string $providerCreatorId, int $limit = 10): array
    {
        return [];
    }

    public function canEmbed(NormalizedCandidate $candidate): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    public function buildQueries(MediaDiscoveryRule $rule): array
    {
        return array_slice($rule->effectiveSearchTerms(), 0, self::MAX_QUERIES_PER_RUN);
    }
}
