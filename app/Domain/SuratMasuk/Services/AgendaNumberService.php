<?php

namespace App\Domain\SuratMasuk\Services;

use App\Domain\SuratMasuk\Models\NumberingSequence;

class AgendaNumberService
{
    /**
     * Menghasilkan nomor agenda surat masuk unik secara atomik
     * Format default: SM-{year}-{0001}
     */
    public function generate(int $year, string $unitCode = 'PUSAT', string $prefix = 'SM'): string
    {
        return NumberingSequence::nextNumber(
            type: 'surat_masuk',
            year: $year,
            unitCode: $unitCode,
            prefix: $prefix,
            formatPattern: '{prefix}-{year}-{number:4}'
        );
    }
}
