<?php

namespace Modules\MediaRadar\Sources\Dailymotion;

use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\Contracts\MediaProviderInterface;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

class DailymotionProvider implements MediaProviderInterface
{
    private const MAX_QUERIES_PER_RUN = 3;

    public function __construct(
        private DailymotionClient $client,
        private DailymotionNormalizer $normalizer,
    ) {
    }

    public function slug(): string
    {
        return 'dailymotion';
    }

    public function label(): string
    {
        return 'Dailymotion';
    }

    public function uploadTypeConstant(): string
    {
        // Played as an iframe via the existing "embedded" player path.
        return 'Embedded';
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
        return 'https://www.dailymotion.com/video/'.$providerVideoId;
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function search(MediaDiscoveryRule $rule): array
    {
        $candidates = [];

        foreach ($this->buildQueries($rule) as $query) {
            foreach ($this->client->searchVideos($query) as $item) {
                $candidate = $this->normalizer->normalize($item);
                if ($candidate !== null) {
                    $candidates[$candidate->dedupeKey()] = $candidate;
                }
            }
        }

        return array_values($candidates);
    }

    public function getVideo(string $providerVideoId): ?NormalizedCandidate
    {
        $item = $this->client->video($providerVideoId);

        return $item === null ? null : $this->normalizer->normalize($item);
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function getRecentCreatorVideos(string $providerCreatorId, int $limit = 10): array
    {
        $candidates = [];

        foreach ($this->client->userVideos($providerCreatorId, $limit) as $item) {
            $candidate = $this->normalizer->normalize($item);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    public function canEmbed(NormalizedCandidate $candidate): bool
    {
        return $candidate->embeddable;
    }

    /**
     * @return list<string>
     */
    public function buildQueries(MediaDiscoveryRule $rule): array
    {
        return array_slice($rule->effectiveSearchTerms(), 0, self::MAX_QUERIES_PER_RUN);
    }
}
