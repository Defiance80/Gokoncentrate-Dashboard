<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\Log;
use Modules\MediaRadar\Jobs\AnalyzeCandidateJob;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Sources\ProviderManager;

/**
 * Watches trusted creators directly instead of rediscovering them through
 * broad keyword searches, which is both more precise and far cheaper against
 * the YouTube search quota.
 */
class TrustedSourceService
{
    public function __construct(
        private ProviderManager $providers,
        private CandidateService $candidates,
        private ProviderHealthService $health,
    ) {
    }

    /**
     * @return array{checked: int, new: int, existing: int, errors: int}
     */
    public function refreshAll(int $limitPerSource = 10): array
    {
        $summary = ['checked' => 0, 'new' => 0, 'existing' => 0, 'errors' => 0];
        $settings = MediaRadarSetting::getInstance();

        $sources = MediaTrustedSource::query()
            ->where('enabled', true)
            ->where('publishing_mode', '!=', 'discovery_only')
            ->orderByDesc('priority')
            ->get();

        foreach ($sources as $source) {
            if (! $settings->providerEnabled($source->provider)) {
                continue;
            }

            $result = $this->refresh($source, $limitPerSource);

            $summary['checked']++;
            $summary['new'] += $result['new'];
            $summary['existing'] += $result['existing'];
            $summary['errors'] += $result['errors'];
        }

        return $summary;
    }

    /**
     * @return array{new: int, existing: int, errors: int}
     */
    public function refresh(MediaTrustedSource $source, int $limit = 10): array
    {
        $settings = MediaRadarSetting::getInstance();
        $counters = ['new' => 0, 'existing' => 0, 'errors' => 0];

        $run = MediaDiscoveryRun::create([
            'rule_id' => null,
            'provider' => $source->provider,
            'status' => MediaDiscoveryRun::STATUS_RUNNING,
            'trigger' => 'watchlist',
            'started_at' => now(),
        ]);

        try {
            $provider = $this->providers->fresh($source->provider);

            if (! $provider->isConfigured()) {
                throw new \RuntimeException(ucfirst($source->provider).' is not configured.');
            }

            $results = $provider->getRecentCreatorVideos($source->provider_creator_id, $limit);

            foreach ($results as $normalized) {
                $stored = $this->candidates->store($normalized, $settings);
                $candidate = $stored['candidate'];

                if ($stored['created']) {
                    if ($candidate->genre_id === null && $source->default_genre_id) {
                        $candidate->forceFill(['genre_id' => $source->default_genre_id])->save();
                    }

                    if ($source->auto_analyze) {
                        AnalyzeCandidateJob::dispatch($candidate->id, null);
                    }

                    $counters['new']++;
                } else {
                    $counters['existing']++;
                }
            }

            $source->forceFill(['last_checked_at' => now()])->save();
            $this->health->recordSuccess($source->provider);

            $run->markCompleted([
                'results_received' => count($results),
                'new_candidates' => $counters['new'],
                'existing_candidates' => $counters['existing'],
                'provider_request_count' => $provider->requestCount(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[MediaRadar] Trusted source refresh failed for '.$source->provider_creator_id.': '.$e->getMessage());
            $this->health->recordFailure($source->provider, $e->getMessage());
            $run->markFailed($e->getMessage());
            $counters['errors']++;
        }

        return $counters;
    }
}
