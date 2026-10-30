<?php

namespace App\Console\Commands;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkSuratKeluarScaleCommand extends Command
{
    protected $signature = 'eoffice:benchmark-surat-keluar-scale
                            {--count=100000 : Target total naskah dinas keluar pada basis data}
                            {--chunk=5000 : Ukuran batch insert}
                            {--measure-only : Hanya ukur performa tanpa memasukkan data baru}';

    protected $description = 'Memuat 100.000 naskah dinas keluar dan mengukur performa query daftar, filter status/unit, dan pencarian pada PostgreSQL';

    public function handle(): int
    {
        // Pengaman keras: tolak di production
        if (app()->isProduction()) {
            $this->error('PERINGATAN KEAMANAN: Command benchmark pengujian beban dilarang keras dijalankan di environment production!');
            return Command::FAILURE;
        }

        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        $targetCount = (int) $this->option('count');
        $chunkSize = min((int) ($this->option('chunk') ?: 2000), 2000);
        $measureOnly = (bool) $this->option('measure-only');

        $driver = DB::getDriverName();
        $this->info("=== BENCHMARK PERFORMA SKALA 100.000 NASKAH DINAS KELUAR ({$driver}) ===");

        $currentCount = SuratKeluar::count();
        $this->line('Jumlah surat keluar saat ini: ' . number_format($currentCount));

        // 1. Populasi Data Skala 100.000
        if (! $measureOnly && $currentCount < $targetCount) {
            $needed = $targetCount - $currentCount;
            $this->info("Menghasilkan {$needed} naskah dinas keluar realistis untuk mencapai target " . number_format($targetCount) . "...");

            $user = User::first() ?? User::factory()->create();
            $unit = OrgUnit::first() ?? OrgUnit::create(['name' => 'Sekretariat Utama', 'code' => 'SEKRET', 'type' => 'administrative']);
            $units = OrgUnit::pluck('id')->toArray();
            if (empty($units)) {
                $units = [$unit->id];
            }
            $unitCount = count($units);

            $klasifikasi = KlasifikasiSurat::first() ?? KlasifikasiSurat::create(['kode_klasifikasi' => 'HM.01', 'nama' => 'Kerjasama']);
            $template = TemplateSurat::first() ?? TemplateSurat::create([
                'nama' => 'Template Standar',
                'jenis' => 'surat_tugas',
                'kode' => 'ST-BENCH',
                'konten_default' => '<p>Konten</p>',
            ]);

            $perihalSamples = [
                'Pemberitahuan Pelaksanaan Workshop Kurikulum Berbasis Industri',
                'Undangan Rapat Koordinasi Pimpinan Jurusan dan Unit Kerja',
                'Surat Tugas Penugasan Dosen Pembimbing Program Magang Bersertifikat',
                'Permohonan Izin Kunjungan Industri Mahasiswa Politeknik',
                'Penyampaian Usulan Anggaran Operasional dan Pemeliharaan Gedung',
                'Penetapan Hasil Seleksi Asisten Laboratorium Komputer dan Jaringan',
                'Pemberitahuan Ujian Akhir Semester dan Unggah Nilai Akademik',
                'Surat Keputusan Panitia Penerimaan Mahasiswa Baru Mandiri',
            ];
            $tujuanSamples = [
                'Direktur Jenderal Pendidikan Vokasi Kemendikbudristek',
                'Kepala Dinas Tenaga Kerja dan Transmigrasi Provinsi Jawa Barat',
                'Rektor Universitas Pendidikan Indonesia',
                'Direktur PT Industri Telekomunikasi Indonesia (Persero)',
                'Kepala Balai Besar Pengembangan Penjaminan Mutu Pendidikan Vokasi',
                'Pimpinan Perusahaan Mitra Kawasan Industri Karawang',
            ];

            $startTime = microtime(true);
            $inserted = 0;
            $bar = $this->output->createProgressBar($needed);
            $bar->start();

            while ($inserted < $needed) {
                $batchSize = min($chunkSize, $needed - $inserted);
                $batchRows = [];
                $now = now()->toDateTimeString();

                for ($i = 0; $i < $batchSize; $i++) {
                    $idx = $currentCount + $inserted + $i + 1;

                    // Distribusi Status Realistis:
                    // 0..59 (60%)  -> bernomor
                    // 60..79 (20%) -> diperiksa
                    // 80..89 (10%) -> draf
                    // 90..94 (5%)  -> revisi
                    // 95..97 (3%)  -> ditolak
                    // 98..99 (2%)  -> dibatalkan
                    $modStatus = $idx % 100;
                    if ($modStatus < 60) {
                        $status = SuratKeluar::STATUS_BERNOMOR;
                        $statusApproval = 'approved';
                        $nomorSurat = sprintf('%04d/SK/%s/2026', ($idx % 5000) + 1, $units[$idx % $unitCount]);
                    } elseif ($modStatus < 80) {
                        $status = SuratKeluar::STATUS_DIPERIKSA;
                        $statusApproval = 'diperiksa';
                        $nomorSurat = null;
                    } elseif ($modStatus < 90) {
                        $status = SuratKeluar::STATUS_DRAF;
                        $statusApproval = 'draft';
                        $nomorSurat = null;
                    } elseif ($modStatus < 95) {
                        $status = SuratKeluar::STATUS_REVISI;
                        $statusApproval = 'revisi';
                        $nomorSurat = null;
                    } elseif ($modStatus < 98) {
                        $status = SuratKeluar::STATUS_DITOLAK;
                        $statusApproval = 'rejected';
                        $nomorSurat = null;
                    } else {
                        $status = SuratKeluar::STATUS_DIBATALKAN;
                        $statusApproval = 'approved';
                        $nomorSurat = sprintf('%04d/SK/%s/2026', ($idx % 5000) + 1, $units[$idx % $unitCount]);
                    }

                    // Distribusi Sifat Realistis: biasa (70%), penting (20%), segera (8%), rahasia (2%)
                    $modSifat = $idx % 100;
                    if ($modSifat < 70) {
                        $sifat = SuratKeluar::SIFAT_BIASA;
                    } elseif ($modSifat < 90) {
                        $sifat = SuratKeluar::SIFAT_PENTING;
                    } elseif ($modSifat < 98) {
                        $sifat = SuratKeluar::SIFAT_SEGERA;
                    } else {
                        $sifat = SuratKeluar::SIFAT_RAHASIA;
                    }

                    $orgUnitId = $units[$idx % $unitCount];
                    $perihal = $perihalSamples[$idx % count($perihalSamples)] . ' - Batch #' . ceil($idx / 1000);
                    $tujuan = $tujuanSamples[$idx % count($tujuanSamples)];

                    $batchRows[] = [
                        'user_id' => $user->id,
                        'template_id' => $template->id,
                        'klasifikasi_id' => $klasifikasi->id,
                        'org_unit_id' => $orgUnitId,
                        'penandatangan_id' => $user->id,
                        'nomor_surat' => $nomorSurat,
                        'nomor_agenda' => $nomorSurat ? sprintf('AGK-%05d', $idx) : null,
                        'tujuan' => $tujuan,
                        'perihal' => $perihal,
                        'isi_surat' => '<p>Isi naskah naskah keluar skala benchmark...</p>',
                        'isi' => '<p>Isi naskah naskah keluar skala benchmark...</p>',
                        'sifat' => $sifat,
                        'status' => $status,
                        'status_approval' => $statusApproval,
                        'tanggal_surat' => Carbon::now()->subDays($idx % 365)->format('Y-m-d'),
                        'current_approval_step' => ($status === SuratKeluar::STATUS_DIPERIKSA) ? 1 : 2,
                        'created_at' => Carbon::now()->subMinutes($idx % (365 * 1440))->toDateTimeString(),
                        'updated_at' => $now,
                    ];
                }

                DB::transaction(function () use ($batchRows) {
                    DB::table('surat_keluar')->insert($batchRows);
                });
                $inserted += $batchSize;
                $bar->advance($batchSize);
            }

            $bar->finish();
            $this->newLine();
            $elapsedSeed = round(microtime(true) - $startTime, 2);
            $this->info("Berhasil memuat {$needed} data dalam {$elapsedSeed} detik.");
        }

        $totalData = SuratKeluar::count();
        $this->line("Total naskah dinas keluar aktif saat ini: " . number_format($totalData));

        // 2. EXPLAIN (ANALYZE, BUFFERS) Query Pada PostgreSQL
        if ($driver === 'pgsql') {
            $this->newLine();
            $this->info("=== EXPLAIN (ANALYZE, BUFFERS) DI POSTGRESQL (PORT 5433) ===");

            // Query 1: Default Listing (Halaman 1, ORDER BY id DESC LIMIT 15)
            $this->line("\n[Query 1: Daftar Standar Surat Keluar - ORDER BY id DESC LIMIT 15]");
            $t1 = microtime(true);
            $q1Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_surat, perihal, tujuan, sifat, status, created_at
                FROM surat_keluar
                WHERE deleted_at IS NULL
                ORDER BY id DESC
                LIMIT 15
            ");
            $dur1 = round((microtime(true) - $t1) * 1000, 2);
            foreach ($q1Plan as $p) {
                $this->line('  ' . $p->{'QUERY PLAN'});
            }
            $this->comment("  -> Total Waktu Eksekusi: {$dur1} ms");

            // Query 2: Filter Berdasarkan Status (status = 'bernomor')
            $this->line("\n[Query 2: Filter Berdasarkan Status (status = 'bernomor') - ORDER BY id DESC LIMIT 15]");
            $t2 = microtime(true);
            $q2Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_surat, perihal, tujuan, sifat, status, created_at
                FROM surat_keluar
                WHERE deleted_at IS NULL
                  AND status = 'bernomor'
                ORDER BY id DESC
                LIMIT 15
            ");
            $dur2 = round((microtime(true) - $t2) * 1000, 2);
            foreach ($q2Plan as $p) {
                $this->line('  ' . $p->{'QUERY PLAN'});
            }
            $this->comment("  -> Total Waktu Eksekusi: {$dur2} ms");

            // Query 3: Filter Gabungan (Unit Kerja + Status)
            $sampleUnit = OrgUnit::first()?->id ?? 1;
            $this->line("\n[Query 3: Filter Gabungan (org_unit_id = {$sampleUnit} AND status = 'bernomor') - ORDER BY id DESC LIMIT 15]");
            $t3 = microtime(true);
            $q3Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_surat, perihal, tujuan, sifat, status, created_at
                FROM surat_keluar
                WHERE deleted_at IS NULL
                  AND org_unit_id = {$sampleUnit}
                  AND status = 'bernomor'
                ORDER BY id DESC
                LIMIT 15
            ");
            $dur3 = round((microtime(true) - $t3) * 1000, 2);
            foreach ($q3Plan as $p) {
                $this->line('  ' . $p->{'QUERY PLAN'});
            }
            $this->comment("  -> Total Waktu Eksekusi: {$dur3} ms");

            // Query 4: Filter Status + Sifat (status = 'bernomor' AND sifat = 'penting')
            $this->line("\n[Query 4: Filter Status & Sifat (status = 'bernomor' AND sifat = 'penting') - ORDER BY id DESC LIMIT 15]");
            $t4 = microtime(true);
            $q4Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_surat, perihal, tujuan, sifat, status, created_at
                FROM surat_keluar
                WHERE deleted_at IS NULL
                  AND status = 'bernomor'
                  AND sifat = 'penting'
                ORDER BY id DESC
                LIMIT 15
            ");
            $dur4 = round((microtime(true) - $t4) * 1000, 2);
            foreach ($q4Plan as $p) {
                $this->line('  ' . $p->{'QUERY PLAN'});
            }
            $this->comment("  -> Total Waktu Eksekusi: {$dur4} ms");

            // Query 5: Pencarian Frasa Perihal / Tujuan
            $this->line("\n[Query 5: Pencarian Perihal (LIKE '%Kurikulum%') - ORDER BY id DESC LIMIT 15]");
            $t5 = microtime(true);
            $q5Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_surat, perihal, tujuan, sifat, status, created_at
                FROM surat_keluar
                WHERE deleted_at IS NULL
                  AND perihal LIKE '%Kurikulum%'
                ORDER BY id DESC
                LIMIT 15
            ");
            $dur5 = round((microtime(true) - $t5) * 1000, 2);
            foreach ($q5Plan as $p) {
                $this->line('  ' . $p->{'QUERY PLAN'});
            }
            $this->comment("  -> Total Waktu Eksekusi: {$dur5} ms");

            // Tampilkan Ringkasan Metrik Performa
            $this->newLine();
            $this->info("=== RINGKASAN PERFORMA QUERY SKALA " . number_format($totalData) . " SURAT KELUAR ===");
            $this->table(
                ['Skenario Query', 'Waktu Eksekusi (ms)', 'SLA Maksimal', 'Status SLA'],
                [
                    ['Query 1: Daftar Standar (Page 1)', "{$dur1} ms", '< 100 ms', $dur1 < 100 ? 'MEMENUHI (PASS)' : 'LAMBAT'],
                    ['Query 2: Filter Status (bernomor)', "{$dur2} ms", '< 100 ms', $dur2 < 100 ? 'MEMENUHI (PASS)' : 'LAMBAT'],
                    ['Query 3: Filter Unit + Status', "{$dur3} ms", '< 100 ms', $dur3 < 100 ? 'MEMENUHI (PASS)' : 'LAMBAT'],
                    ['Query 4: Filter Status + Sifat', "{$dur4} ms", '< 100 ms', $dur4 < 100 ? 'MEMENUHI (PASS)' : 'LAMBAT'],
                    ['Query 5: Pencarian Perihal (LIKE)', "{$dur5} ms", '< 250 ms', $dur5 < 250 ? 'MEMENUHI (PASS)' : 'LAMBAT'],
                ]
            );
        } else {
            $this->line("Database driver: {$driver}. Lewati EXPLAIN ANALYZE khusus PostgreSQL.");
        }

        return Command::SUCCESS;
    }
}
