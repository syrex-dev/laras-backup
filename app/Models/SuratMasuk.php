<?php

namespace App\Models;

use App\Domain\SuratMasuk\Models\SuratMasuk as DomainSuratMasuk;

/**
 * Proxy model for backward compatibility with existing tests and legacy views.
 */
class SuratMasuk extends DomainSuratMasuk {}
