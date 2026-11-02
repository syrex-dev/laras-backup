<?php

namespace App\Console\Commands;

use App\Domain\Instansi\Models\InstitutionProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eoffice:doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendiagnosis kesehatan sistem E-Office, integritas aset frontend, basis data, dan konfigurasi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('================================================================================');
        $this->info('                     DIAGNOSTIK KESEHATAN SISTEM E-OFFICE                       ');
        $this->info('================================================================================');

        $rows = [];
        $hasCriticalError = false;

        // 1. PHP Version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.2.0', '>=')) {
            $rows[] = ['Lingkungan PHP', 'Versi PHP >= 8.2', '<fg=green>SEHAT</>', "v{$phpVersion}"];
        } else {
            $rows[] = ['Lingkungan PHP', 'Versi PHP >= 8.2', '<fg=red>GAGAL</>', "v{$phpVersion} (Wajib >= 8.2)"];
            $hasCriticalError = true;
        }

        // 2. Ekstensi Kunci
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'xml', 'bcmath', 'fileinfo'];
        $missingExtensions = [];
        foreach ($requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        if (empty($missingExtensions)) {
            $rows[] = ['Lingkungan PHP', 'Ekstensi Inti', '<fg=green>SEHAT</>', implode(', ', $requiredExtensions)];
        } else {
            $rows[] = ['Lingkungan PHP', 'Ekstensi Inti', '<fg=red>GAGAL</>', 'Hilang: '.implode(', ', $missingExtensions)];
            $hasCriticalError = true;
        }

        // 3. Basis Data
        try {
            $driver = DB::connection()->getDriverName();
            $dbName = DB::connection()->getDatabaseName();
            DB::connection()->getPdo();
            $rows[] = ['Basis Data', 'Koneksi PDO', '<fg=green>TERHUBUNG</>', "Driver: {$driver}, DB: {$dbName}"];

            if ($driver === 'pgsql') {
                $trgm = DB::select("SELECT extname FROM pg_extension WHERE extname = 'pg_trgm'");
                if (! empty($trgm)) {
                    $rows[] = ['Basis Data', 'Ekstensi pg_trgm', '<fg=green>TERPASANG</>', 'Fungsi pencarian teks & kemiripan aktif'];
                } else {
                    $rows[] = ['Basis Data', 'Ekstensi pg_trgm', '<fg=yellow>PERINGATAN</>', 'pg_trgm belum aktif (jalankan: CREATE EXTENSION pg_trgm)'];
                }
            }
        } catch (\Throwable $e) {
            $rows[] = ['Basis Data', 'Koneksi PDO', '<fg=red>GAGAL</>', $e->getMessage()];
            $hasCriticalError = true;
        }

        // 4. File Hot & Vite Server
        $hotFile = public_path('hot');
        if (File::exists($hotFile)) {
            $hotUrl = trim(File::get($hotFile));
            $isReachable = false;
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 1]]);
                $content = @file_get_contents($hotUrl, false, $ctx);
                if ($content !== false) {
                    $isReachable = true;
                }
            } catch (\Throwable) {}

            if ($isReachable) {
                $rows[] = ['Aset Frontend', 'Mode Vite Dev Server', '<fg=green>AKTIF</>', "Vite aktif di {$hotUrl}"];
            } else {
                $rows[] = ['Aset Frontend', 'File public/hot', '<fg=yellow>PERINGATAN</>', "File 'hot' tertinggal tapi dev server mati. Jalankan 'npm run dev:clean'!"];
            }
        } else {
            $rows[] = ['Aset Frontend', 'File public/hot', '<fg=green>BERSIH</>', 'Tidak ada file hot tertinggal (melayani build statis)'];
        }

        // 5. Manifest Aset Statis
        $manifestPath = public_path('build/manifest.json');
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);
            if (is_array($manifest) && isset($manifest['resources/js/app.tsx'])) {
                $rows[] = ['Aset Frontend', 'Manifest Bundle Vite', '<fg=green>VALID</>', "Ditemukan di {$manifestPath}"];
            } else {
                $rows[] = ['Aset Frontend', 'Manifest Bundle Vite', '<fg=yellow>PERINGATAN</>', 'Manifest ada namun entri app.tsx tidak ditemukan'];
            }
        } else {
            $rows[] = ['Aset Frontend', 'Manifest Bundle Vite', '<fg=yellow>TIDAK ADA</>', 'Belum dibuild. Jalankan: npm run build'];
        }

        // 6. Izin Tulis Direktori
        $writableDirs = [
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        $unwritable = [];
        foreach ($writableDirs as $name => $path) {
            if (! is_writable($path)) {
                $unwritable[] = $name;
            }
        }
        if (empty($unwritable)) {
            $rows[] = ['Sistem Berkas', 'Izin Tulis Direktori', '<fg=green>SEHAT</>', 'Storage dan cache dapat ditulis'];
        } else {
            $rows[] = ['Sistem Berkas', 'Izin Tulis Direktori', '<fg=red>GAGAL</>', 'Tidak dapat ditulis: '.implode(', ', $unwritable)];
            $hasCriticalError = true;
        }

        // 7. Kunci Aplikasi & Keamanan
        if (config('app.key')) {
            $rows[] = ['Keamanan', 'APP_KEY', '<fg=green>TERPASANG</>', 'Kunci enkripsi aplikasi aktif'];
        } else {
            $rows[] = ['Keamanan', 'APP_KEY', '<fg=red>GAGAL</>', 'APP_KEY kosong! Jalankan: php artisan key:generate'];
            $hasCriticalError = true;
        }

        // 8. Konfigurasi Instansi
        try {
            $profile = InstitutionProfile::current();
            if ($profile && $profile->is_configured) {
                $rows[] = ['Aplikasi', 'Status Profil Instansi', '<fg=green>TERKONFIGURASI</>', "{$profile->name} ({$profile->alias})"];
            } else {
                $rows[] = ['Aplikasi', 'Status Profil Instansi', '<fg=yellow>MENUNGGU SETUP</>', 'Instansi belum dikonfigurasi via wizard /setup'];
            }
        } catch (\Throwable $e) {
            $rows[] = ['Aplikasi', 'Status Profil Instansi', '<fg=yellow>TIDAK DIKETAHUI</>', $e->getMessage()];
        }

        // Tampilkan Tabel
        $this->table(['Komponen', 'Item Pemeriksaan', 'Status', 'Keterangan'], $rows);

        if ($hasCriticalError) {
            $this->error('Ditemukan kesalahan kritis pada sistem yang memerlukan perbaikan sebelum beroperasi.');
            return 1;
        }

        $this->info('Semua indikator utama sistem berfungsi dengan baik!');
        return 0;
    }
}
