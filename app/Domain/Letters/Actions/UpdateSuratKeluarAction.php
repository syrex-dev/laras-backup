<?php

namespace App\Domain\Letters\Actions;

use App\Models\SuratKeluar;

class UpdateSuratKeluarAction
{
    public function execute(SuratKeluar $suratKeluar, array $data): bool
    {
        return $suratKeluar->update([
            'klasifikasi_id' => $data['klasifikasi_id'],
            'penyetuju_id' => $data['penyetuju_id'],
            'tanggal_surat' => $data['tanggal_surat'],
            'tujuan' => $data['tujuan'],
            'perihal' => $data['perihal'],
            'lampiran' => $data['lampiran'] ?? null,
            'isi' => $data['isi'] ?? null,
            'templat_surat_id' => $data['templat_surat_id'],
        ]);
    }
}
