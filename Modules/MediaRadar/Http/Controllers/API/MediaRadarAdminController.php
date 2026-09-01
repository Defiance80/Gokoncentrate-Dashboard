<?php

namespace Modules\MediaRadar\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\MediaRadar\Http\Requests\MediaCandidateRequest;
use Modules\MediaRadar\Http\Requests\MediaDiscoveryRuleRequest;
use Modules\MediaRadar\Jobs\AnalyzeCandidateJob;
use Modules\MediaRadar\Jobs\ExecuteDiscoveryRunJob;
use Modules\MediaRadar\Jobs\RunDiscoveryRuleJob;
use Modules\MediaRadar\Models\MediaBlockedSource;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\EditorialWorkflowService;
use Modules\MediaRadar\Services\ProviderHealthService;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\RejectionReason;
use Modules\MediaRadar\Transformers\MediaCandidateResource;

/**
 * Admin JSON API for Media Radar.
 *
 * Authenticated with Sanctum and restricted to users holding the Media Radar
 * permissions. Provider secrets are never returned.
 */
class MediaRadarAdminController extends Controller
{
    public function __construct(
        private CandidateService $candidates,
        private EditorialWorkflowService $workflow,
        private ProviderHealthService $health,
    ) {
    }

    // ---- Rules -----------------------------------------------------------

    public function rules(Request $request): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        $rules = MediaDiscoveryRule::query()
            ->with('genre')
            ->when($request->filled('enabled'), fn ($q) => $q->where('enabled', $request->boolean('enabled')))
            ->orderByDesc('priority')
            ->paginate((int) $request->get('per_page', 25));

        return $this->ok($rules);
    }

    public function storeRule(MediaDiscoveryRuleRequest $request): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_rules');

        $data = $request->ruleAttributes();
        $data['publication_id'] = (int) MediaRadarSetting::getInstance()->publication_id;
        $data['created_by'] = $data['updated_by'] = auth()->id();

        $rule = new MediaDiscoveryRule();
        $rule->fill($data);
        $rule->next_run_at = $rule->schedule_type === 'manual' ? null : now();
        $rule->save();

        return $this->ok($rule->fresh('genre'), __('mediaradar::mediaradar.rule_created'));
    }

    public function showRule(MediaDiscoveryRule $rule): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return $this->ok($rule->load('genre'));
    }

    public function updateRule(MediaDiscoveryRuleRequest $request, MediaDiscoveryRule $rule): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_rules');

        $data = $request->ruleAttributes();
        $data['updated_by'] = auth()->id();
        $rule->fill($data)->save();

        return $this->ok($rule->fresh('genre'), __('mediaradar::mediaradar.rule_updated'));
    }

    public function destroyRule(MediaDiscoveryRule $rule): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_rules');

        $rule->delete();

        return $this->ok(null, __('mediaradar::mediaradar.rule_deleted'));
    }

    public function runRule(MediaDiscoveryRule $rule): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_rules');

        RunDiscoveryRuleJob::dispatch($rule->id, 'manual', auth()->id());

        return $this->ok(null, __('mediaradar::mediaradar.rule_queued'));
    }

    public function ruleRuns(MediaDiscoveryRule $rule, Request $request): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return $this->ok(
            $rule->runs()->latest('id')->paginate((int) $request->get('per_page', 25))
        );
    }

    // ---- Candidates ------------------------------------------------------

    public function candidates(Request $request): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        $query = MediaCandidate::query()->with('genre');

        foreach (['provider' => 'provider', 'status' => 'status', 'genre_id' => 'genre_id'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->get($param));
            }
        }

        if ($request->filled('min_score')) {
            $query->where('editorial_score', '>=', (int) $request->get('min_score'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->get('q').'%';
            $query->where(fn ($q) => $q->where('original_title', 'like', $term)
                ->orWhere('editorial_title', 'like', $term)
                ->orWhere('creator_name', 'like', $term));
        }

        $paginator = $query->orderByDesc('editorial_score')
            ->orderByDesc('discovered_at')
            ->paginate((int) $request->get('per_page', 25));

        return response()->json([
            'status' => true,
            'data' => MediaCandidateResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function showCandidate(MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return response()->json([
            'status' => true,
            'data' => new MediaCandidateResource($candidate->load(['genre', 'analysis'])),
        ]);
    }

    public function updateCandidate(MediaCandidateRequest $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('edit_media_radar');

        $previous = (string) $candidate->status;
        $candidate->fill($request->validated())->save();

        $this->candidates->recordDecision($candidate, 'edited', $previous, (string) $candidate->status, auth()->id());

        return response()->json([
            'status' => true,
            'data' => new MediaCandidateResource($candidate->fresh(['genre', 'analysis'])),
            'message' => __('mediaradar::mediaradar.candidate_updated'),
        ]);
    }

    public function approveCandidate(Request $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('approve_media_radar');

        $done = $this->workflow->approve(
            $candidate,
            auth()->id(),
            $request->boolean('publish_now'),
            'approved',
            $request->input('notes')
        );

        return $this->transition($done, $candidate, __('mediaradar::mediaradar.candidate_approved'));
    }

    public function rejectCandidate(Request $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('approve_media_radar');

        $reason = $request->input('rejection_reason');

        $done = $this->workflow->reject(
            $candidate,
            auth()->id(),
            RejectionReason::isValid($reason) ? $reason : 'other',
            $request->input('notes'),
            $request->boolean('block_creator')
        );

        return $this->transition($done, $candidate, __('mediaradar::mediaradar.candidate_rejected'));
    }

    public function archiveCandidate(MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('edit_media_radar');

        return $this->transition(
            $this->workflow->archive($candidate, auth()->id()),
            $candidate,
            __('mediaradar::mediaradar.candidate_archived')
        );
    }

    public function analyzeCandidate(MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('edit_media_radar');

        AnalyzeCandidateJob::dispatch($candidate->id, optional($candidate->rules()->first())->id);

        return $this->ok(null, __('mediaradar::mediaradar.analysis_queued'));
    }

    public function scheduleCandidate(Request $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('approve_media_radar');

        $request->validate(['scheduled_for' => ['required', 'date', 'after:now']]);

        if ($candidate->status === CandidateStatus::READY_FOR_REVIEW) {
            $this->workflow->approve($candidate, auth()->id());
            $candidate->refresh();
        }

        $done = $this->workflow->schedule($candidate, Carbon::parse($request->input('scheduled_for')), auth()->id());

        return $this->transition($done, $candidate, __('mediaradar::mediaradar.candidate_scheduled'));
    }

    public function publishCandidate(MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('approve_media_radar');

        return $this->transition(
            $this->workflow->publishNow($candidate, auth()->id()),
            $candidate,
            __('mediaradar::mediaradar.candidate_published')
        );
    }

    // ---- Sources ---------------------------------------------------------

    public function sources(Request $request): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return response()->json([
            'status' => true,
            'data' => [
                'trusted' => MediaTrustedSource::with('genre')->get(),
                'blocked' => MediaBlockedSource::all(),
            ],
        ]);
    }

    public function trustSource(Request $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_sources');

        $source = $this->workflow->trustCreator($candidate, auth()->id());

        return $this->ok($source, __('mediaradar::mediaradar.creator_trusted'));
    }

    public function blockSource(Request $request, MediaCandidate $candidate): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_sources');

        $blocked = $this->workflow->blockCreator($candidate, auth()->id(), $request->input('reason'));

        return $this->ok($blocked, __('mediaradar::mediaradar.creator_blocked'));
    }

    public function untrustSource(MediaTrustedSource $source): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_sources');

        $source->delete();

        return $this->ok(null, __('mediaradar::mediaradar.trust_removed'));
    }

    public function unblockSource(MediaBlockedSource $source): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_sources');

        $source->delete();

        return $this->ok(null, __('mediaradar::mediaradar.block_removed'));
    }

    // ---- Runs and system -------------------------------------------------

    public function runs(Request $request): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return $this->ok(
            MediaDiscoveryRun::with('rule')->latest('id')->paginate((int) $request->get('per_page', 25))
        );
    }

    public function showRun(MediaDiscoveryRun $run): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return $this->ok($run->load('rule'));
    }

    public function retryRun(MediaDiscoveryRun $run): JsonResponse
    {
        $this->authorizeAbility('manage_media_radar_rules');

        $retry = MediaDiscoveryRun::create([
            'rule_id' => $run->rule_id,
            'provider' => $run->provider,
            'status' => MediaDiscoveryRun::STATUS_QUEUED,
            'trigger' => 'manual',
            'created_by' => auth()->id(),
        ]);

        ExecuteDiscoveryRunJob::dispatch($retry->id);

        return $this->ok($retry, __('mediaradar::mediaradar.run_requeued'));
    }

    public function status(): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        $settings = MediaRadarSetting::getInstance();

        return response()->json([
            'status' => true,
            'data' => [
                'enabled' => (bool) $settings->enabled,
                'auto_approve' => (bool) $settings->auto_approve,
                'auto_approve_min_score' => (int) $settings->auto_approve_min_score,
                'cover_art_mode' => $settings->cover_art_mode,
                'counters' => $this->candidates->dashboardCounters((int) $settings->publication_id),
                'pipeline' => $this->health->pipelineOverview(),
            ],
        ]);
    }

    public function providerStatus(): JsonResponse
    {
        $this->authorizeAbility('view_media_radar');

        return $this->ok($this->health->overview());
    }

    // ---- Helpers ---------------------------------------------------------

    private function authorizeAbility(string $ability): void
    {
        abort_if(! auth()->check() || ! auth()->user()->can($ability), 403, __('messages.permission_denied'));
    }

    private function ok(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json(array_filter([
            'status' => true,
            'data' => $data,
            'message' => $message,
        ], static fn ($value) => $value !== null));
    }

    private function transition(bool $done, MediaCandidate $candidate, string $message): JsonResponse
    {
        return response()->json([
            'status' => $done,
            'data' => new MediaCandidateResource($candidate->fresh(['genre', 'analysis'])),
            'message' => $done ? $message : __('mediaradar::mediaradar.invalid_transition'),
        ], $done ? 200 : 422);
    }
}
