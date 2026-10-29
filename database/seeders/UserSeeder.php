<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Membuat 4 role Spatie dan 4 user realistis untuk Manual QA.
     * Password universal: 'password'
     */
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Pastikan 4 role Spatie tersedia
        // ---------------------------------------------------------------
        $roles = ['Admin', 'Sekretaris', 'Pimpinan', 'Staf'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // ---------------------------------------------------------------
        // 2. Buat 4 user QA realistis dengan data HIMASE
        // ---------------------------------------------------------------
        $users = [
            [
                'nama'  => 'Admin Simpan.in',
                'email' => 'admin@himase.ac.id',
                'role'  => 'Admin',
            ],
            [
                'nama'  => 'Nazwa Putri K',
                'email' => 'sekretaris@himase.ac.id',
                'role'  => 'Sekretaris',
            ],
            [
                'nama'  => 'Dede Rizki P',
                'email' => 'ketua@himase.ac.id',
                'role'  => 'Pimpinan',
            ],
            [
                'nama'  => 'Ilham Jamaludin',
                'email' => 'staf@himase.ac.id',
                'role'  => 'Staf',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'nama'     => $userData['nama'],
                    'email'    => $userData['email'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
