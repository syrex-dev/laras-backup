<?php

namespace App\Http\Requests\Archive;

use App\Models\KlasifikasiSurat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKlasifikasiSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $param = $this->route('klasifikasi_surat') ?? $this->route('klasifikasiSurat');
        $id = $param instanceof KlasifikasiSurat ? $param->id : $param;

        return [
            'kode_klasifikasi' => 'required|string|max:50|unique:klasifikasi_surat,kode_klasifikasi,'.$id,
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ];
    }
}
