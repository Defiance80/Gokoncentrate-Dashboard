<?php

namespace Modules\MediaRadar\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\MediaRadar\Http\Requests\MediaRadarSettingRequest;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Services\ProviderHealthService;
use Modules\Subscriptions\Models\Plan;

/**
 * Media Radar settings, including the auto/manual approval switch.
 */
class MediaRadarSettingsController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(private ProviderHealthService $health)
    {
        $this->traitInitializeModuleTrait(
            'mediaradar::mediaradar.settings',
            'media-radar-settings',
            'ph ph-sliders'
        );
    }

    public function index(): View
    {
        abort_if(! auth()->user()->can('manage_media_radar_settings'), 403);

        return view('mediaradar::backend.settings.index', [
            'module_action' => 'Settings',
            'settings' => MediaRadarSetting::getInstance(),
            'coverArtModes' => MediaRadarSetting::COVER_ART_MODES,
            'plans' => Plan::pluck('name', 'id'),
            'providerHealth' => $this->health->overview(),
        ]);
    }

    public function store(MediaRadarSettingRequest $request)
    {
        abort_if(! auth()->user()->can('manage_media_radar_settings'), 403);

        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $settings = MediaRadarSetting::getInstance();
        $settings->fill($request->settingAttributes());
        $settings->updated_by = auth()->id();
        $settings->save();

        $message = __('mediaradar::mediaradar.settings_saved');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * The approval switch on its own, so the dashboard can flip it inline.
     */
    public function toggle_auto_approve(Request $request)
    {
        abort_if(! auth()->user()->can('manage_media_radar_settings'), 403);

        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $settings = MediaRadarSetting::getInstance();
        $settings->auto_approve = $request->boolean('status');
        $settings->updated_by = auth()->id();
        $settings->save();

        return response()->json([
            'status' => true,
            'auto_approve' => (bool) $settings->auto_approve,
            'message' => $settings->auto_approve
                ? __('mediaradar::mediaradar.auto_approval_on')
                : __('mediaradar::mediaradar.auto_approval_off'),
        ]);
    }
}
