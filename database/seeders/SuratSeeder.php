<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SuratSeeder extends Seeder
{
    /**
     * Seeder persuratan gabungan (Didegradasi/Dipecah ke KlasifikasiSuratSeeder & SuratDemoSeeder).
     * Memanggil KlasifikasiSuratSeeder (master produksi) dan SuratDemoSeeder (transaksi demo lokal/testing).
     */
    public function run(): void
    {
        $this->call([
            KlasifikasiSuratSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                SuratDemoSeeder::class,
            ]);
        }
    }
}
