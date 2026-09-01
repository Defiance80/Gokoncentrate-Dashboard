<?php

namespace Modules\MediaRadar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\ProviderManager;
use Modules\MediaRadar\Support\Quality;

/**
 * Only the platform and the genre are required. Every other parameter is an
 * optional narrowing filter.
 */
class MediaDiscoveryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'enabled' => ['nullable', 'boolean'],

            // Required.
            'providers' => ['required', 'array', 'min:1'],
            'providers.*' => ['required', 'string', Rule::in(ProviderManager::SUPPORTED)],
            'genre_id' => ['required', 'integer', 'exists:genres,id'],

            // Optional filters.
            'secondary_genre_ids' => ['nullable', 'array'],
            'secondary_genre_ids.*' => ['integer', 'exists:genres,id'],
            'search_terms' => ['nullable', 'array'],
            'search_terms.*' => ['string', 'max:191'],
            'excluded_terms' => ['nullable', 'array'],
            'excluded_terms.*' => ['string', 'max:191'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:191'],
            'actors' => ['nullable', 'array'],
            'actors.*' => ['string', 'max:191'],

            'content_type' => ['nullable', 'string', Rule::in(array_keys(MediaDiscoveryRule::CONTENT_TYPES))],
            'min_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'max_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400', 'gte:min_duration_seconds'],
            'release_year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'release_year_to' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:release_year_from'],
            'min_quality' => ['nullable', 'string', Rule::in(array_keys(Quality::LADDER))],
            'quality_strict' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:5'],
            'published_within_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'min_view_count' => ['nullable', 'integer', 'min:0'],
            'minimum_editorial_score' => ['nullable', 'integer', 'min:0', 'max:100'],

            'preferred_creator_ids' => ['nullable', 'array'],
            'preferred_creator_ids.*' => ['string', 'max:191'],
            'blocked_creator_ids' => ['nullable', 'array'],
            'blocked_creator_ids.*' => ['string', 'max:191'],

            // null keeps the rule on the global auto/manual approval switch.
            'auto_approve' => ['nullable', 'in:inherit,0,1'],

            'destination_movie_access' => ['nullable', 'string', Rule::in(['free', 'paid', 'pay-per-view'])],
            'destination_plan_id' => ['nullable', 'integer'],
            'destination_is_restricted' => ['nullable', 'boolean'],

            'priority' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'schedule_type' => ['nullable', 'string', Rule::in(array_keys(MediaDiscoveryRule::SCHEDULE_TYPES))],
            'schedule_expression' => ['nullable', 'string', 'max:191'],
            'interval_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }

    public function messages(): array
    {
        return [
            'providers.required' => 'Choose at least one platform (YouTube or Vimeo).',
            'genre_id.required' => 'A destination genre is required.',
        ];
    }

    /**
     * The form posts comma separated text boxes and checkbox groups; this turns
     * them into the arrays the model casts to JSON.
     */
    protected function prepareForValidation(): void
    {
        $listFields = [
            'search_terms', 'excluded_terms', 'keywords', 'actors',
            'preferred_creator_ids', 'blocked_creator_ids',
        ];

        $payload = [];

        foreach ($listFields as $field) {
            $payload[$field] = $this->splitList($this->input($field));
        }

        foreach (['enabled', 'quality_strict', 'destination_is_restricted'] as $flag) {
            $payload[$flag] = $this->boolean($flag);
        }

        $this->merge($payload);
    }

    /**
     * @return list<string>|null
     */
    private function splitList(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n|,/', $value) ?: [];
        }

        if (! is_array($value)) {
            return null;
        }

        $clean = [];

        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return $clean === [] ? null : array_values(array_unique($clean));
    }

    /**
     * @return array<string, mixed>
     */
    public function ruleAttributes(): array
    {
        $data = $this->validated();

        $data['auto_approve'] = match ($this->input('auto_approve', 'inherit')) {
            '1', 1 => true,
            '0', 0 => false,
            default => null,
        };

        return $data;
    }
}
