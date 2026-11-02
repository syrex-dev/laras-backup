<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seeder untuk data simulasi, akun QA, dan transaksi naskah dinas demo fiktif.
     * PENGAMAN KETAT: Dilarang keras dijalankan pada lingkungan produksi.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');

            return;
        }

        $this->call([
            PoliteknikDemoSeeder::class,
            SuratMasukDemoSeeder::class,
            DisposisiDemoSeeder::class,
            SuratKeluarDemoSeeder::class,
            SuratDemoSeeder::class,
        ]);
    }
}
