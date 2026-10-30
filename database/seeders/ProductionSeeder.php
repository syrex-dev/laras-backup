<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder resmi untuk lingkungan produksi.
     * Idempoten dan aman dijalankan berulang kali.
     * HANYA memuat:
     * - RolePermissionSeeder (Role PRD & Permission granular)
     * - KlasifikasiSuratSeeder (Klasifikasi arsip dasar)
     * - InstructionTemplateSeeder (Templat instruksi disposisi)
     * - TemplateSuratSeeder (Master templat naskah dinas keluar)
     *
     * TIDAK MEMUAT AKUN DEMO ATAU TRANSAKSI FIKTIF.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            KlasifikasiSuratSeeder::class,
            InstructionTemplateSeeder::class,
            TemplateSuratSeeder::class,
        ]);
    }
}
