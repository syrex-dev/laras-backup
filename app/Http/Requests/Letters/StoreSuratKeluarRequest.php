<?php

namespace App\Http\Requests\Letters;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'klasifikasi_id'   => 'required|exists:klasifikasi_surat,id',
            'penyetuju_id'     => 'required|exists:users,id',
            'tanggal_surat'    => 'required|date',
            'tujuan'           => 'required|string|max:255',
            'perihal'          => 'required|string|max:500',
            'lampiran'         => 'nullable|string|max:255',
            'isi'              => 'nullable|string',
            'templat_surat_id' => 'required|exists:templat_surat,id',
        ];
    }
}
