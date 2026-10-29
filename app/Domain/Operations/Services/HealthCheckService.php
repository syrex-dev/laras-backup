<?php

namespace App\Domain\Operations\Services;

use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    /**
     * Memeriksa kesehatan sistem (FR-OPS-04)
     */
    public function check(): array
    {
        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $storageWritable = is_writable(storage_path('app'));
        $diskFreeSpaceBytes = disk_free_space(storage_path('app'));
        $diskFreeGb = $diskFreeSpaceBytes ? round($diskFreeSpaceBytes / (1024 * 1024 * 1024), 2) : 0;

        return [
            'status' => $dbOk && $storageWritable ? 'healthy' : 'degraded',
            'database' => $dbOk ? 'connected' : 'error',
            'storage_writable' => $storageWritable,
            'disk_free_gb' => $diskFreeGb,
            'app_version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
