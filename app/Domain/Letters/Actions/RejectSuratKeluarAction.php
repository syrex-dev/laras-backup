<?php

namespace App\Domain\Letters\Actions;

use App\Domain\SuratKeluar\Actions\RejectSuratKeluarAction as CanonicalRejectAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;

/**
 * Adapter / Alias untuk kompatibilitas pemanggilan prototipe lama.
 * Seluruh logika kanonik dipusatkan pada App\Domain\SuratKeluar\Actions\RejectSuratKeluarAction.
 */
class RejectSuratKeluarAction
{
    public function __construct(
        protected CanonicalRejectAction $canonicalAction
    ) {}

    public function execute(SuratKeluar $suratKeluar, int $rejectorId, ?string $alasan = null): SuratKeluar
    {
        $rejector = User::find($rejectorId);
        return $this->canonicalAction->execute($rejector, $suratKeluar, $alasan ?? 'Ditolak via legacy adapter');
    }
}
