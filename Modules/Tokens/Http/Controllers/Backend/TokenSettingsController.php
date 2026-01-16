<?php

namespace Modules\Tokens\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\Request;
use Modules\Tokens\Http\Requests\TokenSettingRequest;
use Modules\Tokens\Models\TokenSetting;

class TokenSettingsController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->module_name = 'token-settings';
        $this->traitInitializeModuleTrait(
            'tokens::tokens.settings_title',
            'token-settings',
            'ph ph-gear'
        );
    }

    /**
     * Display token settings form
     */
    public function index()
    {
        $settings = TokenSetting::getInstance();
        $isSuperAdmin = auth()->user()->hasRole('admin');
        $module_action = 'Settings';

        return view('tokens::backend.settings.index', compact('settings', 'isSuperAdmin', 'module_action'));
    }

    /**
     * Update token settings
     */
    public function store(TokenSettingRequest $request)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $settings = TokenSetting::getInstance();
        $data = $request->validated();

        // Only super admin can update these fields
        if (!auth()->user()->hasRole('admin')) {
            unset(
                $data['token_usd_cents_per_token'],
                $data['earn_cents'],
                $data['earn_seconds']
            );
        }

        // Process eligible_content_flags from checkboxes
        $data['eligible_content_flags'] = [
            'free_video' => $request->boolean('flag_free_video'),
            'free_magazine' => $request->boolean('flag_free_magazine'),
            'focus_mode' => $request->boolean('flag_focus_mode'),
        ];

        // Convert global_enabled checkbox
        $data['global_enabled'] = $request->boolean('global_enabled');

        $settings->update($data);

        $message = __('tokens::tokens.settings_saved');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        }

        return redirect()->back()->with('success', $message);
    }
}
