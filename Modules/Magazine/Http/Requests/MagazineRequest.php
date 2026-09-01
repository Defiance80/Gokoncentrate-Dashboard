<?php

namespace Modules\Magazine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MagazineRequest extends FormRequest
{
    public function rules(): array
    {
        $slugRule = 'required|string|max:255';
        $magazine = $this->route('magazine');
        $id = is_object($magazine) ? $magazine->id : $magazine;
        if ($id) {
            $slugRule .= '|unique:magazines,slug,' . $id;
        } else {
            $slugRule .= '|unique:magazines,slug';
        }

        return [
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string',
            'cover_image_url' => 'nullable|string|max:500',
            'theme_color' => 'nullable|string|max:50',
            'status' => 'required|in:draft,published,archived',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
