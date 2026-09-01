<?php

namespace Modules\MediaRadar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Editable editorial payload: the title and description an editor approves
 * before the candidate becomes a movie.
 */
class MediaCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'editorial_title' => ['required', 'string', 'max:191'],
            'editorial_description' => ['nullable', 'string', 'max:20000'],
            'editorial_summary' => ['nullable', 'string', 'max:1000'],
            'editorial_tags' => ['nullable', 'array'],
            'editorial_tags.*' => ['string', 'max:100'],
            'genre_id' => ['required', 'integer', 'exists:genres,id'],
            'secondary_genre_ids' => ['nullable', 'array'],
            'secondary_genre_ids.*' => ['integer', 'exists:genres,id'],
            'poster_url' => ['nullable', 'string', 'max:2000'],
            'thumbnail_url' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $tags = $this->input('editorial_tags');

        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
            $this->merge(['editorial_tags' => $tags ?: null]);
        }
    }
}
