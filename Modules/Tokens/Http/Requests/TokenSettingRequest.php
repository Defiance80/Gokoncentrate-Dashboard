<?php

namespace Modules\Tokens\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'demo_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token_label' => 'required|string|max:10',
            'token_name' => 'required|string|max:100',
            'token_usd_cents_per_token' => 'sometimes|integer|min:1',
            'earn_cents' => 'sometimes|integer|min:0',
            'earn_seconds' => 'sometimes|integer|min:1',
            'daily_cap_cents' => 'nullable|integer|min:0',
            'global_enabled' => 'boolean',
            'repeat_cooldown_seconds' => 'integer|min:0',
            'flag_free_video' => 'boolean',
            'flag_free_magazine' => 'boolean',
            'flag_focus_mode' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token_label.required' => __('tokens::tokens.validation.token_label_required'),
            'token_name.required' => __('tokens::tokens.validation.token_name_required'),
            'earn_seconds.min' => __('tokens::tokens.validation.earn_seconds_min'),
        ];
    }
}
