<?php

namespace Modules\Magazine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MagazineIssueRequest extends FormRequest
{
    public function rules(): array
    {
        $issue = $this->route('magazine_issue');
        $magazineId = (int) $this->input('magazine_id');
        $slugRule = [
            'required',
            'string',
            'max:255',
            Rule::unique('magazine_issues', 'slug')->where('magazine_id', $magazineId),
        ];
        if ($issue) {
            $slugRule[3] = Rule::unique('magazine_issues', 'slug')->ignore($issue->id)->where('magazine_id', $magazineId);
        }

        $rules = [
            'magazine_id' => 'required|exists:magazines,id',
            'issue_number' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'release_date' => 'nullable|date',
            'cover_image_url' => 'nullable|string|max:500',
            'trailer_video_id' => 'nullable|integer',
            'summary' => 'nullable|string',
            'status' => 'required|in:draft,scheduled,published',
            'visibility' => 'required|in:public,subscribers,paid-tier',
            'print_enabled' => 'boolean',
            'magcloud_product_url' => 'nullable|url|max:500',
            'magcloud_viewer_url' => 'nullable|url|max:500',
            'cta_label' => 'nullable|string|max:100',
            'notes_internal' => 'nullable|string',
        ];

        if ($this->boolean('print_enabled')) {
            $rules['magcloud_product_url'] = 'required|url|max:500|starts_with:https://';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('print_enabled') && $this->filled('magcloud_product_url')) {
                $host = parse_url($this->magcloud_product_url, PHP_URL_HOST);
                if ($host && ! str_contains(strtolower($host), 'magcloud.com')) {
                    $validator->errors()->add(
                        'magcloud_product_url',
                        __('magazine::validation_magcloud_host')
                    );
                }
            }
        });
    }

    public function authorize(): bool
    {
        return true;
    }
}
