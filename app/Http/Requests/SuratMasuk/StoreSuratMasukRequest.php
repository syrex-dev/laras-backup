<?php

namespace App\Http\Requests\SuratMasuk;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('surat-masuk.registrasi');
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
            'lampiran.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // Maks 10 MB per berkas
        ];
    }

    public function messages(): array
    {
        return [
            'pengirim.required' => 'Asal surat / pengirim wajib diisi.',
            'pengirim.max' => 'Asal surat tidak boleh lebih dari 255 karakter.',
            'nomor_surat.required' => 'Nomor surat asal wajib diisi.',
            'nomor_surat.max' => 'Nomor surat tidak boleh lebih dari 100 karakter.',
            'tanggal_surat.required' => 'Tanggal naskah surat wajib diisi.',
            'tanggal_surat.date' => 'Format tanggal surat tidak valid.',
            'tanggal_diterima.required' => 'Tanggal diterima wajib diisi.',
            'tanggal_diterima.date' => 'Format tanggal diterima tidak valid.',
            'perihal.required' => 'Perihal surat wajib diisi.',
            'perihal.max' => 'Perihal surat tidak boleh lebih dari 1000 karakter.',
            'sifat.required' => 'Sifat / prioritas surat wajib dipilih.',
            'sifat.in' => 'Sifat surat harus salah satu dari: biasa, penting, segera, rahasia.',
            'jenis.required' => 'Jenis / media surat wajib dipilih.',
            'jenis.in' => 'Jenis surat harus salah satu dari: fisik, email, undangan, edaran, permohonan, umum.',
            'klasifikasi_id.required' => 'Kode klasifikasi arsip wajib dipilih.',
            'klasifikasi_id.exists' => 'Klasifikasi arsip yang dipilih tidak terdaftar di sistem.',
            'unit_tujuan_id.exists' => 'Unit kerja tujuan tidak valid.',
            'lampiran.*.mimes' => 'Format lampiran hanya diperbolehkan PDF, JPG, JPEG, atau PNG.',
            'lampiran.*.max' => 'Ukuran setiap berkas lampiran tidak boleh melebihi 10 MB.',
        ];
    }
}
