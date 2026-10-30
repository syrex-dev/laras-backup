<?php

namespace App\Console\Commands;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'eoffice:sync-permissions';

    protected $description = 'Sinkronisasi seluruh role resmi PRD dan permission dari config/permissions.php serta reset cache Spatie';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  EOFFICE: SINKRONISASI ROLE & PERMISSION RESMI PRD  ');
        $this->info('====================================================');

        // Jalankan seeder idempoten
        $seeder = new RolePermissionSeeder();
        $seeder->run();

        // Tampilkan rekap hasil
        $rolesCount = Role::count();
        $permissionsCount = Permission::count();

        $this->line("Total Role Resmi Terdaftar       : {$rolesCount}");
        $this->line("Total Permission Resmi Terdaftar : {$permissionsCount}");
        $this->newLine();

        $rows = [];
        foreach (Role::with('permissions')->get() as $role) {
            $rows[] = [
                $role->name,
                $role->permissions->count(),
                $role->permissions->pluck('name')->take(3)->implode(', ') . ($role->permissions->count() > 3 ? '...' : ''),
            ];
        }

        $this->table(['Role', 'Jumlah Permission', 'Contoh Permission'], $rows);

        $this->info('[PASS] Sinkronisasi role dan permission selesai dan cache Spatie berhasil direset.');

        return Command::SUCCESS;
    }
}
