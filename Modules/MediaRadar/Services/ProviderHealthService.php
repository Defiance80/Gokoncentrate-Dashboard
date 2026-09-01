<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\Cache;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Sources\ProviderManager;
use Modules\MediaRadar\Sources\Vimeo\VimeoClient;
use Modules\MediaRadar\Sources\YouTube\YouTubeClient;
use Modules\MediaRadar\Support\CandidateStatus;

/**
 * Surfaces provider state in the admin so failures are visible without reading
 * server logs.
 */
class ProviderHealthService
{
    private const CACHE_PREFIX = 'media_radar:health:';

    public function __construct(private ProviderManager $providers)
    {
    }

    public function recordSuccess(string $provider): void
    {
        $this->put($provider, [
            'last_success_at' => now()->toDateTimeString(),
            'last_error' => null,
            'last_error_at' => null,
            'cooldown_until' => null,
        ]);
    }

    public function recordFailure(string $provider, string $message, ?int $retryAfterSeconds = null): void
    {
        $state = $this->state($provider);

        $this->put($provider, [
            'last_success_at' => $state['last_success_at'] ?? null,
            'last_error' => mb_substr($message, 0, 500),
            'last_error_at' => now()->toDateTimeString(),
            'cooldown_until' => $retryAfterSeconds
                ? now()->addSeconds($retryAfterSeconds)->toDateTimeString()
                : null,
            'consecutive_failures' => (int) ($state['consecutive_failures'] ?? 0) + 1,
        ]);
    }

    public function isCoolingDown(string $provider): bool
    {
        $until = $this->state($provider)['cooldown_until'] ?? null;

        return $until !== null && strtotime($until) > time();
    }

    /**
     * @return array<string, mixed>
     */
    public function state(string $provider): array
    {
        return (array) Cache::get(self::CACHE_PREFIX.$provider, []);
    }

    /**
     * Everything the provider health panel renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function overview(): array
    {
        $rows = [];

        foreach (ProviderManager::SUPPORTED as $slug) {
            $adapter = $this->providers->make($slug);
            $state = $this->state($slug);

            $rows[] = [
                'slug' => $slug,
                'label' => $adapter->label(),
                'configured' => $adapter->isConfigured(),
                'status' => $this->statusLabel($slug, $adapter->isConfigured(), $state),
                'last_success_at' => $state['last_success_at'] ?? null,
                'last_error' => $state['last_error'] ?? null,
                'last_error_at' => $state['last_error_at'] ?? null,
                'cooldown_until' => $state['cooldown_until'] ?? null,
                'runs_today' => MediaDiscoveryRun::query()
                    ->where('provider', $slug)
                    ->whereDate('created_at', today())
                    ->count(),
                'detail' => $this->providerDetail($slug),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function providerDetail(string $slug): array
    {
        if ($slug === 'youtube') {
            $config = (array) config('mediaradar.providers.youtube', []);
            $client = new YouTubeClient($config);

            return [
                'searches_today' => $client->searchCallsToday(),
                'daily_search_cap' => (int) ($config['daily_search_cap'] ?? 0),
            ];
        }

        if ($slug === 'vimeo') {
            $rate = VimeoClient::lastRateLimit();

            return [
                'rate_limit' => $rate['limit'] ?? null,
                'rate_remaining' => $rate['remaining'] ?? null,
                'rate_reset' => $rate['reset'] ?? null,
            ];
        }

        return [];
    }

    private function statusLabel(string $slug, bool $configured, array $state): string
    {
        if (! $configured) {
            return 'not_configured';
        }

        if ($this->isCoolingDown($slug)) {
            return 'cooling_down';
        }

        if (! empty($state['last_error'])) {
            $lastSuccess = $state['last_success_at'] ?? null;
            $lastError = $state['last_error_at'] ?? null;

            if ($lastSuccess === null || strtotime((string) $lastError) > strtotime((string) $lastSuccess)) {
                return 'degraded';
            }
        }

        return 'healthy';
    }

    /**
     * @return array<string, mixed>
     */
    public function pipelineOverview(): array
    {
        return [
            'analysis_queue' => MediaCandidate::whereIn('status', [
                CandidateStatus::DEDUPLICATED,
                CandidateStatus::ANALYZING,
            ])->count(),
            'analysis_failures' => MediaCandidate::where('status', CandidateStatus::ANALYSIS_ERROR)->count(),
            'provider_failures' => MediaCandidate::where('status', CandidateStatus::PROVIDER_ERROR)->count(),
            'awaiting_publish' => MediaCandidate::whereIn('status', [
                CandidateStatus::APPROVED,
                CandidateStatus::SCHEDULED,
            ])->count(),
            'last_publish_at' => optional(
                MediaCandidate::whereNotNull('published_at_local')->latest('published_at_local')->first()
            )->published_at_local,
        ];
    }

    private function put(string $provider, array $state): void
    {
        Cache::put(
            self::CACHE_PREFIX.$provider,
            array_merge($this->state($provider), $state),
            now()->addSeconds((int) config('mediaradar.cache.health_ttl', 86400))
        );
    }
}
