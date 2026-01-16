<?php

namespace Modules\Tokens\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenAdjustmentRequest extends FormRequest
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
            'amount_cents' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount_cents.required' => __('tokens::tokens.validation.amount_required'),
            'amount_cents.min' => __('tokens::tokens.validation.amount_min'),
            'reason.max' => __('tokens::tokens.validation.reason_max'),
        ];
    }
}
