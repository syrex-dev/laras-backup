<?php

namespace App\Traits;

use App\Domain\Audit\Services\AuditLogger;

trait Loggable
{
    public function logActivity($aksi, $namaTabel = null, $idRekaman = null)
    {
        $subject = $namaTabel ? "{$namaTabel}#{$idRekaman}" : 'sistem';
        AuditLogger::log(
            action: (string) $aksi,
            subject: $subject,
            description: "Aktivitas {$aksi} pada {$subject}",
            properties: [
                'nama_tabel' => $namaTabel,
                'id_rekaman' => $idRekaman,
            ]
        );
    }
}
