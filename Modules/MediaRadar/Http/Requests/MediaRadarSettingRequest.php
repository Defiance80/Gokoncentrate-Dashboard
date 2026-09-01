<?php

namespace Modules\MediaRadar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\MediaRadar\Models\MediaRadarSetting;

class MediaRadarSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'auto_approve_min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'candidate_expiration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'cover_art_mode' => ['required', 'string', Rule::in(array_keys(MediaRadarSetting::COVER_ART_MODES))],
            'cover_crop_ratio' => ['required', 'string', 'regex:/^\d+:\d+$/'],
            'default_movie_access' => ['required', 'string', Rule::in(['free', 'paid', 'pay-per-view'])],
            'default_plan_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'cover_crop_ratio.regex' => 'Use a ratio such as 2:3 or 16:9.',
        ];
    }

    /**
     * Checkboxes are absent from the payload when unticked, so every switch is
     * read explicitly.
     *
     * @return array<string, mixed>
     */
    public function settingAttributes(): array
    {
        return array_merge($this->validated(), [
            'enabled' => $this->boolean('enabled'),
            'auto_approve' => $this->boolean('auto_approve'),
            'auto_publish_after_approval' => $this->boolean('auto_publish_after_approval'),
            'default_publish_status' => $this->boolean('default_publish_status'),
            'default_is_restricted' => $this->boolean('default_is_restricted'),
            'ai_enabled' => $this->boolean('ai_enabled'),
            'youtube_enabled' => $this->boolean('youtube_enabled'),
            'vimeo_enabled' => $this->boolean('vimeo_enabled'),
        ]);
    }
}
