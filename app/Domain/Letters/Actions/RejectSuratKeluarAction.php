<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Support\SuratKeluarStatus;
use App\Models\SuratKeluar;
use App\Traits\Loggable;

class RejectSuratKeluarAction
{
    use Loggable;

    public function execute(SuratKeluar $suratKeluar): bool
    {
        $updated = $suratKeluar->update([
            'status_approval' => SuratKeluarStatus::REJECTED,
        ]);

        if ($updated) {
            $this->logActivity('rejected', 'surat_keluar', $suratKeluar->id);
        }

        return $updated;
    }
}
