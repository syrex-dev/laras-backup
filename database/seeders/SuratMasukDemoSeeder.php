<?php

namespace Database\Seeders;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratMasuk\Models\NumberingSequence;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Domain\SuratMasuk\Models\SuratMasukAttachment;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuratMasukDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('SuratMasukDemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');

            return;
        }

        $tuUser = User::where('email', 'tu@contoh.ac.id')->first() ?? User::first();
        if (! $tuUser) {
            $this->command?->warn('User untuk pendaftar surat masuk tidak ditemukan. Pastikan PoliteknikDemoSeeder telah dijalankan.');

            return;
        }

        $klasifikasiList = KlasifikasiSurat::all();
        if ($klasifikasiList->isEmpty()) {
            $klasifikasiList = collect([
                KlasifikasiSurat::create(['kode_klasifikasi' => 'HM.01', 'nama' => 'Hubungan Masyarakat & Kerjasama']),
                KlasifikasiSurat::create(['kode_klasifikasi' => 'KU.01', 'nama' => 'Keuangan & Anggaran DIPA']),
                KlasifikasiSurat::create(['kode_klasifikasi' => 'KP.02', 'nama' => 'Kepegawaian & Ketenagaan']),
                KlasifikasiSurat::create(['kode_klasifikasi' => 'AK.01', 'nama' => 'Akademik & Kurikulum']),
                KlasifikasiSurat::create(['kode_klasifikasi' => 'RT.01', 'nama' => 'Rumah Tangga & Perlengkapan BMN']),
            ]);
        }

        $units = OrgUnit::all();
        $sifatList = ['biasa', 'penting', 'segera', 'rahasia'];
        $jenisList = ['fisik', 'email', 'undangan', 'edaran', 'permohonan', 'umum'];
        $statusList = ['terdaftar', 'didisposisi', 'diproses', 'selesai', 'dibatalkan'];

        $senders = [
            'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
            'Direktorat Jenderal Pendidikan Vokasi',
            'Badan Kepegawaian Negara (BKN)',
            'Kementerian Keuangan RI - DJPb',
            'LLDIKTI Wilayah IV Jawa Barat & Banten',
            'Badan Pemeriksa Keuangan (BPK RI)',
            'Pemerintah Provinsi Jawa Barat',
            'Badan Riset dan Inovasi Nasional (BRIN)',
            'PT Telkom Indonesia (Persero) Tbk',
            'Bank Mandiri (Persero) Tbk',
            'Politeknik Negeri Jakarta',
            'Politeknik Negeri Bandung',
            'Universitas Indonesia',
            'Institut Teknologi Bandung',
            'PT Astra International Tbk',
        ];

        $perihals = [
            'Undangan Rapat Koordinasi Nasional Tata Usaha Perguruan Tinggi Vokasi',
            'Pemberitahuan Alokasi Tambahan Bantuan Operasional Perguruan Tinggi Negeri (BOPTN)',
            'Permohonan Kerjasama Program Magang Bersertifikat Industri (MBKM)',
            'Evaluasi Kinerja Pengelolaan Barang Milik Negara (BMN) Semester II',
            'Penyampaian Surat Keputusan Kenaikan Pangkat Dosen & Tenaga Kependidikan',
            'Undangan Sosialisasi Penerimaan Mahasiswa Baru Jalur SNPMB 2027',
            'Pemberitahuan Audit Kinerja Kepatuhan Anggaran DIPA Tahun 2026',
            'Penyampaian Dokumen Perjanjian Kerjasama Penelitian Terapan Vokasi',
            'Permohonan Narasumber Workshop Kurikulum Berbasis Industri',
            'Surat Edaran Pelaksanaan Upacara Peringatan Hari Pendidikan Nasional',
            'Undangan Uji Kompetensi Keahlian Mahasiswa Berstandar BNSP',
            'Laporan Hasil Pengawasan Kearsipan Dinamis Instansi Pemerintah',
        ];

        $targetTotal = 200;
        $records = [];
        $now = Carbon::now();

        $this->command?->info("Membuat {$targetTotal} data demo surat masuk...");

        DB::beginTransaction();

        for ($i = 1; $i <= $targetTotal; $i++) {
            $sender = $senders[array_rand($senders)];
            $perihal = $perihals[array_rand($perihals)]." Batch #{$i}";
            $sifat = $sifatList[array_rand($sifatList)];
            $jenis = $jenisList[array_rand($jenisList)];
            $status = $statusList[array_rand($statusList)];
            $klasifikasi = $klasifikasiList->random();
            $unit = $units->isNotEmpty() ? $units->random() : null;

            $daysAgo = rand(1, 180);
            $tglSurat = $now->copy()->subDays($daysAgo + rand(1, 5))->toDateString();
            $tglTerima = $now->copy()->subDays($daysAgo)->toDateString();

            $nomorAgenda = sprintf('SM-2026-%04d', $i);
            $nomorSurat = sprintf('B/%03d/%s/%s/2026', rand(10, 999), strtoupper(Str::random(3)), rand(1, 12));

            $isBatal = ($status === 'dibatalkan');
            $alasanBatal = $isBatal ? 'Naskah ditarik kembali oleh pihak pengirim karena revisi lampiran.' : null;

            $surat = SuratMasuk::create([
                'nomor_agenda' => $nomorAgenda,
                'nomor_surat' => $nomorSurat,
                'klasifikasi_id' => $klasifikasi->id,
                'unit_tujuan_id' => $unit?->id,
                'tanggal_surat' => $tglSurat,
                'tanggal_diterima' => $tglTerima,
                'pengirim' => $sender,
                'perihal' => $perihal,
                'sifat' => $sifat,
                'jenis' => $jenis,
                'status' => $status,
                'keterangan' => "Dicatat secara otomatis oleh demo seeder pada nomor agenda {$nomorAgenda}.",
                'alasan_batal' => $alasanBatal,
                'dibatalkan_oleh_id' => $isBatal ? $tuUser->id : null,
                'dibatalkan_pada' => $isBatal ? $now->copy()->subDays($daysAgo - 1) : null,
                'qr_token' => Str::random(32),
                'user_id' => $tuUser->id,
            ]);

            // Tambahkan contoh lampiran pada 40% surat
            if ($i % 3 === 0) {
                SuratMasukAttachment::create([
                    'surat_masuk_id' => $surat->id,
                    'file_name' => "Lampiran_Naskah_{$nomorAgenda}.pdf",
                    'file_path' => "surat-masuk/{$surat->id}/sample_attachment.pdf",
                    'mime_type' => 'application/pdf',
                    'file_size' => rand(150000, 2500000), // 150 KB - 2.5 MB
                    'uploader_id' => $tuUser->id,
                ]);
            }
        }

        // Perbarui sequence numbering
        NumberingSequence::updateOrCreate(
            ['type' => 'surat_masuk', 'year' => 2026, 'unit_code' => 'PUSAT'],
            ['prefix' => 'SM', 'last_number' => $targetTotal, 'format_pattern' => '{prefix}-{year}-{number:4}']
        );

        DB::commit();

        $this->command?->info("Berhasil membuat {$targetTotal} data demo surat masuk beserta lampiran dan numbering sequence!");
    }
}
