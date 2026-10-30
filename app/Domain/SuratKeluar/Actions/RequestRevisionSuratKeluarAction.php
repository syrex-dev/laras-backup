<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Notifications\SuratKeluarApprovalNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestRevisionSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Meminta revisi draf naskah dinas keluar (wajib catatan keterangan)
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        string $catatan
    ): SuratKeluar {
        $trimmedCatatan = trim($catatan);
        if (empty($trimmedCatatan)) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan revisi wajib diisi agar pengaju memahami bagian yang harus diperbaiki.',
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $trimmedCatatan) {
            // 1. Kunci baris surat keluar
            /** @var SuratKeluar $lockedSurat */
            $lockedSurat = SuratKeluar::where('id', $suratKeluar->id)->lockForUpdate()->firstOrFail();

            if ($lockedSurat->status !== SuratKeluar::STATUS_DIPERIKSA) {
                throw ValidationException::withMessages([
                    'status' => "Surat keluar berstatus '{$lockedSurat->status}' tidak dapat diminta revisi.",
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

            // 3. Validasi ulang otorisasi setelah baris terkunci
            $effectiveUser = $currentApproval->effectiveUser();
            $isAuthorized = ($effectiveUser && $effectiveUser->id === $actor->id) || $actor->can('surat-keluar.kelola');

            if (! $isAuthorized) {
                throw ValidationException::withMessages([
                    'authorization' => 'Anda bukan penanggung jawab giliran aktif untuk tahapan pemeriksaan ini.',
                ]);
            }

            $currentApproval->update([
                'status' => SuratKeluarApproval::STATUS_REVISI,
                'catatan' => $trimmedCatatan,
                'acted_at' => now(),
                'acted_user_id' => $actor->id,
            ]);

            // Kembalikan status surat ke 'revisi'
            $lockedSurat->update([
                'status' => SuratKeluar::STATUS_REVISI,
                'status_approval' => 'draft',
                'catatan_revisi' => $trimmedCatatan,
            ]);

            // Kirim notifikasi ke pembuat surat
            $creator = $lockedSurat->pembuat;
            if ($creator) {
                $creator->notify(new SuratKeluarApprovalNotification(
                    $lockedSurat,
                    $currentApproval,
                    'revision_requested',
                    $trimmedCatatan
                ));
            }

            $this->auditLogger->log(
                'surat_keluar.request_revision',
                $lockedSurat,
                "Meminta revisi pada draf '{$lockedSurat->perihal}'. Catatan: {$trimmedCatatan}"
            );

            return $lockedSurat->fresh(['approvals', 'versions']);
        });
    }
}
