<?php

namespace Modules\MediaRadar\Sources\Vimeo;

use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\Contracts\MediaProviderInterface;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

class VimeoProvider implements MediaProviderInterface
{
    private const MAX_QUERIES_PER_RUN = 3;

    public function __construct(
        private VimeoClient $client,
        private VimeoNormalizer $normalizer,
    ) {
    }

    public function slug(): string
    {
        return 'vimeo';
    }

    public function label(): string
    {
        return 'Vimeo';
    }

    public function uploadTypeConstant(): string
    {
        return 'Vimeo';
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
        return 'https://vimeo.com/'.$providerVideoId;
    }

    /**
     * Vimeo's search endpoint takes a single free-text query, so each term
     * group is issued separately and merged here.
     *
     * @return list<NormalizedCandidate>
     */
    public function search(MediaDiscoveryRule $rule): array
    {
        $candidates = [];

        foreach ($this->buildQueries($rule) as $query) {
            foreach ($this->client->searchVideos($this->searchParameters($rule, $query)) as $item) {
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

    /**
     * @return array<string, mixed>
     */
    public function searchParameters(MediaDiscoveryRule $rule, string $query): array
    {
        $params = ['query' => $query];

        if ($rule->published_within_days) {
            $params['sort'] = 'date';
        }

        // Vimeo's search endpoint has no duration, resolution or date filters,
        // so those rule parameters are applied by the CandidateFilter after the
        // response is normalized.
        return $params;
    }
}
