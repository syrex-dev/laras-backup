<?php

namespace App\Http\Requests\Archive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_templat'  => 'required|string|max:255',
            'tipe_input'    => 'required|in:upload,textarea,keep',
            'file_template' => 'required_if:tipe_input,upload|file|mimes:html|max:5120',
            'konten_html'   => 'required_if:tipe_input,textarea|string',
        ];
    }
}
