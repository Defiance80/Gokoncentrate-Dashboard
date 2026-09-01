<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\View\View;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaDiscoveryRun;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Services\CandidateService;
use Modules\MediaRadar\Services\ProviderHealthService;
use Modules\MediaRadar\Support\CandidateStatus;

class MediaRadarDashboardController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(
        private CandidateService $candidates,
        private ProviderHealthService $health,
    ) {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.title',
            'media-radar',
            'ph ph-radar'
        );
    }

    public function index(): View
    {
        abort_if(! auth()->user()->can('view_media_radar'), 403);

        $settings = MediaRadarSetting::getInstance();

        $counters = $this->candidates->dashboardCounters((int) $settings->publication_id);

        $priorityCandidates = MediaCandidate::query()
            ->where('publication_id', $settings->publication_id)
            ->where('status', CandidateStatus::READY_FOR_REVIEW)
            ->orderByDesc('editorial_score')
            ->orderByDesc('discovered_at')
            ->with('genre')
            ->limit(6)
            ->get();

        $recentRuns = MediaDiscoveryRun::with('rule')->latest('id')->limit(8)->get();

        $module_action = 'Dashboard';

        return view('mediaradar::backend.dashboard.index', [
            'module_action' => $module_action,
            'settings' => $settings,
            'counters' => $counters,
            'priorityCandidates' => $priorityCandidates,
            'recentRuns' => $recentRuns,
            'providerHealth' => $this->health->overview(),
            'pipeline' => $this->health->pipelineOverview(),
            'activeRules' => MediaDiscoveryRule::enabled()->count(),
            'trustedSources' => MediaTrustedSource::where('enabled', true)->count(),
        ]);
    }
}
