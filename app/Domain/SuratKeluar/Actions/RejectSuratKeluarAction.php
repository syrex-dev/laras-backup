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
        $trimmedAlasan = trim($alasan);
        if (empty($trimmedAlasan)) {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan penolakan naskah dinas wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $trimmedAlasan) {
            // 1. Kunci baris surat keluar
            /** @var SuratKeluar $lockedSurat */
            $lockedSurat = SuratKeluar::where('id', $suratKeluar->id)->lockForUpdate()->firstOrFail();

            if ($lockedSurat->status !== SuratKeluar::STATUS_DIPERIKSA) {
                throw ValidationException::withMessages([
                    'status' => "Surat keluar berstatus '{$lockedSurat->status}' tidak dapat ditolak.",
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
                    'approval' => 'Tidak ditemukan tahapan pemeriksaan aktif untuk surat keluar ini.',
                ]);
            }

            // 3. Validasi ulang otorisasi setelah terkunci
            $effectiveUser = $currentApproval->effectiveUser();
            $isAuthorized = ($effectiveUser && $effectiveUser->id === $actor->id) || $actor->can('surat-keluar.kelola');

            if (! $isAuthorized) {
                throw ValidationException::withMessages([
                    'authorization' => 'Anda bukan penanggung jawab giliran aktif untuk tahapan pemeriksaan ini.',
                ]);
            }

            $currentApproval->update([
                'status' => SuratKeluarApproval::STATUS_DITOLAK,
                'catatan' => $trimmedAlasan,
                'acted_at' => now(),
                'acted_user_id' => $actor->id,
            ]);

            // Transisi status surat ke 'ditolak'
            $lockedSurat->update([
                'status' => SuratKeluar::STATUS_DITOLAK,
                'status_approval' => 'rejected',
                'alasan_tolak' => $trimmedAlasan,
            ]);

            // Kirim notifikasi ke pembuat surat
            $creator = $lockedSurat->pembuat;
            if ($creator) {
                $creator->notify(new SuratKeluarApprovalNotification(
                    $lockedSurat,
                    $currentApproval,
                    'rejected',
                    $trimmedAlasan
                ));
            }

            $this->auditLogger->log(
                'surat_keluar.reject',
                $lockedSurat,
                "Menolak draf surat keluar '{$lockedSurat->perihal}'. Alasan: {$trimmedAlasan}"
            );

            return $lockedSurat->fresh(['approvals', 'versions']);
        });
    }
}
