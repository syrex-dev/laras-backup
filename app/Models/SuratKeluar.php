<?php

namespace App\Models;

use App\Domain\SuratKeluar\Models\SuratKeluar as DomainSuratKeluar;

/**
 * Proxy model untuk kompatibilitas kode prototipe lama.
 * Seluruh logika kanonik berada di App\Domain\SuratKeluar\Models\SuratKeluar.
 */
class SuratKeluar extends DomainSuratKeluar
{
    // Mewarisi seluruh relasi, fillable, casting, dan logika dari Domain SuratKeluar
}
