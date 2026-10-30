<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Arsitektur Seeder E-Office:
     * 1. ProductionSeeder (Selalu dijalankan di semua environment):
     *    - RolePermissionSeeder (Role PRD resmi & mapping permission granular)
     *    - KlasifikasiSuratSeeder (Kode klasifikasi arsip naskah dinas standar)
     *    - InstructionTemplateSeeder (Templat disposisi)
     *    - TemplateSuratSeeder (Templat surat dinas standar)
     *    Idempoten, aman produksi, tidak membuat akun atau dokumen transaksi.
     *
     * 2. DemoSeeder (HANYA dijalankan di environment 'local' dan 'testing'):
     *    - PoliteknikDemoSeeder (Unit kerja, jabatan, dan akun simulasi @contoh.ac.id)
     *    - ProfilOrganisasiSeeder (Profil sampel HIMA-TRPL)
     *    - SuratMasukDemoSeeder, DisposisiDemoSeeder, SuratKeluarDemoSeeder, SuratDemoSeeder
     *    Ditolak otomatis pada environment 'production' untuk integritas data.
     */
    public function run(): void
    {
        // 1. Selalu jalankan seeder produksi (idempoten & aman produksi)
        $this->call([
            ProductionSeeder::class,
        ]);

        // 2. Data demo HANYA dimuat pada lingkungan pengembangan atau pengujian
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
