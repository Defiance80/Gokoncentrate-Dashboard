<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Http\Requests\MediaDiscoveryRuleRequest;
use Modules\MediaRadar\Jobs\RunDiscoveryRuleJob;
use Modules\MediaRadar\Models\MediaCandidateRuleMatch;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Sources\ProviderManager;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\Quality;
use Modules\Subscriptions\Models\Plan;
use Yajra\DataTables\DataTables;

/**
 * Search parameter editor: platform and genre are required, everything else
 * narrows the search.
 */
class MediaDiscoveryRulesController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(private ProviderManager $providers)
    {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.rules',
            'media-radar-rules',
            'ph ph-crosshair'
        );
    }

    public function index(): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return view('mediaradar::backend.rules.index', ['module_action' => 'List']);
    }

    public function index_data(DataTables $datatable)
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $query = MediaDiscoveryRule::query()->with('genre')->withCount('ruleMatches');

        return $datatable->eloquent($query)
            ->editColumn('providers', fn ($data) => implode(', ', array_map('ucfirst', $data->providerSlugs())))
            ->addColumn('genre', fn ($data) => e(optional($data->genre)->name ?: '-'))
            ->addColumn('schedule', fn ($data) => $data->schedule_type === 'interval'
                ? __('mediaradar::mediaradar.every_hours', ['hours' => $data->interval_hours])
                : ucfirst((string) $data->schedule_type))
            ->addColumn('last_run', fn ($data) => optional($data->last_run_at)->diffForHumans() ?: '-')
            ->addColumn('next_run', fn ($data) => optional($data->next_run_at)->diffForHumans() ?: '-')
            ->addColumn('candidates', fn ($data) => (int) $data->rule_matches_count)
            ->addColumn('approval_rate', fn ($data) => $this->approvalRate($data))
            ->editColumn('enabled', fn ($data) => $data->enabled
                ? '<span class="badge bg-success-subtle">'.__('mediaradar::mediaradar.enabled').'</span>'
                : '<span class="badge bg-secondary-subtle">'.__('mediaradar::mediaradar.paused').'</span>')
            ->addColumn('action', fn ($data) => view('mediaradar::backend.rules.action', compact('data'))->render())
            ->rawColumns(['enabled', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    private function approvalRate(MediaDiscoveryRule $rule): string
    {
        $matches = MediaCandidateRuleMatch::where('rule_id', $rule->id)->pluck('candidate_id');

        if ($matches->isEmpty()) {
            return '-';
        }

        $decided = \Modules\MediaRadar\Models\MediaCandidate::whereIn('id', $matches)
            ->whereIn('status', [CandidateStatus::APPROVED, CandidateStatus::SCHEDULED, CandidateStatus::PUBLISHED, CandidateStatus::REJECTED])
            ->get(['status']);

        if ($decided->isEmpty()) {
            return '-';
        }

        $approved = $decided->reject(fn ($c) => $c->status === CandidateStatus::REJECTED)->count();

        return round(($approved / $decided->count()) * 100).'%';
    }

    public function create(): View
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        return view('mediaradar::backend.rules.create', $this->formData());
    }

    public function store(MediaDiscoveryRuleRequest $request): RedirectResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $data = $request->ruleAttributes();
        $data['publication_id'] = (int) MediaRadarSetting::getInstance()->publication_id;
        $data['created_by'] = $data['updated_by'] = auth()->id();

        $rule = new MediaDiscoveryRule();
        $rule->fill($data);
        $rule->next_run_at = $rule->schedule_type === 'manual' ? null : now();
        $rule->save();

        return redirect()
            ->route('backend.media-radar-rules.index')
            ->with('success', __('messages.save_form', ['form' => __('mediaradar::mediaradar.rule')]));
    }

    public function edit(MediaDiscoveryRule $rule): View
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        return view('mediaradar::backend.rules.edit', array_merge($this->formData(), ['rule' => $rule]));
    }

    public function update(MediaDiscoveryRuleRequest $request, MediaDiscoveryRule $rule): RedirectResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $data = $request->ruleAttributes();
        $data['updated_by'] = auth()->id();

        $rule->fill($data)->save();

        return redirect()
            ->route('backend.media-radar-rules.index')
            ->with('success', __('messages.update_form', ['form' => __('mediaradar::mediaradar.rule')]));
    }

    public function destroy(MediaDiscoveryRule $rule): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $rule->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.delete_form', ['form' => __('mediaradar::mediaradar.rule')]),
        ]);
    }

    public function update_status(Request $request, MediaDiscoveryRule $rule): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $rule->forceFill(['enabled' => $request->boolean('status')])->save();

        return response()->json(['status' => true, 'message' => __('messages.status_updated')]);
    }

    public function duplicate(MediaDiscoveryRule $rule): RedirectResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $copy = $rule->replicate(['last_run_at', 'next_run_at', 'last_error']);
        $copy->name = $rule->name.' (copy)';
        $copy->enabled = false;
        $copy->created_by = $copy->updated_by = auth()->id();
        $copy->save();

        return redirect()
            ->route('backend.media-radar-rules.edit', $copy->id)
            ->with('success', __('mediaradar::mediaradar.rule_duplicated'));
    }

    /**
     * Run now. Still goes through the queue, never inline in the request.
     */
    public function run(MediaDiscoveryRule $rule): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        RunDiscoveryRuleJob::dispatch($rule->id, 'manual', auth()->id());

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.rule_queued'),
        ]);
    }

    public function runs(MediaDiscoveryRule $rule): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return view('mediaradar::backend.runs.index', [
            'module_action' => 'List',
            'ruleId' => $rule->id,
            'ruleName' => $rule->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'module_action' => 'Create',
            'genres' => Genres::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'providerOptions' => $this->providers->options(),
            'contentTypes' => MediaDiscoveryRule::CONTENT_TYPES,
            'scheduleTypes' => MediaDiscoveryRule::SCHEDULE_TYPES,
            'qualityOptions' => array_combine(array_keys(Quality::LADDER), array_keys(Quality::LADDER)),
            'plans' => Plan::pluck('name', 'id'),
        ];
    }
}
