<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowPermissionsMatrixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eoffice:permissions-matrix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menampilkan matriks Permission x Role resmi PRD dan tabel pemakaian permission di codebase';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $permissions = config('permissions.permissions', []);
        $rolePermissions = config('permissions.role_permissions', []);
        $roles = array_keys(config('permissions.roles', []));

        $this->info('================================================================================================');
        $this->info('                 MATRIKS HAK AKSES SISTEM E-OFFICE (PERMISSION X ROLE RESMI PRD)                 ');
        $this->info('================================================================================================');

        $matrixHeaders = array_merge(['Permission', 'Modul'], $roles, ['Status / Fase']);
        $matrixRows = [];

        foreach ($permissions as $permKey => $permMeta) {
            $row = [
                $permKey,
                $permMeta['module'] ?? '-',
            ];

            foreach ($roles as $role) {
                $hasPerm = in_array($permKey, $rolePermissions[$role] ?? [], true);
                $row[] = $hasPerm ? '✓' : '-';
            }

            $phase = $permMeta['phase'] ?? 'Fase 3A (Aktif)';
            $row[] = $phase;

            $matrixRows[] = $row;
        }

        $this->table($matrixHeaders, $matrixRows);

        $this->newLine();
        $this->info('================================================================================================');
        $this->info('                   TABEL PEMAKAIAN PERMISSION DALAM KODE SUMBER (APP/ & ROUTES/)                 ');
        $this->info('================================================================================================');

        $usageData = $this->collectPermissionUsages();
        $usageHeaders = ['Permission', 'Lokasi Penggunaan (File:Baris)', 'Konteks / Mekanisme Otorisasi', 'Fase'];
        $usageRows = [];

        foreach ($permissions as $permKey => $permMeta) {
            $phase = $permMeta['phase'] ?? 'Fase 3A (Aktif)';
            $occurrences = $usageData[$permKey] ?? [];

            if (empty($occurrences)) {
                $usageRows[] = [
                    $permKey,
                    '(Belum dipanggil langsung di kode / Disiapkan)',
                    'Definisi konfigurasi hak akses terpusat',
                    $phase,
                ];
            } else {
                foreach ($occurrences as $idx => $occ) {
                    $usageRows[] = [
                        $idx === 0 ? $permKey : '',
                        $occ['file'] . ':' . $occ['line'],
                        $occ['context'],
                        $idx === 0 ? $phase : '',
                    ];
                }
            }
        }

        $this->table($usageHeaders, $usageRows);

        return self::SUCCESS;
    }

    /**
     * Memindai direktori app/ dan routes/ untuk menemukan lokasi pemakaian permission
     *
     * @return array<string, array<int, array{file: string, line: int, context: string}>>
     */
    protected function collectPermissionUsages(): array
    {
        $scanPaths = [
            app_path(),
            base_path('routes'),
        ];

        $results = [];

        foreach ($scanPaths as $path) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $relPath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);
                $relPath = str_replace('\\', '/', $relPath);

                $lines = file($filePath);
                foreach ($lines as $lineNum => $lineContent) {
                    $actualLine = $lineNum + 1;

                    // 1. Pola ->can('permission.name')
                    if (preg_match_all("/->can\(\s*['\"]([a-z0-9\-]+\.[a-z0-9\-]+)['\"]\s*\)/", $lineContent, $m1)) {
                        foreach ($m1[1] as $p) {
                            $results[$p][] = [
                                'file' => $relPath,
                                'line' => $actualLine,
                                'context' => 'Gate/Policy: $user->can() check',
                            ];
                        }
                    }

                    // 2. Pola ->hasPermissionTo('permission.name')
                    if (preg_match_all("/->hasPermissionTo\(\s*['\"]([a-z0-9\-]+\.[a-z0-9\-]+)['\"]\s*\)/", $lineContent, $m2)) {
                        foreach ($m2[1] as $p) {
                            $results[$p][] = [
                                'file' => $relPath,
                                'line' => $actualLine,
                                'context' => 'Spatie Direct: $user->hasPermissionTo()',
                            ];
                        }
                    }

                    // 3. Pola Middleware: permission:permission.name|other.name
                    if (preg_match_all("/permission:([a-z0-9\-\.\|]+)/", $lineContent, $m3)) {
                        foreach ($m3[1] as $pipe) {
                            $parts = explode('|', $pipe);
                            foreach ($parts as $single) {
                                $clean = trim($single);
                                if (str_contains($clean, '.')) {
                                    $results[$clean][] = [
                                        'file' => $relPath,
                                        'line' => $actualLine,
                                        'context' => 'Route Middleware: permission:' . $pipe,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }
}
