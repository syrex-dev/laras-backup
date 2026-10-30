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
        return DB::transaction(function () use ($actor, $suratKeluar, $catatan, $autoAllocateNomor) {
            // 1. Kunci baris surat keluar
            /** @var SuratKeluar $lockedSurat */
            $lockedSurat = SuratKeluar::where('id', $suratKeluar->id)->lockForUpdate()->firstOrFail();

            if ($lockedSurat->status !== SuratKeluar::STATUS_DIPERIKSA) {
                throw ValidationException::withMessages([
                    'status' => "Surat keluar berstatus '{$lockedSurat->status}' tidak sedang dalam pemeriksaan aktif.",
                ]);
            }

            // 2. Kunci baris approval giliran aktif
            /** @var SuratKeluarApproval|null $currentApproval */
            $currentApproval = $lockedSurat->approvals()
                ->where('step_order', $lockedSurat->current_approval_step)
                ->lockForUpdate()
                ->first();

            if (! $currentApproval || $currentApproval->status !== SuratKeluarApproval::STATUS_MENUNGGU) {
                throw ValidationException::withMessages([
                    'approval' => 'Tidak ditemukan tahapan pemeriksaan aktif yang menunggu persetujuan.',
                ]);
            }

            // 3. Validasi ulang otorisasi setelah baris terkunci
            $effectiveUser = $currentApproval->effectiveUser();
            $isAuthorized = ($effectiveUser && $effectiveUser->id === $actor->id) || $actor->can('surat-keluar.kelola');

            if (! $isAuthorized) {
                throw ValidationException::withMessages([
                    'authorization' => 'Anda bukan penanggung jawab giliran aktif untuk tahapan pemeriksaan ini.',
                ]);
            }

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
            $nextStep = $lockedSurat->approvals()
                ->where('step_order', $currentApproval->step_order + 1)
                ->lockForUpdate()
                ->first();

            if ($nextStep) {
                // Maju ke langkah berikutnya
                $lockedSurat->update([
                    'current_approval_step' => $nextStep->step_order,
                ]);

                $nextStep->update([
                    'status' => SuratKeluarApproval::STATUS_MENUNGGU,
                ]);

                // Notifikasi ke pemeriksa langkah berikutnya
                $nextUser = $nextStep->effectiveUser();
                if ($nextUser) {
                    $nextUser->notify(new SuratKeluarApprovalNotification(
                        $lockedSurat,
                        $nextStep,
                        'turn_started'
                    ));
                }

                $actionType = $currentApproval->role_type === SuratKeluarApproval::ROLE_PARAF ? 'paraf' : 'periksa';
                $this->auditLogger->log(
                    "surat_keluar.{$actionType}",
                    $lockedSurat,
                    "Memberikan {$actionType} tahap {$currentApproval->step_order} pada surat keluar '{$lockedSurat->perihal}'"
                );
            } else {
                // Tahap akhir (penandatangan) disetujui penuh!
                $lockedSurat->update([
                    'status' => SuratKeluar::STATUS_DISETUJUI,
                    'status_approval' => 'approved',
                ]);

                if ($autoAllocateNomor) {
                    $nomorService = app(\App\Domain\SuratKeluar\Services\NomorSuratKeluarService::class);
                    $nomorService->allocateNomor($lockedSurat, $actor);
                }

                // Notifikasi ke pembuat surat bahwa draf telah disetujui penuh
                $creator = $lockedSurat->pembuat;
                if ($creator) {
                    $creator->notify(new SuratKeluarApprovalNotification(
                        $lockedSurat,
                        $currentApproval,
                        'approved'
                    ));
                }

                $this->auditLogger->log(
                    'surat_keluar.approved_final',
                    $lockedSurat,
                    "Menyetujui penuh surat keluar '{$lockedSurat->perihal}'" . ($autoAllocateNomor ? ' dan mengalokasikan nomor resmi' : ' (siap dialokasikan nomor resmi)')
                );
            }

            return $lockedSurat->fresh(['approvals', 'versions']);
        });
    }
}
