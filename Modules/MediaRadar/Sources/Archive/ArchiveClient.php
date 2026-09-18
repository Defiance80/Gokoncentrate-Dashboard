<?php

namespace Modules\MediaRadar\Sources\Archive;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\MediaRadar\Sources\Support\ProviderException;

/**
 * Thin HTTP wrapper around the Internet Archive (archive.org).
 *
 * No API key is required. Discovery uses advancedsearch.php (movies only);
 * a per-item metadata call resolves a directly-playable MP4 so the published
 * record streams through the standard external-URL player path.
 */
class ArchiveClient
{
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
        return rtrim($this->config['base_url'] ?? 'https://archive.org', '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchVideos(string $query, int $rows = 15): array
    {
        // Restrict to public-domain / freely-viewable movies.
        $q = '('.$query.') AND mediatype:(movies)';

        // advancedsearch expects fl[] as REPEATED keys, which http_build_query
        // can't produce — so the field list is appended to the URL by hand.
        $fields = ['identifier', 'title', 'description', 'year', 'downloads', 'creator', 'subject', 'runtime'];
        $fl = '';
        foreach ($fields as $f) {
            $fl .= '&fl[]='.$f;
        }
        $fl .= '&sort[]='.rawurlencode('downloads desc');

        $base = http_build_query([
            'q' => $q,
            'rows' => max(1, min($rows, (int) ($this->config['max_results'] ?? 15))),
            'page' => 1,
            'output' => 'json',
        ]);

        $response = $this->getUrl($this->base().'/advancedsearch.php?'.$base.$fl);

        return (array) ($response['response']['docs'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(string $identifier): ?array
    {
        $response = $this->get('/metadata/'.$identifier, []);

        return empty($response['files']) ? $response ?: null : $response;
    }

    /**
     * Resolve the best directly-playable MP4 URL for an item, or null.
     */
    public function resolveMp4(string $identifier): ?string
    {
        $meta = $this->metadata($identifier);
        if (empty($meta['files'])) {
            return null;
        }

        // Prefer h.264 MP4 derivatives (streamable), then any .mp4.
        $best = null;
        foreach ((array) $meta['files'] as $file) {
            $name = $file['name'] ?? '';
            $format = strtolower((string) ($file['format'] ?? ''));
            if ($name === '') {
                continue;
            }
            $isMp4 = str_ends_with(strtolower($name), '.mp4') || str_contains($format, 'h.264') || str_contains($format, 'mpeg4');
            if (! $isMp4) {
                continue;
            }
            // Favour the "h.264" derivative Archive generates for streaming.
            if (str_contains($format, 'h.264') && ! str_contains($format, 'hd')) {
                $best = $name;
                break;
            }
            $best = $best ?? $name;
        }

        if ($best === null) {
            return null;
        }

        return $this->base().'/download/'.$identifier.'/'.rawurlencode($best);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params): array
    {
        return $this->getUrl($this->base().$path.(empty($params) ? '' : '?'.http_build_query($params)));
    }

    /**
     * @return array<string, mixed>
     */
    private function getUrl(string $url): array
    {
        $this->requestCount++;

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 20))
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);
        } catch (ConnectionException $e) {
            throw new ProviderException('Archive.org connection failed: '.$e->getMessage(), 'archive', null, true);
        }

        if ($response->status() === 429) {
            throw new ProviderException('Archive.org rate limit reached.', 'archive', 429, true, 300);
        }

        if ($response->failed()) {
            throw ProviderException::fromStatus('archive', $response->status(), $response->body());
        }

        return (array) $response->json();
    }
}
