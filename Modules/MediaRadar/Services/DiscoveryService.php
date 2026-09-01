<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\Log;
use Modules\MediaRadar\Jobs\AnalyzeCandidateJob;
use Modules\MediaRadar\Models\MediaBlockedSource;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Sources\ProviderManager;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Sources\Support\ProviderException;
use Modules\MediaRadar\Support\CandidateFilter;
use Modules\MediaRadar\Support\RuleCriteria;

/**
 * Runs one discovery rule against one provider and records the outcome.
 *
 * Always invoked from a queued job or a console command, never from an HTTP
 * request that renders the public app.
 */
class DiscoveryService
{
    public function __construct(
        private ProviderManager $providers,
        private CandidateService $candidates,
        private DeduplicationService $deduplication,
        private CandidateFilter $filter,
        private ProviderHealthService $health,
    ) {
    }

    /**
     * @return list<int> ids of the runs that were created
     */
    public function queueRule(MediaDiscoveryRule $rule, string $trigger = 'schedule', ?int $userId = null): array
    {
        $settings = MediaRadarSetting::getInstance();
        $runIds = [];

        foreach ($rule->providerSlugs() as $slug) {
            if (! $settings->providerEnabled($slug)) {
                continue;
            }

            $run = MediaDiscoveryRun::create([
                'rule_id' => $rule->id,
                'provider' => $slug,
                'status' => MediaDiscoveryRun::STATUS_QUEUED,
                'trigger' => $trigger,
                'created_by' => $userId,
            ]);

            $runIds[] = $run->id;
        }

        $rule->forceFill([
            'last_run_at' => now(),
            'next_run_at' => $rule->calculateNextRun(),
        ])->save();

        return $runIds;
    }

    /**
     * Execute a queued run. Errors are captured on the run record so an
     * administrator can see what happened without reading server logs.
     */
    public function executeRun(MediaDiscoveryRun $run): void
    {
        $rule = $run->rule;

        if ($rule === null) {
            $run->markFailed('The discovery rule no longer exists.');

            return;
        }

        $run->markRunning();

        $settings = MediaRadarSetting::getInstance();
        $criteria = $this->criteriaFor($rule, (int) $settings->publication_id);

        $counters = [
            'results_received' => 0,
            'new_candidates' => 0,
            'existing_candidates' => 0,
            'filtered_out' => 0,
            'errors_count' => 0,
            'provider_request_count' => 0,
        ];

        try {
            $provider = $this->providers->fresh($run->provider);

            if (! $provider->isConfigured()) {
                throw new ProviderException(
                    ucfirst($run->provider).' is not configured. Add its credentials to the environment.',
                    $run->provider,
                    401,
                    false
                );
            }

            $results = $this->deduplication->unique($provider->search($rule));
            $counters['results_received'] = count($results);
            $counters['provider_request_count'] = $provider->requestCount();

            foreach ($results as $normalized) {
                $outcome = $this->ingest($normalized, $rule, $criteria, $settings, $run->id);

                if ($outcome === 'filtered') {
                    $counters['filtered_out']++;
                } elseif ($outcome === 'existing') {
                    $counters['existing_candidates']++;
                } elseif ($outcome === 'new') {
                    $counters['new_candidates']++;
                } else {
                    $counters['errors_count']++;
                }
            }

            $this->health->recordSuccess($run->provider);
            $run->markCompleted($counters);
            $rule->forceFill(['last_error' => null])->save();
        } catch (ProviderException $e) {
            $counters['provider_request_count'] = $counters['provider_request_count'] ?: 0;
            $this->health->recordFailure($run->provider, $e->getMessage(), $e->retryAfterSeconds);
            $run->markFailed($e->getMessage(), $counters);
            $rule->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            throw $e;
        } catch (\Throwable $e) {
            Log::error('[MediaRadar] Discovery run '.$run->id.' failed: '.$e->getMessage());
            $this->health->recordFailure($run->provider, $e->getMessage());
            $run->markFailed($e->getMessage(), $counters);
            $rule->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            throw $e;
        }
    }

    /**
     * @return string one of filtered, existing, new, error
     */
    private function ingest(
        NormalizedCandidate $normalized,
        MediaDiscoveryRule $rule,
        RuleCriteria $criteria,
        MediaRadarSetting $settings,
        int $runId
    ): string {
        try {
            if ($this->filter->reject($normalized, $criteria) !== CandidateFilter::PASSED) {
                return 'filtered';
            }

            $result = $this->candidates->store(
                $normalized,
                $settings,
                $rule,
                $runId,
                $this->filter->matchedTerms($normalized, $criteria),
                $this->filter->matchedActors($normalized, $criteria)
            );

            if ($result['created']) {
                // Persisted first, analysed second: an AI outage cannot lose it.
                AnalyzeCandidateJob::dispatch($result['candidate']->id, $rule->id);

                return 'new';
            }

            return 'existing';
        } catch (\Throwable $e) {
            Log::error('[MediaRadar] Failed to ingest '.$normalized->dedupeKey().': '.$e->getMessage());

            return 'error';
        }
    }

    /**
     * Rule filters plus the publication-wide blocked creator list.
     */
    public function criteriaFor(MediaDiscoveryRule $rule, int $publicationId): RuleCriteria
    {
        $criteria = RuleCriteria::fromRule($rule);

        $blocked = MediaBlockedSource::query()
            ->where('publication_id', $publicationId)
            ->whereIn('provider', $rule->providerSlugs())
            ->pluck('provider_creator_id')
            ->all();

        $criteria->blockedCreatorIds = array_values(array_unique(array_merge(
            $criteria->blockedCreatorIds,
            array_map('strval', $blocked)
        )));

        return $criteria;
    }

    /**
     * @return \Illuminate\Support\Collection<int, MediaDiscoveryRule>
     */
    public function dueRules()
    {
        return MediaDiscoveryRule::query()
            ->enabled()
            ->where('schedule_type', '!=', 'manual')
            ->where(function ($query) {
                $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();
    }
}
