<?php

namespace App\Domain\Archive\Actions;

use App\Models\KlasifikasiSurat;

class CreateKlasifikasiAction
{
    public function execute(array $data): KlasifikasiSurat
    {
        return KlasifikasiSurat::create([
            'kode_klasifikasi' => $data['kode_klasifikasi'],
            'nama'             => $data['nama'],
            'keterangan'       => $data['keterangan'] ?? null,
        ]);
    }
}
