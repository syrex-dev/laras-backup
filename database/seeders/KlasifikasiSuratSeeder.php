<?php

namespace Database\Seeders;

use App\Models\KlasifikasiSurat;
use App\Models\TemplatSurat;
use Illuminate\Database\Seeder;

class KlasifikasiSuratSeeder extends Seeder
{
    /**
     * Master Data Klasifikasi Arsip dan Format Naskah Dinas Dasar (Aman Produksi).
     * Idempoten: Menggunakan firstOrCreate agar aman dijalankan berkali-kali.
     */
    public function run(): void
    {
        $klasifikasiData = [
            [
                'kode_klasifikasi' => 'UND',
                'nama' => 'Undangan',
                'keterangan' => 'Surat undangan resmi untuk kegiatan kedinasan, rapat koordinasi, atau acara institusi.',
            ],
            [
                'kode_klasifikasi' => 'PBR',
                'nama' => 'Pemberitahuan',
                'keterangan' => 'Surat pemberitahuan umum kedinasan kepada pihak internal maupun eksternal.',
            ],
            [
                'kode_klasifikasi' => 'PMJ',
                'nama' => 'Peminjaman',
                'keterangan' => 'Surat permohonan peminjaman ruangan, fasilitas, atau perlengkapan kedinasan.',
            ],
            [
                'kode_klasifikasi' => 'HM.01',
                'nama' => 'Hubungan Masyarakat & Kerjasama',
                'keterangan' => 'Persuratan terkait kerjasama antar lembaga, kemitraan, dan siaran publik.',
            ],
            [
                'kode_klasifikasi' => 'KU.01',
                'nama' => 'Keuangan & Anggaran',
                'keterangan' => 'Persuratan terkait pengelolaan keuangan, perbendaharaan, dan anggaran dinas.',
            ],
            [
                'kode_klasifikasi' => 'KP.02',
                'nama' => 'Kepegawaian & Ketenagaan',
                'keterangan' => 'Persuratan mutasi, cuti, kenaikan pangkat, dan administrasi personil.',
            ],
            [
                'kode_klasifikasi' => 'AK.01',
                'nama' => 'Akademik & Kurikulum',
                'keterangan' => 'Persuratan proses belajar mengajar, kalender akademik, dan akreditasi.',
            ],
            [
                'kode_klasifikasi' => 'RT.01',
                'nama' => 'Rumah Tangga & Perlengkapan',
                'keterangan' => 'Persuratan inventarisasi barang milik negara dan pemeliharaan sarana.',
            ],
        ];

        foreach ($klasifikasiData as $item) {
            KlasifikasiSurat::firstOrCreate(
                ['kode_klasifikasi' => $item['kode_klasifikasi']],
                $item
            );
        }

        // Templat master naskah dinas legacy
        TemplatSurat::firstOrCreate(
            ['nama_templat' => 'Template Surat Resmi Institusi'],
            [
                'nama_templat' => 'Template Surat Resmi Institusi',
                'path_file' => 'templates/default.html',
            ]
        );
    }
}
