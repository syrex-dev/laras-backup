<?php

namespace App\Console\Commands;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkDisposisiCommand extends Command
{
    protected $signature = 'eoffice:benchmark-disposisi
                            {--surat-count=100000 : Jumlah total data surat masuk}
                            {--disposisi-count=200000 : Jumlah total data disposisi}
                            {--chunk=1000 : Ukuran batch insert}
                            {--measure-only : Hanya ukur performa tanpa seeding data baru}';

    protected $description = 'Menguji skala 100.000 surat + 200.000 disposisi dengan distribusi pengguna realistis, query EXPLAIN BUFFERS, dan waktu respons HTTP di PostgreSQL';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('PERINGATAN KEAMANAN: Command benchmark dilarang dijalankan di environment production!');
            return Command::FAILURE;
        }

        ini_set('memory_limit', '1536M');
        DB::disableQueryLog();

        $targetSurat = (int) $this->option('surat-count');
        $targetDisposisi = (int) $this->option('disposisi-count');
        $chunkSize = min((int) $this->option('chunk'), 1000);
        $measureOnly = $this->option('measure-only');

        $this->info('========================================================================');
        $this->info('  BENCHMARK PERFORMA KOTAK MASUK DISPOSISI (POSTGRESQL)');
        $this->info("  Target Skala: {$targetSurat} Surat Masuk + {$targetDisposisi} Disposisi");
        $this->info('========================================================================');

        // Daftar pengguna untuk distribusi penerima realistis instansi
        $heavyUser = User::where('email', 'kajur.jtk@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Kepala Unit'))->first()
            ?? User::first();

        $user2 = User::where('email', 'kaprodi.ti@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Staf'))->first()
            ?? $heavyUser;

        $user3 = User::where('email', 'direktur@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Pimpinan'))->first()
            ?? $heavyUser;

        $user4 = User::where('email', 'wadir1@contoh.ac.id')->first()
            ?? User::where('id', '!=', $user3->id)->first()
            ?? $heavyUser;

        $user5 = User::where('email', 'tu@contoh.ac.id')->first()
            ?? User::where('id', '!=', $user2->id)->first()
            ?? $heavyUser;

        $recipientsPool = [
            $heavyUser, $user2, $user3, $user4, $user5
        ];

        $currentSuratCount = SuratMasuk::count();
        $currentDisposisiCount = Disposisi::count();

        $this->line("Data Surat Masuk saat ini: " . number_format($currentSuratCount));
        $this->line("Data Disposisi saat ini  : " . number_format($currentDisposisiCount));

        // 1. Pembuatan Data Uji Tambahan Jika Kurang
        if (! $measureOnly) {
            $senderUser = $user3; // Direktur / Pimpinan sebagai pembuat disposisi
            $klasifikasi = KlasifikasiSurat::first() ?? KlasifikasiSurat::create([
                'kode_klasifikasi' => 'UM.01',
                'nama' => 'Umum & Tata Usaha',
                'retensi_aktif' => 1,
                'retensi_inaktif' => 2,
            ]);
            $unit = OrgUnit::first();
            $pos = Position::first();

            // A. Seed Surat Masuk hingga targetSurat
            if ($currentSuratCount < $targetSurat) {
                $neededSurat = $targetSurat - $currentSuratCount;
                $this->info("\n[1/2] Menghasilkan " . number_format($neededSurat) . " surat masuk tambahan...");
                $bar = $this->output->createProgressBar($neededSurat);
                $bar->start();

                $inserted = 0;
                while ($inserted < $neededSurat) {
                    $batchSize = min($chunkSize, $neededSurat - $inserted);
                    $rows = [];
                    $now = now()->toDateTimeString();

                    for ($i = 0; $i < $batchSize; $i++) {
                        $idx = $currentSuratCount + $inserted + $i + 1;
                        $rows[] = [
                            'nomor_agenda' => sprintf('%06d/SM/%s', $idx, date('Y')),
                            'nomor_surat' => sprintf('B-%06d/ORG/%s', $idx, date('Y')),
                            'tanggal_surat' => '2026-03-01',
                            'tanggal_diterima' => '2026-03-02',
                            'pengirim' => ($idx % 2 === 0) ? 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi' : 'Direktorat Jenderal Pendidikan Vokasi',
                            'perihal' => ($idx === 99999) ? 'Dokumen #99999 Perihal Sangat Langka' : "Naskah Dinas Surat Masuk Koordinasi Teknis #{$idx}",
                            'ringkasan' => "Ringkasan naskah dinas koordinasi kelembagaan nomor urut {$idx}.",
                            'status' => 'terdaftar',
                            'sifat' => ($idx % 5 === 0) ? 'sangat_rahasia' : (($idx % 3 === 0) ? 'penting' : 'biasa'),
                            'klasifikasi_id' => $klasifikasi->id,
                            'unit_tujuan_id' => $unit?->id,
                            'user_id' => $senderUser->id,
                            'qr_token' => Str::random(32),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table('surat_masuk')->insert($rows);
                    $inserted += $batchSize;
                    $bar->advance($batchSize);
                }
                $bar->finish();
                $this->newLine();
            }

            // B. Seed Disposisi + Disposisi Recipients hingga targetDisposisi (Distribusi Realistis)
            if ($currentDisposisiCount < $targetDisposisi) {
                $neededDisposisi = $targetDisposisi - $currentDisposisiCount;
                $this->info("\n[2/2] Menghasilkan " . number_format($neededDisposisi) . " disposisi & penerima tambahan...");
                $bar = $this->output->createProgressBar($neededDisposisi);
                $bar->start();

                $suratIds = DB::table('surat_masuk')->select('id')->orderBy('id', 'asc')->limit(100000)->pluck('id')->toArray();
                if (empty($suratIds)) {
                    $suratIds = [1];
                }
                $suratCount = count($suratIds);
                $inserted = 0;

                while ($inserted < $neededDisposisi) {
                    $batchSize = min($chunkSize, $neededDisposisi - $inserted);
                    $dispRows = [];
                    $recRows = [];
                    $now = now()->toDateTimeString();

                    for ($i = 0; $i < $batchSize; $i++) {
                        $idx = $currentDisposisiCount + $inserted + $i + 1;
                        $suratId = $suratIds[$i % $suratCount];

                        // Distribusi Realistis:
                        // 0..49 (50%) -> Heavy User (Kajur JTK)
                        // 50..74 (25%) -> User 2 (Kaprodi D4 TI)
                        // 75..84 (10%) -> User 3 (Direktur)
                        // 85..94 (10%) -> User 4 (Wadir 1)
                        // 95..99 (5%)  -> User 5 (Staf TU)
                        $mod = $idx % 100;
                        if ($mod < 50) {
                            $targetRecipient = $heavyUser;
                        } elseif ($mod < 75) {
                            $targetRecipient = $user2;
                        } elseif ($mod < 85) {
                            $targetRecipient = $user3;
                        } elseif ($mod < 95) {
                            $targetRecipient = $user4;
                        } else {
                            $targetRecipient = $user5;
                        }

                        $dispRows[] = [
                            'id' => $idx,
                            'surat_masuk_id' => $suratId,
                            'pengirim_id' => $senderUser->id,
                            'penerima_id' => $targetRecipient->id,
                            'pemberi_id' => $senderUser->id,
                            'pemberi_position_id' => $senderUser->position_id,
                            'parent_disposisi_id' => null,
                            'instruksi' => ($idx % 3 === 0) ? 'Tindak lanjuti dan koordinasikan dengan tim teknis.' : 'Pelajari naskah dan siapkan telaah staf.',
                            'catatan' => 'Catatan disposisi percepatan pelaksanaan.',
                            'prioritas' => ($idx % 3 === 0) ? 'sangat_segera' : (($idx % 2 === 0) ? 'segera' : 'biasa'),
                            'status' => 'diproses',
                            'tenggat_waktu' => now()->addDays(($idx % 10) - 2)->toDateTimeString(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $statuses = ['belum_dibaca', 'dibaca', 'diproses', 'selesai'];
                        $recStatus = $statuses[$idx % 4];

                        $recRows[] = [
                            'disposisi_id' => $idx,
                            'recipient_user_id' => $targetRecipient->id,
                            'recipient_position_id' => $targetRecipient->position_id,
                            'parent_recipient_id' => null,
                            'status' => $recStatus,
                            'is_delegated' => false,
                            'delegated_user_id' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table('disposisi')->insert($dispRows);
                    DB::table('disposisi_recipients')->insert($recRows);
                    $inserted += $batchSize;
                    $bar->advance($batchSize);
                }
                $bar->finish();
                $this->newLine();
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('disposisi', 'id'), coalesce(max(id), 1)) FROM disposisi");
                DB::statement("SELECT setval(pg_get_serial_sequence('disposisi_recipients', 'id'), coalesce(max(id), 1)) FROM disposisi_recipients");
                DB::statement("SELECT setval(pg_get_serial_sequence('surat_masuk', 'id'), coalesce(max(id), 1)) FROM surat_masuk");
            }
        }

        $totalSurat = SuratMasuk::count();
        $totalDisp = Disposisi::count();
        $totalRec = DisposisiRecipient::count();

        $this->info("\n=== VERIFIKASI POPULASI DATA AKTUAL ===");
        $this->line("Total Surat Masuk        : " . number_format($totalSurat));
        $this->line("Total Disposisi          : " . number_format($totalDisp));
        $this->line("Total Penerima Disposisi : " . number_format($totalRec));

        // Tampilkan Tabel Distribusi Realistis Penerima
        $this->newLine();
        $this->info("=== DISTRIBUSI REALISTIS PENERIMA DISPOSISI (SKALA {$totalDisp} DISPOSISI) ===");
        $distRows = [];
        $uniqueUsers = collect($recipientsPool)->unique('id');

        foreach ($uniqueUsers as $u) {
            $totalUserRec = DisposisiRecipient::where('recipient_user_id', $u->id)->count();
            if ($totalUserRec === 0) continue;

            $pct = $totalRec > 0 ? round(($totalUserRec / $totalRec) * 100, 1) : 0;
            $unread = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'belum_dibaca')->count();
            $read = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'dibaca')->count();
            $processing = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'diproses')->count();
            $done = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'selesai')->count();

            $isHeavy = ($u->id === $heavyUser->id) ? ' (HEAVY USER / TERPADAT)' : '';

            $distRows[] = [
                'Nama & Jabatan' => $u->name . $isHeavy,
                'Email' => $u->email,
                'Total Disposisi' => number_format($totalUserRec),
                'Beban (%)' => "{$pct}%",
                'Belum Dibaca' => number_format($unread),
                'Dibaca' => number_format($read),
                'Diproses' => number_format($processing),
                'Selesai' => number_format($done),
            ];
        }

        $this->table(
            ['Nama & Jabatan', 'Email', 'Total Disposisi', 'Beban (%)', 'Belum Dibaca', 'Dibaca', 'Diproses', 'Selesai'],
            $distRows
        );

        // 2. EXPLAIN (ANALYZE, BUFFERS) Query Kotak Masuk Disposisi di PostgreSQL
        $testUser = $heavyUser;

        if (DB::getDriverName() === 'pgsql') {
            $this->newLine();
            $this->info("=== EXPLAIN (ANALYZE, BUFFERS) QUERY KOTAK MASUK PENGGUNA TERPADAT (POSTGRESQL) ===");
            $this->line("Target Pengujian User: {$testUser->name} (ID: {$testUser->id}, Beban Terbesar)");

            // Query 1: Filter Belum Dibaca (Inbox Utama)
            $this->line("\n[Query 1: Kotak Masuk Belum Dibaca - Filter recipient_user_id + status='belum_dibaca']");
            $q1Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT r.id, r.disposisi_id, r.status, r.created_at
                FROM disposisi_recipients r
                WHERE (r.recipient_user_id = {$testUser->id} OR r.delegated_user_id = {$testUser->id})
                  AND r.status = 'belum_dibaca'
                ORDER BY r.id DESC
                LIMIT 15
            ");
            foreach ($q1Plan as $row) {
                $this->line('  ' . $row->{'QUERY PLAN'});
            }

            // Query 2: Filter Prioritas Sangat Segera + Diproses
            $this->line("\n[Query 2: Filter Gabungan - Disposisi Prioritas 'sangat_segera' + Status 'diproses']");
            $q2Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT r.id, r.disposisi_id, r.status, d.prioritas
                FROM disposisi_recipients r
                JOIN disposisi d ON d.id = r.disposisi_id
                WHERE (r.recipient_user_id = {$testUser->id} OR r.delegated_user_id = {$testUser->id})
                  AND d.prioritas = 'sangat_segera'
                  AND r.status = 'diproses'
                ORDER BY r.id DESC
                LIMIT 15
            ");
            foreach ($q2Plan as $row) {
                $this->line('  ' . $row->{'QUERY PLAN'});
            }

            // Query 3: Pencarian Perihal Surat Masuk pada Kotak Masuk Disposisi
            $this->line("\n[Query 3: Pencarian Perihal Surat Masuk pada Kotak Masuk Disposisi]");
            $q3Plan = DB::select("
                EXPLAIN (ANALYZE, BUFFERS)
                SELECT r.id, r.disposisi_id, sm.perihal, r.status
                FROM disposisi_recipients r
                JOIN disposisi d ON d.id = r.disposisi_id
                JOIN surat_masuk sm ON sm.id = d.surat_masuk_id
                WHERE (r.recipient_user_id = {$testUser->id} OR r.delegated_user_id = {$testUser->id})
                  AND sm.perihal LIKE '%Koordinasi Teknis%'
                ORDER BY r.id DESC
                LIMIT 15
            ");
            foreach ($q3Plan as $row) {
                $this->line('  ' . $row->{'QUERY PLAN'});
            }
        }

        // 3. Pengukuran Waktu Respons HTTP Nyata (Middleware + Policy + Inertia + Render)
        $this->newLine();
        $this->info("=== PENGUKURAN WAKTU RESPONS HTTP NYATA PADA BEBAN 100K SURAT + 200K DISPOSISI ===");

        Auth::login($testUser);
        $kernel = app(Kernel::class);

        $httpScenarios = [
            [
                'name' => '1. Kotak Masuk Halaman 1 (Default)',
                'uri' => '/disposisi?tab=inbox',
            ],
            [
                'name' => '2. Kotak Masuk dengan Filter (Status: Belum Dibaca, Prioritas: Sangat Segera)',
                'uri' => '/disposisi?tab=inbox&status=belum_dibaca&prioritas=sangat_segera',
            ],
            [
                'name' => '3. Kotak Masuk dengan Pencarian Perihal (search=Koordinasi Teknis)',
                'uri' => '/disposisi?tab=inbox&search=Koordinasi+Teknis',
            ],
            [
                'name' => '4. Tab Terkirim (Sent Disposisi)',
                'uri' => '/disposisi?tab=sent',
            ],
        ];

        // Warmup HTTP request
        $warmupReq = Request::create('/disposisi?tab=inbox', 'GET');
        $warmupReq->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml');
        $warmupRes = $kernel->handle($warmupReq);
        $kernel->terminate($warmupReq, $warmupRes);

        $httpResults = [];
        $thresholdSeconds = 2.0;

        foreach ($httpScenarios as $sc) {
            $req = Request::create($sc['uri'], 'GET');
            $req->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml');

            $start = microtime(true);
            $res = $kernel->handle($req);
            $elapsedSeconds = microtime(true) - $start;
            $elapsedMs = round($elapsedSeconds * 1000, 2);

            $status = $res->getStatusCode();
            $contentLen = strlen($res->getContent());
            $sizeKb = round($contentLen / 1024, 2);

            $passed = $elapsedSeconds < $thresholdSeconds;
            $statusStr = $passed ? 'MEMENUHI SYARAT (< 2s)' : 'GAGAL (>= 2s)';

            $httpResults[] = [
                'Skenario HTTP' => $sc['name'],
                'HTTP Status' => $status,
                'Ukuran Response' => "{$sizeKb} KB",
                'Waktu Nyata (Total)' => "{$elapsedMs} ms (" . round($elapsedSeconds, 3) . " s)",
                'Status Target' => $statusStr,
            ];

            $kernel->terminate($req, $res);
        }

        $this->table(
            ['Skenario HTTP', 'Status Code', 'Ukuran Response', 'Waktu Respons Nyata', 'Target PRD (< 2 Detik)'],
            $httpResults
        );

        $this->info("Benchmark kotak masuk disposisi selesai dengan sukses.");
        return Command::SUCCESS;
    }
}
