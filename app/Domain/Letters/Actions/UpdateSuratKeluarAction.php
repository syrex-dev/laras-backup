<?php

namespace App\Domain\Letters\Actions;

use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction as CanonicalSaveDraftAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;

/**
 * Adapter / Alias untuk kompatibilitas pemanggilan prototipe lama.
 * Seluruh logika kanonik dipusatkan pada App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction.
 */
class UpdateSuratKeluarAction
{
    public function __construct(
        protected CanonicalSaveDraftAction $canonicalAction
    ) {}

    public function execute(SuratKeluar $suratKeluar, array $data, ?int $userId = null): SuratKeluar
    {
        $user = $userId ? User::find($userId) : ($suratKeluar->pembuat ?? User::find($suratKeluar->user_id));
        return $this->canonicalAction->execute($user, $data, $suratKeluar);
    }
}
