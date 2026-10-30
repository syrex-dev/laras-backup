<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seeder Idempoten dan Aman Produksi untuk Role & Permission Resmi Sistem.
     * Menggunakan konfigurasi terpusat di config/permissions.php sebagai satu sumber kebenaran.
     */
    public function run(): void
    {
        // 1. Reset cache izin Spatie agar pembaruan langsung terdeteksi
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsConfig = config('permissions.permissions', []);
        $rolesConfig = config('permissions.roles', []);
        $rolePermissions = config('permissions.role_permissions', []);

        // 2. Buat atau perbarui seluruh permission resmi
        foreach ($permissionsConfig as $permName => $permMeta) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        // 3. Buat atau perbarui 5 Role Resmi PRD
        foreach ($rolesConfig as $roleName => $roleDesc) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            // Sinkronisasi permission sesuai pemetaan di konfigurasi
            if (isset($rolePermissions[$roleName])) {
                $role->syncPermissions($rolePermissions[$roleName]);
            }
        }

        // 4. Bersihkan role warisan (Admin, Staf, Sekretaris)
        $legacyRoles = ['Admin', 'Staf', 'Sekretaris'];
        foreach ($legacyRoles as $legacy) {
            $role = Role::where('name', $legacy)->where('guard_name', 'web')->first();
            if ($role) {
                // Pindahkan pengguna yang memiliki role Admin ke Admin Instansi
                if ($legacy === 'Admin') {
                    $adminInstansiRole = Role::where('name', 'Admin Instansi')->first();
                    foreach ($role->users as $u) {
                        $u->assignRole($adminInstansiRole);
                    }
                } elseif ($legacy === 'Sekretaris' || $legacy === 'Staf') {
                    // Pindahkan ke Staf TU
                    $stafTuRole = Role::where('name', 'Staf TU')->first();
                    foreach ($role->users as $u) {
                        $u->assignRole($stafTuRole);
                    }
                }
                $role->delete();
            }
        }

        // 5. Reset cache izin Spatie kembali setelah sinkronisasi tuntas
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
