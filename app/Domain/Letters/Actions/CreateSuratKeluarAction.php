<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Services\NomorSuratGenerator;
use App\Domain\Letters\Support\SuratKeluarStatus;
use App\Models\SuratKeluar;
use App\Traits\Loggable;

class CreateSuratKeluarAction
{
    use Loggable;

    public function __construct(
        protected NomorSuratGenerator $nomorSuratGenerator
    ) {}

    public function execute(array $data, int $userId): SuratKeluar
    {
        $surat = SuratKeluar::create([
            'klasifikasi_id'   => $data['klasifikasi_id'],
            'penyetuju_id'     => $data['penyetuju_id'],
            'tanggal_surat'    => $data['tanggal_surat'],
            'tujuan'           => $data['tujuan'],
            'perihal'          => $data['perihal'],
            'lampiran'         => $data['lampiran'] ?? null,
            'isi'              => $data['isi'] ?? null,
            'status_approval'  => SuratKeluarStatus::DRAFT,
            'user_id'          => $userId,
            'templat_surat_id' => $data['templat_surat_id'],
        ]);

        $nomorSurat = $this->nomorSuratGenerator->generate($surat);
        $surat->update(['nomor_surat' => $nomorSurat]);

        $this->logActivity('created', 'surat_keluar', $surat->id);

        return $surat;
    }
}
