<?php

namespace App\Domain\Letters\Actions;

use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction as CanonicalApproveAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;

/**
 * Adapter / Alias untuk kompatibilitas pemanggilan prototipe lama.
 * Seluruh logika kanonik dipusatkan pada App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction.
 */
class ApproveSuratKeluarAction
{
    public function __construct(
        protected CanonicalApproveAction $canonicalAction
    ) {}

    /**
     * Menjalankan approval surat keluar via pipeline kanonik.
     *
     * @return array{success: bool, message: string, was_signed: bool, surat: SuratKeluar}
     */
    public function execute(SuratKeluar $suratKeluar, int $approverId): array
    {
        $approver = User::find($approverId);
        $result = $this->canonicalAction->execute($approver, $suratKeluar);

        return [
            'success' => true,
            'message' => 'Surat keluar berhasil disetujui melalui pipeline kanonik.',
            'was_signed' => true,
            'surat' => $result,
        ];
    }
}
