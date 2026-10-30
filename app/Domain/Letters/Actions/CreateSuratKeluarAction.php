<?php

namespace App\Domain\Letters\Actions;

use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction as CanonicalSaveDraftAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;

/**
 * Adapter / Alias untuk kompatibilitas pemanggilan prototipe lama.
 * Seluruh logika kanonik dipusatkan pada App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction.
 */
class CreateSuratKeluarAction
{
    public function __construct(
        protected CanonicalSaveDraftAction $canonicalAction
    ) {}

    public function execute(array $data, int $userId): SuratKeluar
    {
        $user = User::find($userId);
        return $this->canonicalAction->execute($user, $data);
    }
}
