<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfilOrganisasiSeeder extends Seeder
{
    /**
     * Seed profil organisasi.
     *
     * PENTING: path_logo dan path_stempel TIDAK di-overwrite saat re-seed
     * agar file yang sudah diupload oleh Admin tidak hilang.
     * Jika record belum ada (fresh install), path diisi null dan Admin
     * harus upload via halaman Templat Kop Surat.
     */
    public function run(): void
    {
        $exists = DB::table('profil_organisasi')->where('id', 1)->exists();

        if ($exists) {
            // Hanya update data teks; JANGAN sentuh path_logo & path_stempel
            DB::table('profil_organisasi')->where('id', 1)->update([
                'nama_instansi'   => 'Himpunan Mahasiswa TRPL',
                'kode_organisasi' => 'HIMA-TRPL',
                'alamat'          => 'Jl. Kampus Politeknik, Gedung TI Lantai 2',
                'email'           => 'hima.trpl@example.com',
                'telepon'         => '081234567890',
                'updated_at'      => now(),
            ]);
        } else {
            // Insert baru (fresh install): path null, Admin upload manual
            DB::table('profil_organisasi')->insert([
                'id'              => 1,
                'nama_instansi'   => 'Himpunan Mahasiswa TRPL',
                'kode_organisasi' => 'HIMA-TRPL',
                'alamat'          => 'Jl. Kampus Politeknik, Gedung TI Lantai 2',
                'email'           => 'hima.trpl@example.com',
                'telepon'         => '081234567890',
                'path_logo'       => null,
                'path_stempel'    => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
