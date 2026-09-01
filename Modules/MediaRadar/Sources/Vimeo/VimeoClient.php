<?php

namespace Modules\MediaRadar\Sources\Vimeo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Thin HTTP wrapper around the Vimeo API.
 *
 * Vimeo asks integrators to cache responses and to use field filtering, so
 * every request asks for the narrow field set the module actually stores.
 */
class VimeoClient
{
    public const RATE_LIMIT_CACHE_KEY = 'media_radar:vimeo:rate_limit';

    public const FIELDS = 'uri,name,description,duration,created_time,release_time,link,language,width,height,'
        .'privacy.embed,pictures.sizes,pictures.base_link,user.uri,user.name,user.link,stats.plays,'
        .'metadata.connections.likes.total,metadata.connections.comments.total,tags.tag';

    private int $requestCount = 0;

    public function __construct(private array $config)
    {
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['enabled']) && ! empty($this->config['access_token']);
    }

    public function requestCount(): int
    {
        return $this->requestCount;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function searchVideos(array $params): array
    {
        $response = $this->get('/videos', array_merge([
            'per_page' => $this->config['max_results'] ?? 25,
            'sort' => 'relevant',
            'direction' => 'desc',
        ], $params));

        return (array) ($response['data'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function video(string $videoId): ?array
    {
        $response = $this->get('/videos/'.$videoId, []);

        return empty($response['uri']) ? null : $response;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function userVideos(string $userId, int $limit = 10): array
    {
        $response = $this->get('/users/'.$userId.'/videos', [
            'per_page' => min(max($limit, 1), 50),
            'sort' => 'date',
            'direction' => 'desc',
        ]);

        return (array) ($response['data'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        $params['fields'] = $params['fields'] ?? self::FIELDS;

        $this->requestCount++;

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 20))
                ->withToken($this->config['access_token'])
                ->withHeaders(['Accept' => 'application/vnd.vimeo.*+json;version=3.4'])
                ->get(rtrim($this->config['base_url'], '/').$path, $params);
        } catch (ConnectionException $e) {
            throw new ProviderException('Vimeo connection failed: '.$e->getMessage(), 'vimeo', null, true);
        }

        $this->rememberRateLimit($response->header('X-RateLimit-Limit'), $response->header('X-RateLimit-Remaining'), $response->header('X-RateLimit-Reset'));

        if ($response->status() === 429) {
            $reset = $response->header('X-RateLimit-Reset');
            $retryAfter = $reset ? max(0, strtotime($reset) - time()) : 900;

            throw new ProviderException(
                'Vimeo rate limit reached. Cooling down for '.$retryAfter.'s.',
                'vimeo',
                429,
                true,
                $retryAfter,
            );
        }

        if ($response->failed()) {
            throw ProviderException::fromStatus('vimeo', $response->status(), $response->body());
        }

        return (array) $response->json();
    }

    private function rememberRateLimit(?string $limit, ?string $remaining, ?string $reset): void
    {
        if ($limit === null && $remaining === null) {
            return;
        }

        Cache::put(self::RATE_LIMIT_CACHE_KEY, [
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => $reset,
            'observed_at' => now()->toDateTimeString(),
        ], now()->addDay());
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function lastRateLimit(): ?array
    {
        return Cache::get(self::RATE_LIMIT_CACHE_KEY);
    }
}
