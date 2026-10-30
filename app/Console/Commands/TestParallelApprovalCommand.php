<?php

namespace App\Console\Commands;

use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class TestParallelApprovalCommand extends Command
{
    protected $signature = 'app:test-parallel-approval
                            {--letter-id= : ID Surat Keluar yang diuji}
                            {--approver-id= : ID User Penyetuju}
                            {--worker : Flag internal untuk worker anak}';

    protected $description = 'Uji konkurensi persetujuan paralel di PostgreSQL untuk memastikan hanya satu proses yang berhasil menyetujui dan mengalokasikan nomor';

    public function handle(ApproveSuratKeluarAction $approveAction): int
    {
        if (app()->isProduction()) {
            $this->error('Dilarang dijalankan di production!');
            return Command::FAILURE;
        }

        // Mode Worker Anak
        if ($this->option('worker')) {
            $letterId = (int) $this->option('letter-id');
            $approverId = (int) $this->option('approver-id');

            $surat = SuratKeluar::findOrFail($letterId);
            $actor = User::findOrFail($approverId);

            try {
                // Eksekusi persetujuan dengan alokasi nomor otomatis
                $approveAction->execute($actor, $surat, 'Persetujuan via parallel worker', true);
                $this->line("SUCCESS: Letter {$letterId} approved by user {$approverId}");
                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $this->line("REJECTED: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Mode Coordinator
        $this->info("=== UJI PERSETUJUAN PARALEL POSTGRESQL ===");

        // Siapkan data pengujian jika tidak disediakan
        $creator = User::first() ?? User::factory()->create();
        $approver = User::where('id', '!=', $creator->id)->first() ?? User::factory()->create();
        $klasifikasi = KlasifikasiSurat::first() ?? KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);
        $template = TemplateSurat::first();

        $surat = SuratKeluar::create([
            'klasifikasi_id' => $klasifikasi->id,
            'user_id' => $creator->id,
            'penandatangan_id' => $approver->id,
            'template_id' => $template?->id,
            'perihal' => 'Uji Persetujuan Ganda Paralel',
            'tujuan' => 'Mitra Industri',
            'isi_surat' => '<p>Pengujian konkurensi approval.</p>',
            'status' => SuratKeluar::STATUS_DIPERIKSA,
            'status_approval' => 'diperiksa',
            'current_approval_step' => 1,
        ]);

        SuratKeluarApproval::create([
            'surat_keluar_id' => $surat->id,
            'user_id' => $approver->id,
            'step_order' => 1,
            'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
            'status' => SuratKeluarApproval::STATUS_MENUNGGU,
        ]);

        // Pastikan tidak ada transaksi terbuka agar proses anak dapat melihat data
        while (DB::transactionLevel() > 0) {
            DB::commit();
        }

        $letterId = $surat->id;
        $approverId = $approver->id;

        $phpBinary = PHP_BINARY;
        $artisan = base_path('artisan');

        // Siapkan environment database yang sama untuk proses anak
        $env = $_ENV;
        if (DB::getDriverName() === 'pgsql') {
            $env['DB_CONNECTION'] = 'pgsql';
            $env['DB_HOST'] = config('database.connections.pgsql.host', '127.0.0.1');
            $env['DB_PORT'] = (string) config('database.connections.pgsql.port', '5433');
            $env['DB_DATABASE'] = config('database.connections.pgsql.database', 'laras_test');
            $env['DB_USERNAME'] = config('database.connections.pgsql.username', 'postgres');
            $env['DB_PASSWORD'] = config('database.connections.pgsql.password', 'secret');
        }

        $cmd1 = [$phpBinary, $artisan, 'app:test-parallel-approval', '--worker', "--letter-id={$letterId}", "--approver-id={$approverId}"];
        $cmd2 = [$phpBinary, $artisan, 'app:test-parallel-approval', '--worker', "--letter-id={$letterId}", "--approver-id={$approverId}"];

        $proc1 = new Process($cmd1, base_path(), $env);
        $proc2 = new Process($cmd2, base_path(), $env);

        $this->line("Menjalankan dua proses persetujuan bersamaan...");
        $proc1->start();
        $proc2->start();

        $proc1->wait();
        $proc2->wait();

        $out1 = trim($proc1->getOutput());
        $out2 = trim($proc2->getOutput());
        $exit1 = $proc1->getExitCode();
        $exit2 = $proc2->getExitCode();

        $this->line("Worker 1 Exit [{$exit1}]: {$out1}");
        $this->line("Worker 2 Exit [{$exit2}]: {$out2}");

        // Verifikasi kondisi database
        $freshSurat = SuratKeluar::find($letterId);
        $approvedCount = SuratKeluarApproval::where('surat_keluar_id', $letterId)
            ->where('status', SuratKeluarApproval::STATUS_DISETUJUI)
            ->count();

        $this->line("Approval record 'disetujui': {$approvedCount}");
        $this->line("Nomor surat teralokasi: " . ($freshSurat->nomor_surat ?? 'TIDAK ADA'));

        $successCount = ($exit1 === 0 ? 1 : 0) + ($exit2 === 0 ? 1 : 0);
        $rejectedCount = ($exit1 !== 0 ? 1 : 0) + ($exit2 !== 0 ? 1 : 0);

        // Bersihkan data pengujian
        SuratKeluarApproval::where('surat_keluar_id', $letterId)->delete();
        $freshSurat?->delete();

        if ($successCount === 1 && $rejectedCount === 1 && $approvedCount === 1 && ! empty($freshSurat->nomor_surat)) {
            $this->info("[PASS] Hanya satu approval dan satu nomor surat yang berhasil!");
            return Command::SUCCESS;
        }

        $this->error("[FAIL] Konkurensi gagal: successCount={$successCount}, approvedCount={$approvedCount}");
        return Command::FAILURE;
    }
}
