<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Jobs\RefreshTrustedSourceJob;
use Modules\MediaRadar\Models\MediaBlockedSource;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Sources\ProviderManager;
use Yajra\DataTables\DataTables;

class MediaSourcesController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.sources',
            'media-radar-sources',
            'ph ph-user-focus'
        );
    }

    public function index(Request $request): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return view('mediaradar::backend.sources.index', [
            'module_action' => 'List',
            'tab' => $request->get('tab', 'trusted'),
            'genres' => Genres::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'providerOptions' => app(ProviderManager::class)->options(),
            'publishingModes' => MediaTrustedSource::PUBLISHING_MODES,
        ]);
    }

    public function trusted_data(DataTables $datatable)
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return $datatable->eloquent(MediaTrustedSource::query()->with('genre'))
            ->editColumn('provider', fn ($data) => ucfirst((string) $data->provider))
            ->addColumn('genre', fn ($data) => e(optional($data->genre)->name ?: '-'))
            ->addColumn('approval_rate', fn ($data) => $data->approvalRate() === null ? '-' : $data->approvalRate().'%')
            ->addColumn('totals', fn ($data) => (int) $data->approval_count.' / '.(int) $data->rejection_count)
            ->addColumn('last_checked', fn ($data) => optional($data->last_checked_at)->diffForHumans() ?: '-')
            ->editColumn('enabled', fn ($data) => $data->enabled
                ? '<span class="badge bg-success-subtle">'.__('mediaradar::mediaradar.enabled').'</span>'
                : '<span class="badge bg-secondary-subtle">'.__('mediaradar::mediaradar.paused').'</span>')
            ->addColumn('action', fn ($data) => view('mediaradar::backend.sources.trusted_action', compact('data'))->render())
            ->rawColumns(['enabled', 'action'])
            ->make(true);
    }

    public function blocked_data(DataTables $datatable)
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        return $datatable->eloquent(MediaBlockedSource::query())
            ->editColumn('provider', fn ($data) => ucfirst((string) $data->provider))
            ->editColumn('created_at', fn ($data) => optional($data->created_at)->diffForHumans() ?: '-')
            ->addColumn('action', fn ($data) => view('mediaradar::backend.sources.blocked_action', compact('data'))->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store_trusted(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $data = $request->validate([
            'provider' => ['required', Rule::in(ProviderManager::SUPPORTED)],
            'provider_creator_id' => ['required', 'string', 'max:191'],
            'creator_name' => ['nullable', 'string', 'max:191'],
            'creator_url' => ['nullable', 'string', 'max:2000'],
            'default_genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'publishing_mode' => ['nullable', Rule::in(array_keys(MediaTrustedSource::PUBLISHING_MODES))],
            'priority' => ['nullable', 'integer', 'min:-100', 'max:100'],
        ]);

        $source = MediaTrustedSource::updateOrCreate(
            [
                'publication_id' => (int) MediaRadarSetting::getInstance()->publication_id,
                'provider' => $data['provider'],
                'provider_creator_id' => $data['provider_creator_id'],
            ],
            array_merge($data, [
                'enabled' => true,
                'auto_analyze' => $request->boolean('auto_analyze', true),
                // Phase 1 default: third-party media always waits for a human.
                'publishing_mode' => $data['publishing_mode'] ?? 'approval_required',
                'updated_by' => auth()->id(),
            ])
        );

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.creator_trusted'),
            'id' => $source->id,
        ]);
    }

    public function store_blocked(Request $request): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $data = $request->validate([
            'provider' => ['required', Rule::in(ProviderManager::SUPPORTED)],
            'provider_creator_id' => ['required', 'string', 'max:191'],
            'creator_name' => ['nullable', 'string', 'max:191'],
            'reason' => ['nullable', 'string', 'max:191'],
        ]);

        MediaBlockedSource::updateOrCreate(
            [
                'publication_id' => (int) MediaRadarSetting::getInstance()->publication_id,
                'provider' => $data['provider'],
                'provider_creator_id' => $data['provider_creator_id'],
            ],
            array_merge($data, ['blocked_by' => auth()->id()])
        );

        return response()->json([
            'status' => true,
            'message' => __('mediaradar::mediaradar.creator_blocked'),
        ]);
    }

    public function destroy_trusted(MediaTrustedSource $source): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $source->delete();

        return response()->json(['status' => true, 'message' => __('mediaradar::mediaradar.trust_removed')]);
    }

    public function destroy_blocked(MediaBlockedSource $source): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        $source->delete();

        return response()->json(['status' => true, 'message' => __('mediaradar::mediaradar.block_removed')]);
    }

    public function refresh(MediaTrustedSource $source): JsonResponse
    {
        abort_if(! auth()->user()->can('manage_media_radar_sources'), 403);

        RefreshTrustedSourceJob::dispatch($source->id);

        return response()->json(['status' => true, 'message' => __('mediaradar::mediaradar.source_refresh_queued')]);
    }
}
