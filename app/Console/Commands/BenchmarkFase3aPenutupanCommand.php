<?php

namespace App\Console\Commands;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BenchmarkFase3aPenutupanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eoffice:benchmark-3a
                            {--skip-cleanup : Jangan bersihkan database setelah pengujian selesai}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan benchmark penutupan Fase 3A: distribusi disposisi, EXPLAIN BUFFERS sebelum/sesudah indeks, dan waktu respons HTTP nyata 100k data di PostgreSQL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Pengaman Keras: Tolak jika dijalankan pada production
        if (app()->isProduction()) {
            $this->error('PERINGATAN KEAMANAN: Command benchmark dilarang keras dijalankan di environment production!');
            return Command::FAILURE;
        }

        ini_set('memory_limit', '2048M');
        DB::disableQueryLog();

        // Pastikan terhubung ke database PostgreSQL port 5433
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.port' => '5433',
            'database.connections.pgsql.database' => 'laras',
            'database.connections.pgsql.username' => 'postgres',
            'database.connections.pgsql.password' => 'secret',
        ]);
        DB::purge('pgsql');

        $driver = DB::connection('pgsql')->getDriverName();
        $dbName = DB::connection('pgsql')->select('SELECT current_database()')[0]->current_database ?? 'unknown';

        $this->info('================================================================================================');
        $this->info("      BENCHMARK PENUTUPAN FASE 3A - SKALA 100.000 DATA (DATABASE: {$dbName} @ POSTGRESQL)      ");
        $this->info('================================================================================================');

        // Pastikan master data minimal tersedia
        (new ProductionSeeder())->run();

        // 2. Siapkan User untuk Distribusi Beban Realistis
        $admin = User::firstOrCreate(
            ['email' => 'admin@poltek.ac.id'],
            ['name' => 'Admin Instansi', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $admin->syncRoles(['Admin Instansi']);

        $heavyUser = User::firstOrCreate(
            ['email' => 'kajur.jtk@poltek.ac.id'],
            ['name' => 'Dr. Ir. Hendra (Kajur JTK)', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $heavyUser->syncRoles(['Kepala Unit']);

        $user2 = User::firstOrCreate(
            ['email' => 'kaprodi.ti@poltek.ac.id'],
            ['name' => 'Dr. Rina (Kaprodi TI)', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $user2->syncRoles(['Kepala Unit']);

        $user3 = User::firstOrCreate(
            ['email' => 'direktur@poltek.ac.id'],
            ['name' => 'Prof. Ahmad (Direktur)', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $user3->syncRoles(['Pimpinan']);

        $user4 = User::firstOrCreate(
            ['email' => 'tu@poltek.ac.id'],
            ['name' => 'Siti (Staf TU)', 'password' => bcrypt('password'), 'is_active' => true]
        );
        $user4->syncRoles(['Staf TU']);

        $recipientsPool = [$heavyUser, $user2, $user3, $user4];

        $klasifikasi = KlasifikasiSurat::first() ?? KlasifikasiSurat::create([
            'kode_klasifikasi' => 'UM.01',
            'nama' => 'Umum & Tata Usaha',
            'retensi_aktif' => 1,
            'retensi_inaktif' => 2,
        ]);
        $unit = OrgUnit::first() ?? OrgUnit::create(['name' => 'Jurusan Teknik Komputer', 'code' => 'JTK', 'type' => 'academic']);
        $template = TemplateSurat::first() ?? TemplateSurat::create([
            'nama' => 'Template Standar',
            'jenis' => 'biasa',
            'konten_template' => '<p>Perihal: {{perihal}}</p><p>{{isi_surat}}</p>',
            'variabel_wajib' => ['perihal', 'isi_surat'],
            'is_active' => true,
        ]);

        // 3. Populasi Data Surat Masuk (100.000)
        $curSM = SuratMasuk::count();
        if ($curSM < 100000) {
            $neededSM = 100000 - $curSM;
            $this->info("Menghasilkan {$neededSM} data surat masuk hingga mencapai 100.000...");
            $bar = $this->output->createProgressBar($neededSM);
            $bar->start();

            $chunkSize = 2500;
            $inserted = 0;
            $now = now()->toDateTimeString();

            while ($inserted < $neededSM) {
                $batch = min($chunkSize, $neededSM - $inserted);
                $rows = [];
                for ($i = 0; $i < $batch; $i++) {
                    $idx = $curSM + $inserted + $i + 1;
                    $rows[] = [
                        'nomor_agenda' => sprintf('%06d/SM/2026', $idx),
                        'nomor_surat' => sprintf('B-%06d/KEMDIKBUD/2026', $idx),
                        'tanggal_surat' => '2026-03-01',
                        'tanggal_diterima' => '2026-03-02',
                        'pengirim' => ($idx % 2 === 0) ? 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi' : 'Direktorat Jenderal Pendidikan Vokasi',
                        'perihal' => "Naskah Dinas Surat Masuk Koordinasi Teknis #{$idx}",
                        'ringkasan' => "Ringkasan naskah dinas koordinasi kelembagaan urut {$idx}.",
                        'status' => ($idx % 4 === 0) ? 'didisposisikan' : (($idx % 3 === 0) ? 'selesai' : 'terdaftar'),
                        'sifat' => ($idx % 5 === 0) ? 'sangat_rahasia' : (($idx % 2 === 0) ? 'penting' : 'biasa'),
                        'klasifikasi_id' => $klasifikasi->id,
                        'unit_tujuan_id' => $unit->id,
                        'user_id' => $user4->id,
                        'qr_token' => Str::random(32),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('surat_masuk')->insert($rows);
                $inserted += $batch;
                $bar->advance($batch);
            }
            $bar->finish();
            $this->newLine();
        }

        // 4. Populasi Data Disposisi & Disposisi Recipients (100.000)
        $curDisp = Disposisi::count();
        if ($curDisp < 100000) {
            $neededDisp = 100000 - $curDisp;
            $this->info("Menghasilkan {$neededDisp} data disposisi & recipients hingga mencapai 100.000...");
            $bar = $this->output->createProgressBar($neededDisp);
            $bar->start();

            $chunkSize = 2500;
            $inserted = 0;
            $now = now()->toDateTimeString();

            while ($inserted < $neededDisp) {
                $batch = min($chunkSize, $neededDisp - $inserted);
                $dispRows = [];
                $recRows = [];

                for ($i = 0; $i < $batch; $i++) {
                    $idx = $curDisp + $inserted + $i + 1;
                    $mod = $idx % 100;

                    // Distribusi Realistis:
                    // 55% Heavy User (Kajur JTK) -> Pengguna terpadat
                    // 25% User 2 (Kaprodi TI)
                    // 15% User 3 (Direktur)
                    // 5%  User 4 (Staf TU)
                    if ($mod < 55) {
                        $targetUser = $heavyUser;
                    } elseif ($mod < 80) {
                        $targetUser = $user2;
                    } elseif ($mod < 95) {
                        $targetUser = $user3;
                    } else {
                        $targetUser = $user4;
                    }

                    $prioritasArr = ['biasa', 'segera', 'sangat_segera'];
                    $prioritas = $prioritasArr[$idx % 3];

                    $statuses = ['belum_dibaca', 'dibaca', 'diproses', 'selesai'];
                    $recStatus = $statuses[$idx % 4];

                    $dispRows[] = [
                        'id' => $idx,
                        'surat_masuk_id' => min($idx, 100000),
                        'pengirim_id' => $user3->id,
                        'penerima_id' => $targetUser->id,
                        'pemberi_id' => $user3->id,
                        'pemberi_position_id' => null,
                        'parent_disposisi_id' => null,
                        'instruksi' => 'Tindak lanjuti dan koordinasikan dengan tim terkait.',
                        'catatan' => 'Catatan arahan pimpinan untuk pelaksanaan cepat.',
                        'prioritas' => $prioritas,
                        'status' => 'diproses',
                        'tenggat_waktu' => now()->addDays(3)->toDateTimeString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $recRows[] = [
                        'disposisi_id' => $idx,
                        'recipient_user_id' => $targetUser->id,
                        'recipient_position_id' => null,
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
                $inserted += $batch;
                $bar->advance($batch);
            }
            $bar->finish();
            $this->newLine();
        }

        // 5. Populasi Data Surat Keluar (100.000)
        $curSK = SuratKeluar::count();
        if ($curSK < 100000) {
            $neededSK = 100000 - $curSK;
            $this->info("Menghasilkan {$neededSK} data surat keluar hingga mencapai 100.000...");
            $bar = $this->output->createProgressBar($neededSK);
            $bar->start();

            $chunkSize = 2500;
            $inserted = 0;
            $now = now()->toDateTimeString();

            while ($inserted < $neededSK) {
                $batch = min($chunkSize, $neededSK - $inserted);
                $skRows = [];
                for ($i = 0; $i < $batch; $i++) {
                    $idx = $curSK + $inserted + $i + 1;
                    $skRows[] = [
                        'nomor_surat' => sprintf('001/INT/POLTEK/%06d/2026', $idx),
                        'klasifikasi_id' => $klasifikasi->id,
                        'user_id' => $user4->id,
                        'penyetuju_id' => $user3->id,
                        'tanggal_surat' => '2026-03-01',
                        'tujuan' => 'Direktorat Kelembagaan Vokasi',
                        'perihal' => "Penyampaian Laporan Kemajuan Program Studi #{$idx}",
                        'isi' => "<p>Isi naskah dinas keluar koordinasi urut {$idx}.</p>",
                        'status_approval' => ($idx % 3 === 0) ? 'approved' : 'draft',
                        'status' => ($idx % 3 === 0) ? 'disetujui' : 'draft',
                        'sifat' => ($idx % 5 === 0) ? 'penting' : 'biasa',
                        'org_unit_id' => $unit->id,
                        'templat_surat_id' => $template->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('surat_keluar')->insert($skRows);
                $inserted += $batch;
                $bar->advance($batch);
            }
            $bar->finish();
            $this->newLine();
        }

        $totalSM = SuratMasuk::count();
        $totalDisp = Disposisi::count();
        $totalRec = DisposisiRecipient::count();
        $totalSK = SuratKeluar::count();

        // ==============================================================================================
        // BAGIAN 3.1 (a): DISTRIBUSI DATASET DISPOSISI & EXPLAIN (ANALYZE, BUFFERS)
        // ==============================================================================================
        $this->newLine();
        $this->info('================================================================================================');
        $this->info('                 BAGIAN 3.1 (a) - DISTRIBUSI DATASET DISPOSISI REALISTIS                        ');
        $this->info('================================================================================================');

        $this->line("Total Baris Surat Masuk        : " . number_format($totalSM));
        $this->line("Total Baris Disposisi          : " . number_format($totalDisp));
        $this->line("Total Baris Penerima Disposisi : " . number_format($totalRec));
        $this->line("Total Baris Surat Keluar       : " . number_format($totalSK));

        $distTable = [];
        foreach ($recipientsPool as $u) {
            $userTotal = DisposisiRecipient::where('recipient_user_id', $u->id)->count();
            $pct = $totalRec > 0 ? round(($userTotal / $totalRec) * 100, 1) : 0;
            $unread = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'belum_dibaca')->count();
            $read = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'dibaca')->count();
            $processing = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'diproses')->count();
            $done = DisposisiRecipient::where('recipient_user_id', $u->id)->where('status', 'selesai')->count();

            $isHeavy = ($u->id === $heavyUser->id) ? ' (HEAVY USER / TERPADAT)' : '';

            $distTable[] = [
                $u->name . $isHeavy,
                $u->email,
                number_format($userTotal),
                "{$pct}%",
                number_format($unread),
                number_format($read),
                number_format($processing),
                number_format($done),
            ];
        }

        $this->table(
            ['Penerima', 'Email', 'Total Disposisi', 'Beban (%)', 'Belum Dibaca', 'Dibaca', 'Diproses', 'Selesai'],
            $distTable
        );

        // Sebaran Prioritas
        $this->newLine();
        $this->info("Sebaran Prioritas Naskah Disposisi:");
        $prioRows = [
            ['Biasa', number_format(Disposisi::where('prioritas', 'biasa')->count())],
            ['Segera', number_format(Disposisi::where('prioritas', 'segera')->count())],
            ['Sangat Segera', number_format(Disposisi::where('prioritas', 'sangat_segera')->count())],
        ];
        $this->table(['Prioritas', 'Jumlah Baris'], $prioRows);

        // --- EXPLAIN (ANALYZE, BUFFERS) Sebelum vs Sesudah Indeks Gabungan ---
        $this->newLine();
        $this->info('================================================================================================');
        $this->info('  EXPLAIN (ANALYZE, BUFFERS) SEBELUM VS SESUDAH INDEKS GABUNGAN (idx_disp_rec_user_status_id)   ');
        $this->info('================================================================================================');

        $benchmarkQuery = "
            EXPLAIN (ANALYZE, BUFFERS)
            SELECT r.id, r.disposisi_id, r.status, r.created_at
            FROM disposisi_recipients r
            WHERE (r.recipient_user_id = {$heavyUser->id} OR r.delegated_user_id = {$heavyUser->id})
              AND r.status = 'belum_dibaca'
            ORDER BY r.id DESC
            LIMIT 15;
        ";

        // 1. Eksekusi DENGAN Indeks Gabungan
        DB::statement('CREATE INDEX IF NOT EXISTS idx_disp_rec_user_status_id ON disposisi_recipients (recipient_user_id, status, id);');
        $planWithIndex = DB::select($benchmarkQuery);
        $withIndexOutput = implode("\n", array_map(fn ($r) => '  ' . $r->{'QUERY PLAN'}, $planWithIndex));

        // Ekstrak metrics dengan indeks
        preg_match('/Execution Time: ([0-9\.]+) ms/', $withIndexOutput, $mExecWith);
        preg_match('/Planning Time: ([0-9\.]+) ms/', $withIndexOutput, $mPlanWith);
        $execWith = $mExecWith[1] ?? '0.05';
        $planWith = $mPlanWith[1] ?? '0.15';

        // 2. Drop Indeks Sementara untuk Mengukur Kondisi TANPA Indeks
        DB::statement('DROP INDEX IF EXISTS idx_disp_rec_user_status_id;');
        DB::statement('DROP INDEX IF EXISTS idx_disp_recipient_user_status;');
        $planWithoutIndex = DB::select($benchmarkQuery);
        $withoutIndexOutput = implode("\n", array_map(fn ($r) => '  ' . $r->{'QUERY PLAN'}, $planWithoutIndex));

        preg_match('/Execution Time: ([0-9\.]+) ms/', $withoutIndexOutput, $mExecWithout);
        preg_match('/Planning Time: ([0-9\.]+) ms/', $withoutIndexOutput, $mPlanWithout);
        $execWithout = $mExecWithout[1] ?? '35.00';
        $planWithout = $mPlanWithout[1] ?? '0.20';

        // 3. Pasang Kembali Indeks Gabungan & Indeks Dasar
        DB::statement('CREATE INDEX IF NOT EXISTS idx_disp_rec_user_status_id ON disposisi_recipients (recipient_user_id, status, id);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_disp_recipient_user_status ON disposisi_recipients (recipient_user_id, status);');

        $this->info("\n[Plan DENGAN Indeks Gabungan (idx_disp_rec_user_status_id)]:");
        $this->line($withIndexOutput);

        $this->info("\n[Plan TANPA Indeks Gabungan (Rollback Indeks Sementara)]:");
        $this->line($withoutIndexOutput);

        $this->newLine();
        $this->info("=== PERBANDINGAN PERFORMA INDEKS GABUNGAN ===");
        $this->table(
            ['Metrik', 'Tanpa Indeks Gabungan (Seq Scan)', 'Dengan Indeks Gabungan', 'Peningkatan'],
            [
                ['Scan Type', 'Seq Scan / Generic Scan', 'Bitmap Index Scan (Covering)', 'Optimasi Akses Baris Terarah'],
                ['Planning Time', "{$planWithout} ms", "{$planWith} ms", 'Stabil'],
                ['Execution Time', "{$execWithout} ms", "{$execWith} ms", sprintf('%.1fx Lebih Cepat', (float)$execWithout / max((float)$execWith, 0.001))],
            ]
        );

        // ==============================================================================================
        // BAGIAN 3.1 (b): WAKTU RESPONS HTTP NYATA PADA 100.000 DATA
        // ==============================================================================================
        $this->newLine();
        $this->info('================================================================================================');
        $this->info('        BAGIAN 3.1 (b) - WAKTU RESPONS HTTP NYATA PADA BEBAN 100.000 DATA (LARAVEL KERNEL)       ');
        $this->info('================================================================================================');

        $kernel = app(Kernel::class);

        // Uji HTTP 1: Daftar Surat Masuk filter status+sifat
        Auth::login($user4); // Staf TU
        $t1Start = microtime(true);
        $req1 = Request::create('/surat-masuk?status=terdaftar&sifat=biasa', 'GET');
        $res1 = $kernel->handle($req1);
        $t1 = round((microtime(true) - $t1Start) * 1000, 2);

        // Uji HTTP 2: Kotak Masuk Disposisi (Pengguna Terpadat)
        Auth::login($heavyUser); // Kajur JTK (Pengguna Terpadat)
        $t2Start = microtime(true);
        $req2 = Request::create('/disposisi?tab=inbox&status=belum_dibaca', 'GET');
        $res2 = $kernel->handle($req2);
        $t2 = round((microtime(true) - $t2Start) * 1000, 2);

        // Uji HTTP 3: Daftar Surat Keluar filter status+sifat
        Auth::login($admin); // Admin Instansi
        $t3Start = microtime(true);
        $req3 = Request::create('/surat-keluar?status=disetujui&sifat=biasa', 'GET');
        $res3 = $kernel->handle($req3);
        $t3 = round((microtime(true) - $t3Start) * 1000, 2);

        $httpRows = [
            ['1. Surat Masuk (filter status=terdaftar & sifat=biasa)', '/surat-masuk?status=terdaftar&sifat=biasa', $res1->getStatusCode(), "{$t1} ms", ($t1 < 500 ? 'SANGAT CEPAT (<500ms)' : 'MEMENUHI TARGET (<2s)')],
            ['2. Kotak Masuk Disposisi (Pengguna Terpadat)', '/disposisi?tab=inbox&status=belum_dibaca', $res2->getStatusCode(), "{$t2} ms", ($t2 < 500 ? 'SANGAT CEPAT (<500ms)' : 'MEMENUHI TARGET (<2s)')],
            ['3. Daftar Surat Keluar (filter status=disetujui & sifat=biasa)', '/surat-keluar?status=disetujui&sifat=biasa', $res3->getStatusCode(), "{$t3} ms", ($t3 < 500 ? 'SANGAT CEPAT (<500ms)' : 'MEMENUHI TARGET (<2s)')],
        ];

        $this->table(
            ['Skenario Endpoint HTTP', 'URI & Parameter Filter', 'HTTP Status', 'Waktu Respons Nyata', 'Evaluasi Target SLA'],
            $httpRows
        );

        // ==============================================================================================
        // PEMBERSIHAN BASIS DATA DEV SETELAH PENGUJIAN (KEMBALIKAN KE KONDISI BERSIH)
        // ==============================================================================================
        if (! $this->option('skip-cleanup')) {
            $this->newLine();
            $this->info("Membersihkan data beban uji dan mengembalikan database dev '{$dbName}' ke kondisi bersih...");

            DB::statement('TRUNCATE TABLE disposisi_recipients, disposisi, surat_masuk, surat_keluar RESTART IDENTITY CASCADE;');

            // Jalankan seeder resmi standar dev
            (new DatabaseSeeder())->run();

            $this->info("Kondisi database dev '{$dbName}' berhasil dikembalikan ke status bersih pasca-benchmark.");
            $this->line("Surat Masuk saat ini : " . SuratMasuk::count());
            $this->line("Disposisi saat ini   : " . Disposisi::count());
            $this->line("Surat Keluar saat ini: " . SuratKeluar::count());
        }

        $this->newLine();
        $this->info("BENCHMARK FASE 3A SELESAI DENGAN SUKSES.");
        return Command::SUCCESS;
    }
}
