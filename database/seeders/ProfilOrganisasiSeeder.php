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

        $exists = DB::table('profil_organisasi')->where('id', 1)->exists();

        if ($exists) {
            // Hanya update data teks sampel; JANGAN sentuh path_logo & path_stempel
            DB::table('profil_organisasi')->where('id', 1)->update([
                'nama_instansi' => 'Himpunan Mahasiswa TRPL',
                'kode_organisasi' => 'HIMA-TRPL',
                'alamat' => 'Jl. Kampus Politeknik, Gedung TI Lantai 2',
                'email' => 'hima.trpl@example.com',
                'telepon' => '081234567890',
                'updated_at' => now(),
            ]);
        } else {
            // Insert baru (fresh install demo): path null
            DB::table('profil_organisasi')->insert([
                'id' => 1,
                'nama_instansi' => 'Himpunan Mahasiswa TRPL',
                'kode_organisasi' => 'HIMA-TRPL',
                'alamat' => 'Jl. Kampus Politeknik, Gedung TI Lantai 2',
                'email' => 'hima.trpl@example.com',
                'telepon' => '081234567890',
                'path_logo' => null,
                'path_stempel' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
