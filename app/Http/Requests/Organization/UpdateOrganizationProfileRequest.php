<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_instansi' => 'required|string|max:255',
            'kode_organisasi' => 'required|string|max:20',
            'alamat' => 'required|string',
            'email' => 'required|email|max:255',
            'telepon' => 'required|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'stempel' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'tema_default' => 'nullable|string|in:light,dark',
        ];
    }
}
