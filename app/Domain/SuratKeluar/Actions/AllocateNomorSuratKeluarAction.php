<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Services\NomorSuratKeluarService;
use App\Models\User;
use Carbon\Carbon;

class AllocateNomorSuratKeluarAction
{
    public function __construct(
        protected ?NomorSuratKeluarService $service = null
    ) {
        $this->service = $service ?? app(NomorSuratKeluarService::class);
    }

    /**
     * Mengalokasikan nomor resmi naskah dinas keluar secara atomik
     */
    public function execute(
        SuratKeluar $suratKeluar,
        ?User $actor = null,
        ?Carbon $date = null,
        ?string $customPattern = null
    ): SuratKeluar {
        return $this->service->allocateNomor($suratKeluar, $actor, $date, $customPattern);
    }
}
