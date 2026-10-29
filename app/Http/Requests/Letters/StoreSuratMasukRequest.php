<?php

namespace App\Http\Requests\Letters;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_surat'   => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'pengirim'      => 'required|string|max:255',
            'perihal'       => 'required|string|max:500',
            'ringkasan'     => 'nullable|string',
            'file_surat'    => 'nullable|file|mimes:pdf|max:10240',
        ];
    }
}
