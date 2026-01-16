<?php

namespace Modules\Filemanager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilemanagerRequest extends FormRequest
{
    public function rules()
    {
        // If files are posted directly, validate them.
        if ($this->hasFile('file_url')) {
            return [
                'file_url.*' => 'required|file|mimes:jpeg,jpg,png,gif,mov,mp4,avi',
            ];
        }
        // Otherwise, we expect pre-uploaded temp file names (assembled via chunk upload).
        return [
            'file_names.*' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'file_url.*.required' => 'File is required.',
            'file_url.*.file' => 'Each file must be a valid file.',
            'file_url.*.mimes' => 'Each file must be a type of: jpeg, jpg, png, gif, mov, mp4, avi.',
            'file_names.*.required' => 'File name is required.',
            'file_names.*.string' => 'Each file name must be a string.',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

