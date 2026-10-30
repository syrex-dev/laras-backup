<?php

namespace App\Console\Commands;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkSuratMasukCommand extends Command
{
    protected $signature = 'eoffice:benchmark-surat-masuk 
                            {--count=100000 : Jumlah total data uji yang dimuat} 
                            {--chunk=5000 : Ukuran batch insert}
                            {--measure-only : Hanya ukur performa tanpa memasukkan data baru}';

    protected $description = 'Memuat 100.000 data surat masuk dan mengukur performa query daftar & pencarian (< 2 detik)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('PERINGATAN KEAMANAN: Command benchmark pengujian beban data dilarang dijalankan di environment production!');

            return Command::FAILURE;
        }

        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $targetCount = (int) $this->option('count');
        $chunkSize = min((int) $this->option('chunk'), 500);
        $measureOnly = $this->option('measure-only');

        $this->info('=== BENCHMARK PERFORMA BUKU AGENDA SURAT MASUK ===');
        $currentCount = SuratMasuk::count();
        $this->line('Jumlah data surat masuk saat ini: '.number_format($currentCount));

        if (! $measureOnly && $currentCount < $targetCount) {
            $needed = $targetCount - $currentCount;
            $this->info("Menghasilkan {$needed} data uji tambahan untuk mencapai total ".number_format($targetCount).'...');

            $tuUser = User::where('email', 'tu@contoh.ac.id')->first() ?? User::first();
            $klasifikasi = KlasifikasiSurat::first() ?? KlasifikasiSurat::create([
                'kode_klasifikasi' => 'UM.01',
                'nama' => 'Umum & Tata Usaha',
            ]);
            $unit = OrgUnit::first();

            $senders = [
                'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
                'Direktorat Jenderal Pendidikan Vokasi',
                'Badan Kepegawaian Negara (BKN)',
                'Kementerian Keuangan RI',
                'LLDIKTI Wilayah IV Jawa Barat',
                'Badan Pemeriksa Keuangan (BPK RI)',
                'Pemerintah Provinsi Jawa Barat',
                'PT Telkom Indonesia Tbk',
            ];

            $perihals = [
                'Undangan Rapat Koordinasi Nasional Perguruan Tinggi Vokasi',
                'Pemberitahuan Alokasi Tambahan BOPTN Tahun Anggaran',
                'Permohonan Kerjasama Program Magang Bersertifikat Industri',
                'Evaluasi Kinerja Pengelolaan Barang Milik Negara (BMN)',
                'Penyampaian SK Kenaikan Pangkat Jabatan Fungsional Dosen',
                'Undangan Sosialisasi Penerimaan Mahasiswa Baru SNPMB',
            ];

            $sifatList = ['biasa', 'penting', 'segera', 'rahasia'];
            $statusList = ['terdaftar', 'didisposisi', 'diproses', 'selesai', 'dibatalkan'];

            $startTime = microtime(true);
            $inserted = 0;
            $now = Carbon::now();

            $bar = $this->output->createProgressBar($needed);
            $bar->start();

            DB::beginTransaction();

            while ($inserted < $needed) {
                $batchSize = min($chunkSize, $needed - $inserted);
                $batchData = [];

                for ($j = 0; $j < $batchSize; $j++) {
                    $index = $currentCount + $inserted + $j + 1;
                    $nomorAgenda = sprintf('SM-2026-%06d', $index);
                    $nomorSurat = 'B/'.rand(100, 999).'/UN.1/'.rand(1, 12).'/2026';
                    $sender = $senders[$j % count($senders)];
                    $perihal = $perihals[$j % count($perihals)].' - Dokumen #'.$index;
                    $sifat = $sifatList[$j % count($sifatList)];
                    $status = $statusList[$j % count($statusList)];
                    $date = $now->copy()->subDays($j % 365)->toDateString();
                    $searchText = "{$nomorAgenda} {$nomorSurat} {$sender} {$perihal}";

                    $batchData[] = [
                        'nomor_agenda' => $nomorAgenda,
                        'nomor_surat' => $nomorSurat,
                        'klasifikasi_id' => $klasifikasi->id,
                        'unit_tujuan_id' => $unit?->id,
                        'tanggal_surat' => $date,
                        'tanggal_diterima' => $date,
                        'pengirim' => $sender,
                        'perihal' => $perihal,
                        'sifat' => $sifat,
                        'jenis' => 'fisik',
                        'status' => $status,
                        'keterangan' => 'Generated benchmark test data',
                        'search_text' => $searchText,
                        'qr_token' => Str::random(32),
                        'user_id' => $tuUser ? $tuUser->id : User::first()->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('surat_masuk')->insert($batchData);
                $batchData = null;
                unset($batchData);

                $inserted += $batchSize;
                $bar->advance($batchSize);

                if ($inserted % 5000 === 0) {
                    DB::commit();
                    gc_collect_cycles();
                    DB::beginTransaction();
                }
            }

            DB::commit();

            $bar->finish();
            $this->newLine();
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Berhasil memuat {$needed} data uji dalam {$duration} detik.");
        }

        $totalRecords = SuratMasuk::count();
        $this->newLine();
        $this->info('Memulai pengukuran performa terhadap total '.number_format($totalRecords).' data surat masuk...');

        $tests = [
            [
                'name' => '1. Halaman Pertama (Page 1 Default)',
                'action' => function () {
                    return SuratMasuk::with(['klasifikasi', 'unitTujuan', 'user', 'attachments'])
                        ->latest('tanggal_diterima')
                        ->latest('id')
                        ->paginate(15);
                },
            ],
            [
                'name' => '2. Halaman Dalam / Deep Pagination (Halaman 500)',
                'action' => function () {
                    return SuratMasuk::with(['klasifikasi', 'unitTujuan', 'user', 'attachments'])
                        ->latest('tanggal_diterima')
                        ->latest('id')
                        ->paginate(15, ['*'], 'page', 500);
                },
            ],
            [
                'name' => '3. Pencarian Kata Kunci (Keyword: Kementerian)',
                'action' => function () {
                    return SuratMasuk::with(['klasifikasi', 'unitTujuan', 'user', 'attachments'])
                        ->filter(['search' => 'Kementerian'])
                        ->latest('tanggal_diterima')
                        ->latest('id')
                        ->paginate(15);
                },
            ],
            [
                'name' => '4. Filter Gabungan (Status: terdaftar & Sifat: penting)',
                'action' => function () {
                    return SuratMasuk::with(['klasifikasi', 'unitTujuan', 'user', 'attachments'])
                        ->filter(['status' => 'terdaftar', 'sifat' => 'penting'])
                        ->latest('tanggal_diterima')
                        ->latest('id')
                        ->paginate(15);
                },
            ],
            [
                'name' => '5. Filter Rentang Tanggal 30 Hari Terakhir',
                'action' => function () {
                    return SuratMasuk::with(['klasifikasi', 'unitTujuan', 'user', 'attachments'])
                        ->filter([
                            'start_date' => Carbon::now()->subDays(30)->toDateString(),
                            'end_date' => Carbon::now()->toDateString(),
                        ])
                        ->latest('tanggal_diterima')
                        ->latest('id')
                        ->paginate(15);
                },
            ],
        ];

        $resultsTable = [];
        $thresholdSeconds = 2.0;

        foreach ($tests as $t) {
            $start = microtime(true);
            $paginator = $t['action']();
            $elapsedSeconds = microtime(true) - $start;
            $elapsedMs = round($elapsedSeconds * 1000, 2);

            $passed = $elapsedSeconds < $thresholdSeconds;
            $statusStr = $passed ? 'MEMENUHI SYARAT (< 2s)' : 'GAGAL (>= 2s)';

            $resultsTable[] = [
                'Pengujian' => $t['name'],
                'Hasil Ditemukan' => number_format($paginator->total()).' naskah',
                'Waktu Eksekusi' => "{$elapsedMs} ms (".round($elapsedSeconds, 3).' s)',
                'Status Target PRD' => $statusStr,
            ];
        }

        $this->table(
            ['Skenario Pengujian', 'Total Ditemukan', 'Waktu Eksekusi Sebenarnya', 'Status (< 2 Detik)'],
            $resultsTable
        );

        // Jika driver PostgreSQL, jalankan EXPLAIN ANALYZE untuk 2 query utama
        if (DB::getDriverName() === 'pgsql') {
            $this->newLine();
            $this->info('=== EXPLAIN (ANALYZE, BUFFERS) DUA QUERY UTAMA PADA POSTGRESQL ===');

            $this->line("\n[Query 1: Filter Gabungan (status='terdaftar', sifat='penting')]:");
            $q1Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT id, nomor_agenda, perihal, status, sifat, tanggal_diterima
                FROM surat_masuk
                WHERE status = 'terdaftar' AND sifat = 'penting'
                ORDER BY tanggal_diterima DESC, id DESC
                LIMIT 15
            ");
            foreach ($q1Plan as $row) {
                $this->line('  '.$row->{'QUERY PLAN'});
            }

            $cases = [
                'Kasus 1: Kata Umum (Kementerian)' => 'Kementerian',
                'Kasus 2: Kata Langka < 20 baris (Dokumen #99999)' => 'Dokumen #99999',
                'Kasus 3: Kata Tidak Ada Hasil (KosongAbsolutXYZ999)' => 'KosongAbsolutXYZ999',
            ];

            foreach ($cases as $label => $keyword) {
                $this->line("\n[Query FTS GIN Index - {$label}]:");
                $plan = DB::select("
                    EXPLAIN (ANALYZE, BUFFERS)
                    SELECT id, nomor_agenda, perihal, tanggal_diterima
                    FROM surat_masuk
                    WHERE search_vector @@ plainto_tsquery('simple', '{$keyword}')
                    LIMIT 15
                ");
                foreach ($plan as $row) {
                    $this->line('  '.$row->{'QUERY PLAN'});
                }
            }
        }

        $this->info('Semua pengujian performa selesai.');

        return 0;
    }
}
