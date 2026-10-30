<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Notifications\SuratKeluarApprovalNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Menolak naskah dinas keluar (wajib alasan penolakan)
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        string $alasan
    ): SuratKeluar {
        if ($suratKeluar->status !== SuratKeluar::STATUS_DIPERIKSA) {
            throw ValidationException::withMessages([
                'status' => "Surat keluar berstatus '{$suratKeluar->status}' tidak dapat ditolak.",
            ]);
        }

        $trimmedAlasan = trim($alasan);
        if (empty($trimmedAlasan)) {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan penolakan naskah dinas wajib diisi.',
            ]);
        }

        $currentApproval = $suratKeluar->currentApproval();
        if (! $currentApproval) {
            throw ValidationException::withMessages([
                'approval' => 'Tidak ditemukan tahapan pemeriksaan aktif untuk surat keluar ini.',
            ]);
        }

        $effectiveUser = $currentApproval->effectiveUser();
        $isAuthorized = ($effectiveUser && $effectiveUser->id === $actor->id) || $actor->can('surat-keluar.kelola');

        if (! $isAuthorized) {
            throw ValidationException::withMessages([
                'authorization' => 'Anda bukan penanggung jawab giliran aktif untuk tahapan pemeriksaan ini.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $currentApproval, $trimmedAlasan) {
            $currentApproval->update([
                'status' => SuratKeluarApproval::STATUS_DITOLAK,
                'catatan' => $trimmedAlasan,
                'acted_at' => now(),
                'acted_user_id' => $actor->id,
            ]);

            // Transisi status surat ke 'ditolak'
            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_DITOLAK,
                'status_approval' => 'rejected',
                'alasan_tolak' => $trimmedAlasan,
            ]);

            // Kirim notifikasi ke pembuat surat
            $creator = $suratKeluar->pembuat;
            if ($creator) {
                $creator->notify(new SuratKeluarApprovalNotification(
                    $suratKeluar,
                    $currentApproval,
                    'rejected',
                    $trimmedAlasan
                ));
            }

            $this->auditLogger->log(
                'surat_keluar.reject',
                "Menolak draf surat keluar '{$suratKeluar->perihal}'. Alasan: {$trimmedAlasan}",
                $suratKeluar
            );

            return $suratKeluar->fresh(['approvals', 'versions']);
        });
    }
}
