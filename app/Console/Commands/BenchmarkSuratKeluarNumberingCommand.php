<?php

namespace App\Console\Commands;

use App\Domain\SuratKeluar\Services\NomorSuratKeluarService;
use App\Domain\SuratMasuk\Models\NumberingSequence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class BenchmarkSuratKeluarNumberingCommand extends Command
{
    protected $signature = 'app:benchmark-surat-keluar-numbering
                            {--processes=10 : Jumlah proses paralel independen}
                            {--per-process=3 : Jumlah nomor per proses}
                            {--unit=PARALLEL_SK : Kode unit pengujian}
                            {--year=2026 : Tahun penomoran}
                            {--worker : Flag internal untuk mode worker proses anak}
                            {--test-rollback : Jalankan verifikasi rollback transaksi}';

    protected $description = 'Uji konkurensi penomoran otomatis surat keluar dengan 10+ proses paralel dan verifikasi rollback pada PostgreSQL';

    public function handle(NomorSuratKeluarService $service): int
    {
        if (app()->isProduction()) {
            $this->error('PERINGATAN KEAMANAN: Command pengujian konkurensi dilarang dijalankan di environment production!');

            return Command::FAILURE;
        }

        $year = (int) $this->option('year');
        $unit = (string) $this->option('unit');
        $perProcess = (int) $this->option('per-process');

        // Mode Worker (dijalankan oleh masing-masing proses anak)
        if ($this->option('worker')) {
            $pattern = '{urut:4}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}';
            for ($i = 0; $i < $perProcess; $i++) {
                $seqVal = DB::transaction(function () use ($year, $unit, $pattern) {
                    return NumberingSequence::nextVal(
                        type: 'surat_keluar',
                        year: $year,
                        unitCode: $unit,
                        prefix: 'SK',
                        formatPattern: $pattern
                    );
                });

                $formatted = $service->formatNomor($seqVal, [
                    'kode_unit' => $unit,
                    'kode_klasifikasi' => 'OT.01',
                    'bulan_romawi' => 'X',
                    'bulan' => '10',
                    'tahun' => $year,
                ], $pattern);

                $this->line($formatted);
                // Jitter mikro untuk mensimulasikan latensi I/O jaringan nyata
                usleep(random_int(1000, 5000));
            }

            return Command::SUCCESS;
        }

        // Mode Master Coordinator
        $processCount = (int) $this->option('processes');
        $expectedTotal = $processCount * $perProcess;

        $this->info('========================================================================');
        $this->info('  BENCHMARK KONKURENSI PENOMORAN SURAT KELUAR (MULTI-PROCESS SYMFONY)  ');
        $this->info('========================================================================');
        $this->line('Koneksi DB        : '.config('database.default'));
        $this->line('Host / Port       : '.config('database.connections.'.config('database.default').'.host').':'.config('database.connections.'.config('database.default').'.port'));
        $this->line('Database          : '.config('database.connections.'.config('database.default').'.database'));
        $this->line("Jumlah Proses     : {$processCount} proses paralel independen");
        $this->line("Nomor per Proses  : {$perProcess} nomor");
        $this->line("Target Total      : {$expectedTotal} nomor surat keluar");
        $this->line("Unit / Tahun      : {$unit} / {$year}");
        $this->newLine();

        // 1. Reset sequence pengujian
        NumberingSequence::where('type', 'surat_keluar')
            ->where('year', $year)
            ->where('unit_code', $unit)
            ->delete();

        $this->line("1. Sequence {$unit}-{$year} di-reset ke 0.");

        // 2. Verifikasi Uji Rollback Transaksi
        $this->line('2. Menguji keamanan rollback transaksi (kegagalan transaksi tidak boleh membakar nomor)...');
        $rolledBackNumber = null;
        try {
            DB::transaction(function () use ($year, $unit) {
                $val = NumberingSequence::nextVal('surat_keluar', $year, $unit, 'SK');
                throw new \RuntimeException("Simulasi kegagalan transaksi setelah mengambil nomor {$val}");
            });
        } catch (\RuntimeException $e) {
            $this->comment("   [SIMULASI ERROR]: {$e->getMessage()}");
        }

        // Ambil nomor lagi pada transaksi baru
        $nextNumberAfterRollback = DB::transaction(function () use ($year, $unit) {
            return NumberingSequence::nextVal('surat_keluar', $year, $unit, 'SK');
        });

        $this->line("   Nomor setelah rollback: {$nextNumberAfterRollback}");
        if ($nextNumberAfterRollback === 1) {
            $this->info('   [VERIFIKASI ROLLBACK SUKSES] Nomor 1 dikembalikan secara atomik, tidak ada celah akibat transaksi gagal!');
        } else {
            $this->error("   [GAGAL] Nomor terbakar: diharapkan 1 tetapi mendapat {$nextNumberAfterRollback}");

            return Command::FAILURE;
        }

        // Reset lagi untuk pengujian konkurensi multi-proses
        NumberingSequence::where('type', 'surat_keluar')
            ->where('year', $year)
            ->where('unit_code', $unit)
            ->delete();

        // 3. Eksekusi Proses Paralel Sungguhan
        $this->newLine();
        $this->line("3. Meluncurkan {$processCount} proses paralel secara bersamaan...");

        $phpFinder = new \Symfony\Component\Process\PhpExecutableFinder;
        $phpBinary = $phpFinder->find() ?: PHP_BINARY;
        $artisanPath = base_path('artisan');

        /** @var Process[] $processes */
        $processes = [];

        // Teruskan environment database aktif ke proses anak
        $env = array_merge($_ENV, [
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host', '127.0.0.1'),
            'DB_PORT' => (string) config('database.connections.'.config('database.default').'.port', '5433'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
            'DB_USERNAME' => config('database.connections.'.config('database.default').'.username'),
            'DB_PASSWORD' => (string) config('database.connections.'.config('database.default').'.password'),
        ]);

        for ($p = 1; $p <= $processCount; $p++) {
            $cmd = [
                $phpBinary,
                $artisanPath,
                'app:benchmark-surat-keluar-numbering',
                '--worker',
                "--per-process={$perProcess}",
                "--unit={$unit}",
                "--year={$year}",
            ];

            $process = new Process($cmd, base_path(), $env);
            $process->setTimeout(60);
            $processes[] = $process;
        }

        $startTime = microtime(true);

        // Start all processes simultaneously
        foreach ($processes as $proc) {
            $proc->start();
        }

        // Wait for all processes to complete
        $allOutput = '';
        $failedCount = 0;
        foreach ($processes as $i => $proc) {
            $proc->wait();
            if (! $proc->isSuccessful()) {
                $failedCount++;
                $this->error("Proses #{$i} gagal: ".$proc->getErrorOutput());
            } else {
                $allOutput .= $proc->getOutput()."\n";
            }
        }

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);

        // 4. Analisis Hasil
        $lines = array_filter(array_map('trim', explode("\n", $allOutput)));
        $totalReceived = count($lines);
        $uniqueCount = count(array_unique($lines));

        $this->newLine();
        $this->info('========================================================================');
        $this->info('                     HASIL ANALISIS PENGUJIAN                           ');
        $this->info('========================================================================');
        $this->line("Waktu Eksekusi    : {$elapsed} ms");
        $this->line("Proses Berhasil   : ".($processCount - $failedCount)." / {$processCount}");
        $this->line("Nomor Dihasilkan  : {$totalReceived} / {$expectedTotal}");
        $this->line("Nomor Unik        : {$uniqueCount}");

        // Ekstrak angka urut
        $seqNumbers = [];
        foreach ($lines as $line) {
            if (preg_match('/^(\d+)\//', $line, $m)) {
                $seqNumbers[] = (int) $m[1];
            }
        }
        sort($seqNumbers);

        $gaps = [];
        $expectedSeq = range(1, $expectedTotal);
        $diff = array_diff($expectedSeq, $seqNumbers);

        $this->line('Sampel 5 nomor pertama : '.implode(', ', array_slice($lines, 0, 5)));
        $this->line('Sampel 5 nomor terakhir: '.implode(', ', array_slice($lines, -5)));

        if ($failedCount === 0 && $totalReceived === $expectedTotal && $uniqueCount === $expectedTotal && empty($diff)) {
            $this->newLine();
            $this->info('========================================================================');
            $this->info(' [SUKSES MUTLAK] SELURUH NOMOR UNIK, BERURUTAN TANPA CELAH (NO GAPS)!   ');
            $this->info('========================================================================');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->error('========================================================================');
        $this->error(' [GAGAL] DITEMUKAN DUPLIKASI ATAU CELAH (GAPS) PADA PENOMORAN!          ');
        $this->error('========================================================================');
        if (! empty($diff)) {
            $this->error('Nomor yang hilang/gap: '.implode(', ', array_slice($diff, 0, 10)));
        }

        return Command::FAILURE;
    }
}
