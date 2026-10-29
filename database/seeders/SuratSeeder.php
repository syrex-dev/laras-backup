<?php

namespace Database\Seeders;

use App\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplatSurat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuratSeeder extends Seeder
{
    /**
     * Data Dummy untuk Manual QA:
     * - 3 KlasifikasiSurat   (Undangan, Pemberitahuan, Peminjaman)
     * - 1 TemplatSurat
     * - 3 SuratMasuk
     * - 3 SuratKeluar         (draft, approved + qr_token, rejected)
     * - 2 Disposisi aktif      (terhubung antar user yang valid)
     */
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Ambil referensi user dari UserSeeder
        // ---------------------------------------------------------------
        $admin = User::where('email', 'admin@himase.ac.id')->firstOrFail();
        $sekretaris = User::where('email', 'sekretaris@himase.ac.id')->firstOrFail();
        $pimpinan = User::where('email', 'ketua@himase.ac.id')->firstOrFail();
        $staf = User::where('email', 'staf@himase.ac.id')->firstOrFail();

        // ---------------------------------------------------------------
        // 1. Master Data: 3 Klasifikasi Surat
        // ---------------------------------------------------------------
        $klasifikasiData = [
            [
                'kode_klasifikasi' => 'UND',
                'nama' => 'Undangan',
                'keterangan' => 'Surat undangan resmi untuk kegiatan internal maupun eksternal organisasi.',
            ],
            [
                'kode_klasifikasi' => 'PBR',
                'nama' => 'Pemberitahuan',
                'keterangan' => 'Surat pemberitahuan umum kepada pihak internal dan eksternal.',
            ],
            [
                'kode_klasifikasi' => 'PMJ',
                'nama' => 'Peminjaman',
                'keterangan' => 'Surat peminjaman ruangan, fasilitas, atau peralatan organisasi.',
            ],
        ];

        foreach ($klasifikasiData as $item) {
            KlasifikasiSurat::firstOrCreate(
                ['kode_klasifikasi' => $item['kode_klasifikasi']],
                $item
            );
        }

        $klsUndangan = KlasifikasiSurat::where('kode_klasifikasi', 'UND')->firstOrFail();
        $klsPemberitahuan = KlasifikasiSurat::where('kode_klasifikasi', 'PBR')->firstOrFail();
        $klsPeminjaman = KlasifikasiSurat::where('kode_klasifikasi', 'PMJ')->firstOrFail();

        // ---------------------------------------------------------------
        // 2. Master Data: 1 Template Surat
        // ---------------------------------------------------------------
        $template = TemplatSurat::firstOrCreate(
            ['nama_templat' => 'Template Surat Resmi HIMASE'],
            [
                'nama_templat' => 'Template Surat Resmi HIMASE',
                'path_file' => 'templates/default.html',
            ]
        );

        // ---------------------------------------------------------------
        // 3. Transaksi: 3 Surat Masuk
        // ---------------------------------------------------------------
        $suratMasukData = [
            [
                'nomor_surat' => 'SM-2026/UND/001',
                'klasifikasi_id' => $klsUndangan->id,
                'tanggal_surat' => now()->subDays(10),
                'tanggal_diterima' => now()->subDays(8),
                'pengirim' => 'Dekanat Fakultas Teknik',
                'perihal' => 'Undangan Rapat Koordinasi Program Kerja',
                'ringkasan' => 'Rapat koordinasi program kerja semester ganjil yang melibatkan seluruh himpunan mahasiswa.',
                'user_id' => $sekretaris->id,
            ],
            [
                'nomor_surat' => 'SM-2026/PBR/002',
                'klasifikasi_id' => $klsPemberitahuan->id,
                'tanggal_surat' => now()->subDays(15),
                'tanggal_diterima' => now()->subDays(12),
                'pengirim' => 'Bagian Kemahasiswaan',
                'perihal' => 'Pemberitahuan Kebijakan Baru Organisasi',
                'ringkasan' => 'Informasi perubahan kebijakan terkait pengelolaan dana organisasi mahasiswa.',
                'user_id' => $sekretaris->id,
            ],
            [
                'nomor_surat' => 'SM-2026/PMJ/003',
                'klasifikasi_id' => $klsPeminjaman->id,
                'tanggal_surat' => now()->subDays(5),
                'tanggal_diterima' => now()->subDays(3),
                'pengirim' => 'BEM Politeknik',
                'perihal' => 'Permohonan Peminjaman Aula',
                'ringkasan' => 'Permohonan peminjaman aula gedung utama untuk acara seminar nasional teknologi.',
                'user_id' => $sekretaris->id,
            ],
        ];

        $suratMasukRecords = [];
        foreach ($suratMasukData as $item) {
            $suratMasukRecords[] = SuratMasuk::firstOrCreate(
                ['nomor_surat' => $item['nomor_surat']],
                array_merge($item, ['status_ekstraksi_ai' => 'done'])
            );
        }

        // ---------------------------------------------------------------
        // 4. Transaksi: 3 Surat Keluar (draft, approved, rejected)
        // ---------------------------------------------------------------
        $suratKeluarData = [
            // Draft — belum disetujui
            [
                'nomor_surat' => '001/UND/HIMASE/X/2026',
                'klasifikasi_id' => $klsUndangan->id,
                'tanggal_surat' => now()->subDays(3),
                'tujuan' => 'Dekan Fakultas Teknik',
                'perihal' => 'Undangan Musyawarah Besar HIMASE',
                'isi' => 'Dengan hormat, mengundang Bapak/Ibu untuk menghadiri Musyawarah Besar HIMASE yang akan dilaksanakan pada bulan November 2026.',
                'status_approval' => 'draft',
                'user_id' => $sekretaris->id,
                'penyetuju_id' => $pimpinan->id,
                'templat_surat_id' => $template->id,
            ],
            // Approved — sudah disetujui + QR token ter-generate
            [
                'nomor_surat' => '002/PBR/HIMASE/X/2026',
                'klasifikasi_id' => $klsPemberitahuan->id,
                'tanggal_surat' => now()->subDays(7),
                'tujuan' => 'Seluruh Anggota HIMASE',
                'perihal' => 'Pemberitahuan Jadwal Pelantikan Pengurus',
                'isi' => 'Surat ini memberitahukan bahwa pelantikan pengurus baru HIMASE akan dilaksanakan pada tanggal yang telah ditetapkan.',
                'status_approval' => 'approved',
                'user_id' => $sekretaris->id,
                'penyetuju_id' => $pimpinan->id,
                'templat_surat_id' => $template->id,
                'file_pdf' => 'surat-keluar/002-PBR-HIMASE-2026.pdf',
                'path_qrcode' => 'surat-keluar/qrcode-002.svg',
                'qr_token' => 'QR-'.Str::uuid()->toString(),
            ],
            // Rejected — ditolak oleh pimpinan
            [
                'nomor_surat' => '003/PMJ/HIMASE/X/2026',
                'klasifikasi_id' => $klsPeminjaman->id,
                'tanggal_surat' => now()->subDays(14),
                'tujuan' => 'Kepala Unit Sarana Prasarana',
                'perihal' => 'Peminjaman Laboratorium Komputer',
                'isi' => 'Permohonan peminjaman laboratorium komputer untuk kegiatan workshop programming HIMASE. Ditolak karena jadwal bentrok.',
                'status_approval' => 'rejected',
                'user_id' => $sekretaris->id,
                'penyetuju_id' => $pimpinan->id,
                'templat_surat_id' => $template->id,
            ],
        ];

        foreach ($suratKeluarData as $item) {
            SuratKeluar::firstOrCreate(
                ['nomor_surat' => $item['nomor_surat']],
                array_merge($item, [
                    'file_pdf' => $item['file_pdf'] ?? null,
                    'path_qrcode' => $item['path_qrcode'] ?? null,
                    'qr_token' => $item['qr_token'] ?? null,
                ])
            );
        }

        // ---------------------------------------------------------------
        // 5. Transaksi: 2 Disposisi Aktif (terhubung ke surat masuk #1 & #2)
        // ---------------------------------------------------------------

        // Disposisi 1: Pimpinan → Sekretaris (dari surat masuk pertama)
        Disposisi::firstOrCreate(
            [
                'surat_masuk_id' => $suratMasukRecords[0]->id,
                'pengirim_id' => $pimpinan->id,
                'penerima_id' => $sekretaris->id,
            ],
            [
                'instruksi' => 'Segera tindak lanjuti undangan rapat koordinasi ini dan siapkan bahan presentasi program kerja.',
                'prioritas' => 'segera',
                'status' => 'sudah_dibaca',
            ]
        );

        // Disposisi 2: Pimpinan → Staf (dari surat masuk kedua)
        Disposisi::firstOrCreate(
            [
                'surat_masuk_id' => $suratMasukRecords[1]->id,
                'pengirim_id' => $pimpinan->id,
                'penerima_id' => $staf->id,
            ],
            [
                'instruksi' => 'Pelajari kebijakan baru organisasi dan buatkan ringkasan laporan untuk rapat pleno berikutnya.',
                'prioritas' => 'biasa',
                'status' => 'belum_dibaca',
            ]
        );
    }
}
