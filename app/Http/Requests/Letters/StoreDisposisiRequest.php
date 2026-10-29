<?php

namespace App\Http\Requests\Letters;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisposisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surat_masuk_id' => 'required|exists:surat_masuk,id',
            'penerima_id' => 'required|exists:users,id',
            'instruksi' => 'required|string',
            'prioritas' => 'required|in:segera,rahasia,biasa',
        ];
    }
}
