<?php

namespace App\Console\Commands;

use App\Domain\SuratMasuk\Models\NumberingSequence;
use App\Domain\SuratMasuk\Services\AgendaNumberService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestAgendaConcurrencyCommand extends Command
{
    protected $signature = 'app:test-agenda-concurrency 
                            {--processes=10 : Jumlah proses paralel yang dijalankan serentak}
                            {--per-process=3 : Jumlah nomor yang di-generate per proses}
                            {--unit=PARALLEL_TEST : Kode unit untuk pengujian}
                            {--year=2026 : Tahun penomoran}
                            {--worker : Flag internal untuk mode worker proses anak}';

    protected $description = 'Uji konkurensi nomor agenda dengan minimal 10 proses paralel sungguhan pada PostgreSQL';

    public function handle(AgendaNumberService $service): int
    {
        $year = (int) $this->option('year');
        $unit = (string) $this->option('unit');
        $perProcess = (int) $this->option('per-process');

        // Mode Worker (dijalankan oleh masing-masing proses anak)
        if ($this->option('worker')) {
            for ($i = 0; $i < $perProcess; $i++) {
                $num = $service->generate($year, $unit);
                $this->line($num);
                // Beri jitter mikro untuk mensimulasikan jeda IO nyata
                usleep(random_int(1000, 5000));
            }

            return Command::SUCCESS;
        }

        // Mode Master Coordinator
        $processCount = (int) $this->option('processes');
        $expectedTotal = $processCount * $perProcess;

        $this->info('===================================================================');
        $this->info('   UJI KONKURENSI ATOMIK NOMOR AGENDA (MULTI-PROCESS SYMFONY)');
        $this->info('===================================================================');
        $this->line('Koneksi DB      : '.config('database.default'));
        $this->line("Jumlah Proses   : {$processCount} proses paralel independen");
        $this->line("Nomor per Proses: {$perProcess} nomor");
        $this->line("Target Total    : {$expectedTotal} nomor agenda");
        $this->line("Unit / Tahun    : {$unit} / {$year}");
        $this->newLine();

        // 1. Bersihkan sequence pengujian sebelumnya
        NumberingSequence::where('type', 'surat_masuk')
            ->where('year', $year)
            ->where('unit_code', $unit)
            ->delete();

        $this->line("1. Sequence {$unit}-{$year} berhasil di-reset ke 0.");

        // 2. Siapkan proses anak
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');

        /** @var Process[] $processes */
        $processes = [];

        // Teruskan environment database aktif ke proses anak
        $env = array_merge($_ENV, [
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host', '127.0.0.1'),
            'DB_PORT' => (string) config('database.connections.'.config('database.default').'.port', '5432'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
            'DB_USERNAME' => config('database.connections.'.config('database.default').'.username'),
            'DB_PASSWORD' => (string) config('database.connections.'.config('database.default').'.password'),
        ]);

        for ($p = 1; $p <= $processCount; $p++) {
            $cmd = [
                $phpBinary,
                $artisanPath,
                'app:test-agenda-concurrency',
                '--worker',
                "--per-process={$perProcess}",
                "--unit={$unit}",
                "--year={$year}",
            ];

            $process = new Process($cmd, base_path(), $env);
            $process->setTimeout(60);
            $processes[] = $process;
        }

        $this->line("2. Menjalankan {$processCount} proses secara serentak (non-blocking start)...");
        $startTime = microtime(true);

        // Start all processes simultaneously
        foreach ($processes as $process) {
            $process->start();
        }

        // Wait for all to finish
        $allOutputs = [];
        $failedCount = 0;

        foreach ($processes as $index => $process) {
            $process->wait();
            if (! $process->isSuccessful()) {
                $failedCount++;
                $this->error("Proses #{$index} GAGAL (Exit {$process->getExitCode()}):\nError: ".$process->getErrorOutput()."\nOut: ".$process->getOutput());
            } else {
                $outputLines = array_filter(array_map('trim', explode("\n", $process->getOutput())));
                foreach ($outputLines as $line) {
                    if (str_starts_with($line, 'SM-')) {
                        $allOutputs[] = $line;
                    }
                }
            }
        }

        $elapsed = round(microtime(true) - $startTime, 3);
        $this->info("3. Semua proses selesai dalam {$elapsed} detik.");
        $this->newLine();

        // 3. Analisis Hasil
        $totalReceived = count($allOutputs);
        $uniqueCount = count(array_unique($allOutputs));

        // Ekstrak angka urut
        $numbers = [];
        foreach ($allOutputs as $out) {
            if (preg_match('/SM-\d+-(\d+)/', $out, $matches)) {
                $numbers[] = (int) $matches[1];
            }
        }
        sort($numbers);

        $expectedNumbers = range(1, $expectedTotal);
        $hasGaps = ($numbers !== $expectedNumbers);

        $this->info('=== HASIL ANALISIS INTEGRITAS NOMOR ===');
        $this->table(
            ['Parameter', 'Target Diharapkan', 'Hasil Aktual', 'Status'],
            [
                ['Proses Gagal', '0', (string) $failedCount, $failedCount === 0 ? '✓ PASS' : '✗ FAIL'],
                ['Total Nomor Di-generate', (string) $expectedTotal, (string) $totalReceived, $totalReceived === $expectedTotal ? '✓ PASS' : '✗ FAIL'],
                ['Jumlah Nomor Unik', (string) $expectedTotal, (string) $uniqueCount, $uniqueCount === $expectedTotal ? '✓ PASS (TANPA DUPLIKAT)' : '✗ FAIL (ADA DUPLIKAT)'],
                ['Urutan Tanpa Celah (Gaps)', '1 s/d '.$expectedTotal, ($numbers[0] ?? 0).' s/d '.($numbers[count($numbers) - 1] ?? 0), ! $hasGaps ? '✓ PASS (BERURUTAN RAPI)' : '✗ FAIL (ADA CELAH)'],
            ]
        );

        $seq = NumberingSequence::where('type', 'surat_masuk')
            ->where('year', $year)
            ->where('unit_code', $unit)
            ->first();

        $this->line('Nilai last_number di tabel numbering_sequences: '.($seq?->last_number ?? 'null'));

        if ($failedCount === 0 && $uniqueCount === $expectedTotal && ! $hasGaps && $seq?->last_number === $expectedTotal) {
            $this->info('KESIMPULAN: atomic lockForUpdate() pada PostgreSQL TERBUKTI BEBAS RACE CONDITION.');

            return Command::SUCCESS;
        }

        $this->error('KESIMPULAN: Ditemukan kegagalan integritas nomor.');

        return Command::FAILURE;
    }
}
