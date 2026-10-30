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
        if ($suratKeluar->status !== SuratKeluar::STATUS_DIPERIKSA) {
            throw ValidationException::withMessages([
                'status' => "Surat keluar berstatus '{$suratKeluar->status}' tidak dapat diminta revisi.",
            ]);
        }

        $trimmedCatatan = trim($catatan);
        if (empty($trimmedCatatan)) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan revisi wajib diisi agar pengaju memahami bagian yang harus diperbaiki.',
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

        return DB::transaction(function () use ($actor, $suratKeluar, $currentApproval, $trimmedCatatan) {
            $currentApproval->update([
                'status' => SuratKeluarApproval::STATUS_REVISI,
                'catatan' => $trimmedCatatan,
                'acted_at' => now(),
                'acted_user_id' => $actor->id,
            ]);

            // Kembalikan status surat ke 'revisi'
            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_REVISI,
                'status_approval' => 'draft',
                'catatan_revisi' => $trimmedCatatan,
            ]);

            // Kirim notifikasi ke pembuat surat
            $creator = $suratKeluar->pembuat;
            if ($creator) {
                $creator->notify(new SuratKeluarApprovalNotification(
                    $suratKeluar,
                    $currentApproval,
                    'revision_requested',
                    $trimmedCatatan
                ));
            }

            $this->auditLogger->log(
                'surat_keluar.request_revision',
                $suratKeluar,
                "Meminta revisi pada draf '{$suratKeluar->perihal}'. Catatan: {$trimmedCatatan}"
            );

            return $suratKeluar->fresh(['approvals', 'versions']);
        });
    }
}
