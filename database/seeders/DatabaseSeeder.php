<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan pemanggilan penting:
     * 1. UserSeeder       → Membuat role (Spatie) dan 4 user QA
     * 2. ProfilOrganisasi → Data kop surat & kode organisasi
     * 3. SuratSeeder      → Master klasifikasi, template, surat, disposisi
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProfilOrganisasiSeeder::class,
            SuratSeeder::class,
        ]);
    }
}
