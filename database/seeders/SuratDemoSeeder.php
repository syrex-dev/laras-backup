<?php

namespace Database\Seeders;

use App\Domain\Disposisi\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplatSurat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuratDemoSeeder extends Seeder
{
    /**
     * Data Transaksi Demo Fiktif untuk Pengujian Alur Persuratan.
     * PENGAMAN: Hanya boleh dijalankan pada environment local atau testing.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('SuratDemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');

            return;
        }

        // Ambil referensi user dari PoliteknikDemoSeeder
        $tu = User::where('email', 'tu@contoh.ac.id')->first() ?? User::first();
        $direktur = User::where('email', 'direktur@contoh.ac.id')->first() ?? $tu;
        $kajur = User::where('email', 'kajur.jtk@contoh.ac.id')->first() ?? $tu;

        if (! $tu || ! $direktur) {
            $this->command?->warn('SuratDemoSeeder dilewati karena user demo belum tersedia.');

            return;
        }

        $klsUndangan = KlasifikasiSurat::where('kode_klasifikasi', 'UND')->first()
            ?? KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'UND'], ['nama' => 'Undangan']);
        $klsPemberitahuan = KlasifikasiSurat::where('kode_klasifikasi', 'PBR')->first()
            ?? KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'PBR'], ['nama' => 'Pemberitahuan']);
        $klsPeminjaman = KlasifikasiSurat::where('kode_klasifikasi', 'PMJ')->first()
            ?? KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'PMJ'], ['nama' => 'Peminjaman']);

        $template = \App\Domain\SuratKeluar\Models\TemplateSurat::firstOrCreate(
            ['kode' => 'STD-DEMO'],
            [
                'nama' => 'Template Surat Resmi Institusi',
                'jenis' => \App\Domain\SuratKeluar\Models\TemplateSurat::JENIS_BIASA,
                'konten_default' => '<p>{isi_surat}</p>',
                'use_institution_header' => true,
                'is_active' => true,
            ]
        );

        // 1. Tiga Surat Masuk Demo
        $suratMasukData = [
            [
                'nomor_surat' => 'SM-CONTOH/UND/001',
                'klasifikasi_id' => $klsUndangan->id,
                'tanggal_surat' => now()->subDays(10),
                'tanggal_diterima' => now()->subDays(8),
                'pengirim' => 'Kementerian Pendidikan dan Kebudayaan',
                'perihal' => 'Undangan Rapat Koordinasi Vokasi Nasional',
                'ringkasan' => 'Rapat koordinasi pimpinan perguruan tinggi vokasi se-Indonesia.',
                'user_id' => $tu->id,
            ],
            [
                'nomor_surat' => 'SM-CONTOH/PBR/002',
                'klasifikasi_id' => $klsPemberitahuan->id,
                'tanggal_surat' => now()->subDays(15),
                'tanggal_diterima' => now()->subDays(12),
                'pengirim' => 'LLDIKTI Wilayah IV',
                'perihal' => 'Pemberitahuan Pemutakhiran Data Dosen dan Tendik',
                'ringkasan' => 'Instruksi pemutakhiran portofolio dan data beban kerja pada pangkalan data.',
                'user_id' => $tu->id,
            ],
            [
                'nomor_surat' => 'SM-CONTOH/PMJ/003',
                'klasifikasi_id' => $klsPeminjaman->id,
                'tanggal_surat' => now()->subDays(5),
                'tanggal_diterima' => now()->subDays(3),
                'pengirim' => 'Pemerintah Kota Contoh',
                'perihal' => 'Permohonan Peminjaman Gedung Serbaguna',
                'ringkasan' => 'Permohonan penggunaan gedung serbaguna untuk kegiatan pelatihan kewirausahaan daerah.',
                'user_id' => $tu->id,
            ],
        ];

        $suratMasukRecords = [];
        foreach ($suratMasukData as $item) {
            $suratMasukRecords[] = SuratMasuk::firstOrCreate(
                ['nomor_surat' => $item['nomor_surat']],
                array_merge($item, ['status_ekstraksi_ai' => 'done'])
            );
        }

        // 2. Tiga Surat Keluar Demo
        $suratKeluarData = [
            [
                'nomor_surat' => '001/UND/POLTEK/X/2026',
                'klasifikasi_id' => $klsUndangan->id,
                'tanggal_surat' => now()->subDays(3),
                'tujuan' => 'Seluruh Kepala Unit Kerja',
                'perihal' => 'Undangan Rapat Evaluasi Anggaran Triwulan',
                'isi' => 'Dengan hormat, mengundang Bapak/Ibu pimpinan unit kerja untuk menghadiri evaluasi anggaran dinas.',
                'status_approval' => 'draft',
                'user_id' => $tu->id,
                'penyetuju_id' => $direktur->id,
                'template_id' => $template->id,
                'templat_surat_id' => null,
            ],
            [
                'nomor_surat' => '002/PBR/POLTEK/X/2026',
                'klasifikasi_id' => $klsPemberitahuan->id,
                'tanggal_surat' => now()->subDays(7),
                'tujuan' => 'Seluruh Sivitas Akademika',
                'perihal' => 'Pemberitahuan Jadwal Libur Kedinasan Bersama',
                'isi' => 'Diberitahukan bahwa pelayanan persuratan disesuaikan mengikuti jadwal cuti kedinasan bersama.',
                'status_approval' => 'approved',
                'user_id' => $tu->id,
                'penyetuju_id' => $direktur->id,
                'template_id' => $template->id,
                'templat_surat_id' => null,
                'qr_token' => 'QR-'.Str::uuid()->toString(),
            ],
            [
                'nomor_surat' => '003/PMJ/POLTEK/X/2026',
                'klasifikasi_id' => $klsPeminjaman->id,
                'tanggal_surat' => now()->subDays(14),
                'tujuan' => 'Pemerintah Kota Contoh',
                'perihal' => 'Tanggapan Permohonan Penggunaan Fasilitas Kampus',
                'isi' => 'Fasilitas yang dimohonkan sedang dalam masa pemeliharaan berkala sehingga belum dapat dipinjamkan.',
                'status_approval' => 'rejected',
                'user_id' => $tu->id,
                'penyetuju_id' => $direktur->id,
                'template_id' => $template->id,
                'templat_surat_id' => null,
            ],
        ];

        foreach ($suratKeluarData as $item) {
            SuratKeluar::firstOrCreate(
                ['nomor_surat' => $item['nomor_surat']],
                $item
            );
        }

        // 3. Disposisi Demo
        if (isset($suratMasukRecords[0]) && isset($suratMasukRecords[1])) {
            Disposisi::firstOrCreate(
                [
                    'surat_masuk_id' => $suratMasukRecords[0]->id,
                    'pengirim_id' => $direktur->id,
                    'penerima_id' => $kajur->id,
                ],
                [
                    'instruksi' => 'Segera tindak lanjuti undangan koordinasi ini dan delegasikan tim terkait.',
                    'prioritas' => 'segera',
                    'status' => 'sudah_dibaca',
                ]
            );

            Disposisi::firstOrCreate(
                [
                    'surat_masuk_id' => $suratMasukRecords[1]->id,
                    'pengirim_id' => $direktur->id,
                    'penerima_id' => $tu->id,
                ],
                [
                    'instruksi' => 'Susun rekapitulasi data dosen dan tindak lanjuti dengan surat edaran internal.',
                    'prioritas' => 'biasa',
                    'status' => 'belum_dibaca',
                ]
            );
        }
    }
}
