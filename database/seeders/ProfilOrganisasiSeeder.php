<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfilOrganisasiSeeder extends Seeder
{
    /**
     * Seed profil organisasi sampel untuk keperluan demo/testing.
     * PENGAMAN: Dilarang dijalankan pada produksi agar tidak mengisi profil instansi sembarangan sebelum Setup Wizard.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('ProfilOrganisasiSeeder dilewati karena sistem tidak berada pada environment local/testing.');

            return;
        }

        // Delegasikan ke InstitutionProfile resmi (Politeknik Contoh Indonesia)
        \App\Domain\Instansi\Models\InstitutionProfile::current();
    }
}
