<?php

namespace Modules\MediaRadar\Sources\Dailymotion;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Thin HTTP wrapper around the public Dailymotion Data API (no key needed for
 * public search). Field filtering keeps responses to what the module stores.
 */
class DailymotionClient
{
    // NB: 'embeddable' is not a valid Data API field (returns HTTP 400).
    public const FIELDS = 'id,title,description,duration,created_time,views_total,thumbnail_720_url,'
        .'thumbnail_480_url,owner.screenname,owner.id,private,tags,url,language';

    private int $requestCount = 0;

    public function __construct(private array $config)
    {
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['enabled']);
    }

    public function requestCount(): int
    {
        return $this->requestCount;
    }

    private function base(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.dailymotion.com', '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchVideos(string $query): array
    {
        $response = $this->get('/videos', [
            'search' => $query,
            'fields' => self::FIELDS,
            'limit' => (int) ($this->config['max_results'] ?? 25),
            'sort' => 'relevance',
            'longer_than' => 2, // minutes — skip trivially short clips
        ]);

        return (array) ($response['list'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function video(string $videoId): ?array
    {
        $response = $this->get('/video/'.$videoId, ['fields' => self::FIELDS]);

        return empty($response['id']) ? null : $response;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function userVideos(string $userId, int $limit = 10): array
    {
        $response = $this->get('/user/'.$userId.'/videos', [
            'fields' => self::FIELDS,
            'limit' => min(max($limit, 1), 100),
            'sort' => 'recent',
        ]);

        return (array) ($response['list'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        $this->requestCount++;

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 20))
                ->withHeaders(['Accept' => 'application/json'])
                ->get($this->base().$path, $params);
        } catch (ConnectionException $e) {
            throw new ProviderException('Dailymotion connection failed: '.$e->getMessage(), 'dailymotion', null, true);
        }

        if ($response->status() === 429) {
            throw new ProviderException('Dailymotion rate limit reached.', 'dailymotion', 429, true, 300);
        }

        if ($response->failed()) {
            throw ProviderException::fromStatus('dailymotion', $response->status(), $response->body());
        }

        return (array) $response->json();
    }
}
