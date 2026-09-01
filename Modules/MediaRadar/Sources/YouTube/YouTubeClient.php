<?php

namespace Modules\MediaRadar\Sources\YouTube;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Thin HTTP wrapper around the YouTube Data API v3.
 *
 * The API key is read from config (env) and never leaves the server.
 */
class YouTubeClient
{
    public const SEARCH_COUNTER_PREFIX = 'media_radar:youtube:search_calls:';

    private int $requestCount = 0;

    public function __construct(private array $config)
    {
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['enabled']) && ! empty($this->config['api_key']);
    }

    public function requestCount(): int
    {
        return $this->requestCount;
    }

    /**
     * search.list is billed against a small daily bucket, so every call is
     * counted and refused once the configured cap is reached.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function search(array $params): array
    {
        $cap = (int) ($this->config['daily_search_cap'] ?? 0);

        if ($cap > 0 && $this->searchCallsToday() >= $cap) {
            throw new ProviderException(
                'YouTube daily search cap reached ('.$cap.' calls). Discovery skipped to protect the quota.',
                'youtube',
                429,
                false,
            );
        }

        $response = $this->get('search', array_merge([
            'part' => 'snippet',
            'type' => 'video',
            'videoEmbeddable' => 'true',
            'maxResults' => $this->config['max_results'] ?? 25,
        ], $params));

        $this->incrementSearchCalls();

        return $response;
    }

    /**
     * videos.list is cheap, so ids are batched 50 at a time.
     *
     * @param  list<string>  $ids
     * @return list<array<string, mixed>>
     */
    public function videos(array $ids, string $parts = 'snippet,contentDetails,statistics,status'): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        $items = [];

        foreach (array_chunk($ids, 50) as $chunk) {
            $response = $this->get('videos', [
                'part' => $parts,
                'id' => implode(',', $chunk),
                'maxResults' => 50,
            ]);

            foreach ($response['items'] ?? [] as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function channel(string $channelId): array
    {
        $response = $this->get('channels', [
            'part' => 'snippet,contentDetails,statistics',
            'id' => $channelId,
        ]);

        return $response['items'][0] ?? [];
    }

    /**
     * Recent uploads for a channel via its uploads playlist. This costs a
     * fraction of a search call, which is why trusted creators are watched
     * this way instead of through keyword search.
     *
     * @return list<string> video ids
     */
    public function recentUploadIds(string $channelId, int $limit = 10): array
    {
        $channel = $this->channel($channelId);
        $playlistId = $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null;

        if (empty($playlistId)) {
            return [];
        }

        $response = $this->get('playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => min(max($limit, 1), 50),
        ]);

        $ids = [];

        foreach ($response['items'] ?? [] as $item) {
            if (! empty($item['contentDetails']['videoId'])) {
                $ids[] = $item['contentDetails']['videoId'];
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $params): array
    {
        $params['key'] = $this->config['api_key'];

        $this->requestCount++;

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 20))
                ->acceptJson()
                ->get(rtrim($this->config['base_url'], '/').'/'.$endpoint, $params);
        } catch (ConnectionException $e) {
            throw new ProviderException('YouTube connection failed: '.$e->getMessage(), 'youtube', null, true);
        }

        if ($response->failed()) {
            throw ProviderException::fromStatus('youtube', $response->status(), $response->body());
        }

        return (array) $response->json();
    }

    public function searchCallsToday(): int
    {
        return (int) Cache::get(self::SEARCH_COUNTER_PREFIX.$this->today(), 0);
    }

    private function incrementSearchCalls(): void
    {
        $key = self::SEARCH_COUNTER_PREFIX.$this->today();
        $current = (int) Cache::get($key, 0);

        // Pacific-day rollover with a generous TTL; the key is rebuilt daily.
        Cache::put($key, $current + 1, now()->addDay());
    }

    private function today(): string
    {
        return date('Y-m-d');
    }
}
