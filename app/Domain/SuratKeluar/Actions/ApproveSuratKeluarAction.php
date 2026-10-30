<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Notifications\SuratKeluarApprovalNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Memproses persetujuan / paraf naskah dinas keluar pada langkah giliran aktif
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        ?string $catatan = null,
        bool $autoAllocateNomor = false
    ): SuratKeluar {
        if ($suratKeluar->status !== SuratKeluar::STATUS_DIPERIKSA) {
            throw ValidationException::withMessages([
                'status' => "Surat keluar berstatus '{$suratKeluar->status}' tidak sedang dalam pemeriksaan aktif.",
            ]);
        }

        $currentApproval = $suratKeluar->currentApproval();
        if (! $currentApproval) {
            throw ValidationException::withMessages([
                'approval' => 'Tidak ditemukan tahapan pemeriksaan aktif untuk surat keluar ini.',
            ]);
        }

        // Validasi: hanya penanggung jawab giliran aktif atau Plt yang berwenang
        $effectiveUser = $currentApproval->effectiveUser();
        $isAuthorized = ($effectiveUser && $effectiveUser->id === $actor->id) || $actor->can('surat-keluar.kelola');

        if (! $isAuthorized) {
            throw ValidationException::withMessages([
                'authorization' => 'Anda bukan penanggung jawab giliran aktif untuk tahapan pemeriksaan ini.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $currentApproval, $catatan, $autoAllocateNomor) {
            $newApprovalStatus = $currentApproval->role_type === SuratKeluarApproval::ROLE_PARAF
                ? SuratKeluarApproval::STATUS_DIPARAF
                : SuratKeluarApproval::STATUS_DISETUJUI;

            $currentApproval->update([
                'status' => $newApprovalStatus,
                'catatan' => $catatan,
                'acted_at' => now(),
                'acted_user_id' => $actor->id,
            ]);

            // Cari langkah pemeriksaan berikutnya
            $nextStep = $suratKeluar->approvals()
                ->where('step_order', $currentApproval->step_order + 1)
                ->first();

            if ($nextStep) {
                // Maju ke langkah berikutnya
                $suratKeluar->update([
                    'current_approval_step' => $nextStep->step_order,
                ]);

                $nextStep->update([
                    'status' => SuratKeluarApproval::STATUS_MENUNGGU,
                ]);

                // Notifikasi ke pemeriksa langkah berikutnya
                $nextUser = $nextStep->effectiveUser();
                if ($nextUser) {
                    $nextUser->notify(new SuratKeluarApprovalNotification(
                        $suratKeluar,
                        $nextStep,
                        'turn_started'
                    ));
                }

                $actionType = $currentApproval->role_type === SuratKeluarApproval::ROLE_PARAF ? 'paraf' : 'periksa';
                $this->auditLogger->log(
                    "surat_keluar.{$actionType}",
                    $suratKeluar,
                    "Memberikan {$actionType} tahap {$currentApproval->step_order} pada surat keluar '{$suratKeluar->perihal}'"
                );
            } else {
                // Tahap akhir (penandatangan) disetujui penuh!
                $suratKeluar->update([
                    'status' => SuratKeluar::STATUS_DISETUJUI,
                    'status_approval' => 'approved',
                ]);

                if ($autoAllocateNomor) {
                    $nomorService = app(\App\Domain\SuratKeluar\Services\NomorSuratKeluarService::class);
                    $nomorService->allocateNomor($suratKeluar, $actor);
                }

                // Notifikasi ke pembuat surat bahwa draf telah disetujui penuh
                $creator = $suratKeluar->pembuat;
                if ($creator) {
                    $creator->notify(new SuratKeluarApprovalNotification(
                        $suratKeluar,
                        $currentApproval,
                        'approved'
                    ));
                }

                $this->auditLogger->log(
                    'surat_keluar.approved_final',
                    $suratKeluar,
                    "Menyetujui penuh surat keluar '{$suratKeluar->perihal}'" . ($autoAllocateNomor ? ' dan mengalokasikan nomor resmi' : ' (siap dialokasikan nomor resmi)')
                );
            }

            return $suratKeluar->fresh(['approvals', 'versions']);
        });
    }
}
