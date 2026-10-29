<?php

namespace App\Http\Requests\SuratMasuk;

use Illuminate\Foundation\Http\FormRequest;

class CancelSuratMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('surat-masuk.batalkan');
    }

    public function rules(): array
    {
        return [
            'alasan_batal' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_batal.required' => 'Alasan pembatalan surat masuk wajib diisi.',
            'alasan_batal.min' => 'Alasan pembatalan minimal harus 5 karakter.',
            'alasan_batal.max' => 'Alasan pembatalan tidak boleh melebihi 1000 karakter.',
        ];
    }
}
