<?php

namespace App\Domain\Archive\Actions;

use App\Models\KlasifikasiSurat;

class UpdateKlasifikasiAction
{
    public function execute(KlasifikasiSurat $klasifikasiSurat, array $data): bool
    {
        return $klasifikasiSurat->update([
            'kode_klasifikasi' => $data['kode_klasifikasi'],
            'nama'             => $data['nama'],
            'keterangan'       => $data['keterangan'] ?? null,
        ]);
    }
}
