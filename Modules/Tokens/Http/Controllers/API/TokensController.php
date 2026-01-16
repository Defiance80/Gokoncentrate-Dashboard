<?php

namespace Modules\Tokens\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tokens\Models\TokenSetting;
use Modules\Tokens\Transformers\MeResource;

class TokensController extends Controller
{
    /**
     * GET /api/me - Returns user token information per INTEGRATIONS.md
     */
    public function me(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'status' => true,
            'data' => new MeResource($user),
            'message' => __('tokens::tokens.user_details'),
        ], 200);
    }

    /**
     * GET /api/token-settings - Returns public token settings for the app
     */
    public function settings()
    {
        $settings = TokenSetting::getInstance();

        return response()->json([
            'status' => true,
            'data' => [
                'token_label' => $settings->token_label,
                'token_name' => $settings->token_name,
                'global_enabled' => $settings->global_enabled,
            ],
            'message' => 'Token settings retrieved successfully.',
        ], 200);
    }
}
