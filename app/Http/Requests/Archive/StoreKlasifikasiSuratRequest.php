<?php

namespace App\Http\Requests\Archive;

use Illuminate\Foundation\Http\FormRequest;

class StoreKlasifikasiSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_klasifikasi' => 'required|string|max:50|unique:klasifikasi_surat',
            'nama'             => 'required|string|max:255',
            'keterangan'       => 'nullable|string',
        ];
    }
}
