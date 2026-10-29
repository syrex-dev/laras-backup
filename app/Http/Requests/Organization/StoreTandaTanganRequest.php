<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreTandaTanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanda_tangan' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'tanda_tangan.required' => 'Silakan pilih file gambar tanda tangan terlebih dahulu.',
            'tanda_tangan.image' => 'File harus berupa gambar.',
            'tanda_tangan.mimes' => 'Format gambar harus berupa PNG, JPG, atau JPEG.',
            'tanda_tangan.max' => 'Ukuran gambar maksimal adalah 2 MB.',
        ];
    }
}
