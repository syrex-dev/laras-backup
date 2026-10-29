<?php

namespace App\Http\Requests\Letters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisposisiStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:belum_dibaca,sudah_dibaca,selesai',
        ];
    }
}
