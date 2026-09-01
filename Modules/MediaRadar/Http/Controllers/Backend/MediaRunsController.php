<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\MediaRadar\Jobs\ExecuteDiscoveryRunJob;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Yajra\DataTables\DataTables;

/**
 * Search-run monitoring, so failures can be inspected without server access.
 */
class MediaRunsController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.runs',
            'media-radar-runs',
            'ph ph-clock-counter-clockwise'
        );
    }

    public function index(Request $request): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return view('mediaradar::backend.runs.index', [
            'module_action' => 'List',
            'ruleId' => $request->get('rule_id'),
            'ruleName' => null,
        ]);
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $query = MediaDiscoveryRun::query()->with('rule');

        if ($request->filled('filter.rule_id')) {
            $query->where('rule_id', (int) $request->input('filter.rule_id'));
        }

        if ($request->filled('filter.provider')) {
            $query->where('provider', $request->input('filter.provider'));
        }

        if ($request->filled('filter.column_status')) {
            $query->where('status', $request->input('filter.column_status'));
        }

        return $datatable->eloquent($query)
            ->addColumn('rule', fn ($data) => e(optional($data->rule)->name ?: __('mediaradar::mediaradar.trusted_source_watch')))
            ->editColumn('provider', fn ($data) => ucfirst((string) $data->provider))
            ->editColumn('status', fn ($data) => $this->statusBadge((string) $data->status))
            ->editColumn('started_at', fn ($data) => optional($data->started_at)->diffForHumans() ?: '-')
            ->addColumn('duration', fn ($data) => $data->durationLabel())
            ->addColumn('action', fn ($data) => view('mediaradar::backend.runs.action', compact('data'))->render())
            ->rawColumns(['status', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            MediaDiscoveryRun::STATUS_COMPLETED => 'bg-success-subtle',
            MediaDiscoveryRun::STATUS_PARTIAL => 'bg-warning-subtle',
            MediaDiscoveryRun::STATUS_FAILED => 'bg-danger-subtle',
            MediaDiscoveryRun::STATUS_RUNNING => 'bg-info-subtle',
            default => 'bg-secondary-subtle',
        };

        return '<span class="badge '.$class.'">'.e(ucfirst($status)).'</span>';
    }

    public function show(MediaDiscoveryRun $run): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return view('mediaradar::backend.runs.show', [
            'module_action' => 'Detail',
            'run' => $run->load('rule'),
        ]);
    }

    public function retry(MediaDiscoveryRun $run): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_rules'), 403);

        $retry = MediaDiscoveryRun::create([
            'rule_id' => $run->rule_id,
            'provider' => $run->provider,
            'status' => MediaDiscoveryRun::STATUS_QUEUED,
            'trigger' => 'manual',
            'created_by' => auth()->id(),
        ]);

        ExecuteDiscoveryRunJob::dispatch($retry->id);

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.run_requeued'),
        ]);
    }
}
