<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Notifications\SuratKeluarApprovalNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitSuratKeluarAction
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Mengajukan draf surat keluar ke alur pemeriksaan berjenjang
     */
    public function execute(
        User $actor,
        SuratKeluar $suratKeluar,
        array $approvalsData = []
    ): SuratKeluar {
        if (! $suratKeluar->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => "Surat keluar dengan status '{$suratKeluar->status}' tidak dapat diajukan.",
            ]);
        }

        return DB::transaction(function () use ($actor, $suratKeluar, $approvalsData) {
            // 1. Inisialisasi atau konfigurasi ulang rantai persetujuan jika disediakan
            if (! empty($approvalsData)) {
                $this->setupApprovalChain($suratKeluar, $approvalsData);
            } elseif ($suratKeluar->approvals()->count() === 0) {
                $this->setupDefaultApprovalChain($suratKeluar);
            }

            // Pastikan ada minimal 1 tahap persetujuan
            if ($suratKeluar->approvals()->count() === 0) {
                throw ValidationException::withMessages([
                    'approvals' => 'Alur persetujuan surat keluar wajib memiliki minimal satu pejabat penandatangan.',
                ]);
            }

            // 2. Transisi status surat ke 'diperiksa'
            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_DIPERIKSA,
                'status_approval' => 'diperiksa',
                'current_approval_step' => 1,
            ]);

            // 3. Reset status step pertama menjadi menunggu
            $firstStep = $suratKeluar->approvals()->where('step_order', 1)->first();
            if ($firstStep) {
                $firstStep->update([
                    'status' => SuratKeluarApproval::STATUS_MENUNGGU,
                    'acted_at' => null,
                    'acted_user_id' => null,
                ]);

                // 4. Kirim notifikasi in-app dan antrean email ke pemeriksa pertama
                $targetUser = $firstStep->effectiveUser();
                if ($targetUser) {
                    $targetUser->notify(new SuratKeluarApprovalNotification(
                        $suratKeluar,
                        $firstStep,
                        'turn_started'
                    ));
                }
            }

            $this->auditLogger->log(
                'surat_keluar.submit',
                "Mengajukan draf '{$suratKeluar->perihal}' ke alur pemeriksaan berjenjang",
                $suratKeluar
            );

            return $suratKeluar->fresh(['approvals.user', 'approvals.position', 'versions']);
        });
    }

    /**
     * Menyusun rantai persetujuan kustom dari form pengajuan
     */
    protected function setupApprovalChain(SuratKeluar $suratKeluar, array $approvalsData): void
    {
        $suratKeluar->approvals()->delete();

        // Validasi tahap terakhir harus berupa penandatangan
        $lastItem = end($approvalsData);
        if (($lastItem['role_type'] ?? '') !== SuratKeluarApproval::ROLE_PENANDATANGAN) {
            throw ValidationException::withMessages([
                'approvals' => 'Tahap akhir pada alur pemeriksaan wajib berkedudukan sebagai penandatangan.',
            ]);
        }

        foreach ($approvalsData as $idx => $step) {
            $stepOrder = $idx + 1;
            $positionId = $step['position_id'] ?? null;
            $userId = $step['user_id'] ?? null;

            // Jika positionId ditentukan tapi userId kosong, cari user aktif di jabatan tersebut
            if ($positionId && ! $userId) {
                $pos = Position::find($positionId);
                $userId = $pos?->users()->first()?->id;
            }

            // Cek apakah jabatan sedang didelegasikan (Plt)
            $delegatedUserId = null;
            if ($positionId) {
                $pos = Position::find($positionId);
                $activeDelegation = $pos?->activeDelegation();
                if ($activeDelegation) {
                    $delegatedUserId = $activeDelegation->delegate_user_id;
                }
            }

            SuratKeluarApproval::create([
                'surat_keluar_id' => $suratKeluar->id,
                'step_order' => $stepOrder,
                'role_type' => $step['role_type'] ?? SuratKeluarApproval::ROLE_PEMERIKSA,
                'position_id' => $positionId,
                'user_id' => $userId,
                'delegated_user_id' => $delegatedUserId,
                'status' => SuratKeluarApproval::STATUS_MENUNGGU,
            ]);
        }
    }

    /**
     * Membangun rantai persetujuan bawaan dari hierarki unit kerja dan penandatangan
     */
    protected function setupDefaultApprovalChain(SuratKeluar $suratKeluar): void
    {
        $penandatanganId = $suratKeluar->penandatangan_id;
        $unit = $suratKeluar->orgUnit;

        $stepOrder = 1;

        // Jika unit memiliki pimpinan/kepala unit dan bukan pembuat sendiri, jadikan paraf tingkat 1
        if ($unit) {
            $headPosition = Position::where('org_unit_id', $unit->id)->orderBy('level')->first();
            if ($headPosition && $headPosition->users()->first() && $headPosition->users()->first()->id !== $suratKeluar->user_id) {
                $activeDelegation = $headPosition->activeDelegation();
                SuratKeluarApproval::create([
                    'surat_keluar_id' => $suratKeluar->id,
                    'step_order' => $stepOrder++,
                    'role_type' => SuratKeluarApproval::ROLE_PARAF,
                    'position_id' => $headPosition->id,
                    'user_id' => $headPosition->users()->first()->id,
                    'delegated_user_id' => $activeDelegation?->delegate_user_id,
                    'status' => SuratKeluarApproval::STATUS_MENUNGGU,
                ]);
            }
        }

        // Tahap akhir: Pejabat penandatangan
        if ($penandatanganId) {
            $penandatangan = User::find($penandatanganId);
            $posId = $penandatangan?->position_id;
            $pos = $posId ? Position::find($posId) : null;
            $activeDelegation = $pos?->activeDelegation();

            SuratKeluarApproval::create([
                'surat_keluar_id' => $suratKeluar->id,
                'step_order' => $stepOrder,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'position_id' => $posId,
                'user_id' => $penandatanganId,
                'delegated_user_id' => $activeDelegation?->delegate_user_id,
                'status' => SuratKeluarApproval::STATUS_MENUNGGU,
            ]);
        }
    }
}
