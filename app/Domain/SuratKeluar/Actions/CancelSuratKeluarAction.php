<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Membatalkan surat keluar (baik yang sudah bernomor maupun belum).
     * Nomor surat yang sudah dialokasikan tidak akan dihapus atau dipakai ulang (anti-gap / audit-safe).
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        string $alasanBatal
    ): SuratKeluar {
        $alasan = trim($alasanBatal);
        if (empty($alasan)) {
            throw ValidationException::withMessages([
                'alasan_batal' => 'Alasan pembatalan naskah keluar wajib diisi.',
            ]);
        }

        if ($suratKeluar->status === SuratKeluar::STATUS_DIBATALKAN) {
            throw ValidationException::withMessages([
                'status' => 'Surat keluar ini telah berada dalam status dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $alasan) {
            $nomorSurat = $suratKeluar->nomor_surat ?: '(belum bernomor)';

            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_DIBATALKAN,
                'status_approval' => 'cancelled',
                'alasan_batal' => $alasan,
            ]);

            $this->auditLogger->log(
                'surat_keluar.cancel',
                $suratKeluar,
                "Membatalkan surat keluar '{$nomorSurat}' oleh {$actor->name}. Alasan: {$alasan}",
                causer: $actor
            );

            return $suratKeluar->fresh();
        });
    }
}
