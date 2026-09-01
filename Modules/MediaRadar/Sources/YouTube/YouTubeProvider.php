<?php

namespace Modules\MediaRadar\Sources\YouTube;

use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\Contracts\MediaProviderInterface;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\Duration;
use Modules\MediaRadar\Support\Quality;

class YouTubeProvider implements MediaProviderInterface
{
    /**
     * Search terms are OR-ed together and capped so one rule can never drain
     * the daily search bucket.
     */
    private const TERMS_PER_QUERY = 4;

    private const MAX_QUERIES_PER_RUN = 3;

    public function __construct(
        private YouTubeClient $client,
        private YouTubeNormalizer $normalizer,
    ) {
    }

    public function slug(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube';
    }

    public function uploadTypeConstant(): string
    {
        return 'YouTube';
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
        return 'https://www.youtube.com/watch?v='.$providerVideoId;
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function search(MediaDiscoveryRule $rule): array
    {
        $ids = [];

        foreach ($this->buildQueries($rule) as $query) {
            $response = $this->client->search($this->searchParameters($rule, $query));

            foreach ($response['items'] ?? [] as $item) {
                if (! empty($item['id']['videoId'])) {
                    $ids[] = $item['id']['videoId'];
                }
            }
        }

        return $this->enrich($ids);
    }

    public function getVideo(string $providerVideoId): ?NormalizedCandidate
    {
        return $this->enrich([$providerVideoId])[0] ?? null;
    }

    /**
     * @return list<NormalizedCandidate>
     */
    public function getRecentCreatorVideos(string $providerCreatorId, int $limit = 10): array
    {
        return $this->enrich($this->client->recentUploadIds($providerCreatorId, $limit));
    }

    public function canEmbed(NormalizedCandidate $candidate): bool
    {
        return $candidate->embeddable;
    }

    /**
     * Second call: turn ids into full metadata.
     *
     * @param  list<string>  $ids
     * @return list<NormalizedCandidate>
     */
    private function enrich(array $ids): array
    {
        $candidates = [];

        foreach ($this->client->videos($ids) as $item) {
            $candidate = $this->normalizer->normalize($item);

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * @return list<string>
     */
    public function buildQueries(MediaDiscoveryRule $rule): array
    {
        $terms = $rule->effectiveSearchTerms();
        $excluded = array_map(
            fn (string $term): string => '-'.$this->quote($term),
            array_slice($rule->arrayValue('excluded_terms'), 0, 5)
        );

        $queries = [];

        foreach (array_chunk($terms, self::TERMS_PER_QUERY) as $chunk) {
            $quoted = array_map(fn (string $term): string => $this->quote($term), $chunk);
            $queries[] = trim(implode('|', $quoted).' '.implode(' ', $excluded));
        }

        return array_slice(array_values(array_filter($queries)), 0, self::MAX_QUERIES_PER_RUN);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchParameters(MediaDiscoveryRule $rule, string $query): array
    {
        $params = [
            'q' => $query,
            'order' => $rule->published_within_days ? 'date' : 'relevance',
        ];

        if ($after = $rule->publishedAfter()) {
            $params['publishedAfter'] = $after->toIso8601ZuluString();
        }

        if ($before = $rule->publishedBefore()) {
            $params['publishedBefore'] = $before->toIso8601ZuluString();
        }

        $bucket = Duration::toYouTubeBucket($rule->min_duration_seconds, $rule->max_duration_seconds);

        if ($bucket !== null) {
            $params['videoDuration'] = $bucket;
        }

        $requiredHeight = Quality::heightFor($rule->min_quality);

        if ($requiredHeight !== null && $requiredHeight >= 720) {
            $params['videoDefinition'] = 'high';
        }

        if (! empty($rule->language)) {
            $params['relevanceLanguage'] = substr($rule->language, 0, 5);
        }

        if (! empty($rule->region)) {
            $params['regionCode'] = strtoupper(substr($rule->region, 0, 2));
        }

        return $params;
    }

    private function quote(string $term): string
    {
        $term = trim(str_replace(['"', '|'], ' ', $term));

        return str_contains($term, ' ') ? '"'.$term.'"' : $term;
    }
}
