<?php

namespace App\Domain\Letters\Actions;

use App\Domain\SuratKeluar\Actions\GenerateSuratKeluarPdfAction as CanonicalPdfAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;

/**
 * Adapter / Alias untuk kompatibilitas pemanggilan prototipe lama.
 * Seluruh logika kanonik dipusatkan pada App\Domain\SuratKeluar\Actions\GenerateSuratKeluarPdfAction.
 */
class PreviewSuratKeluarAction
{
    public function __construct(
        protected CanonicalPdfAction $canonicalAction
    ) {}

    public function execute(SuratKeluar $suratKeluar): \Barryvdh\DomPDF\PDF
    {
        return $this->canonicalAction->render($suratKeluar);
    }
}
