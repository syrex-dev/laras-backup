<?php

namespace App\Http\Requests\SuratMasuk;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('surat-masuk.ubah');
    }

    public function rules(): array
    {
        return [
            'pengirim' => ['required', 'string', 'max:255'],
            'nomor_surat' => ['required', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_diterima' => ['required', 'date'],
            'perihal' => ['required', 'string', 'max:1000'],
            'sifat' => ['required', 'string', 'in:biasa,penting,segera,rahasia'],
            'jenis' => ['required', 'string', 'in:fisik,email,undangan,edaran,permohonan,umum'],
            'klasifikasi_id' => ['required', 'exists:klasifikasi_surat,id'],
            'unit_tujuan_id' => ['nullable', 'exists:org_units,id'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'pengirim.required' => 'Asal surat / pengirim wajib diisi.',
            'nomor_surat.required' => 'Nomor surat asal wajib diisi.',
            'tanggal_surat.required' => 'Tanggal naskah surat wajib diisi.',
            'tanggal_diterima.required' => 'Tanggal diterima wajib diisi.',
            'perihal.required' => 'Perihal surat wajib diisi.',
            'sifat.required' => 'Sifat / prioritas surat wajib dipilih.',
            'jenis.required' => 'Jenis / media surat wajib dipilih.',
            'klasifikasi_id.required' => 'Kode klasifikasi arsip wajib dipilih.',
            'lampiran.*.mimes' => 'Format lampiran hanya diperbolehkan PDF, JPG, JPEG, atau PNG.',
            'lampiran.*.max' => 'Ukuran setiap berkas lampiran tidak boleh melebihi 10 MB.',
        ];
    }
}
