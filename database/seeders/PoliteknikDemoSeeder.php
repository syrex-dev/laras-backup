<?php

namespace Database\Seeders;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\Pengguna\Models\PositionDelegation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PoliteknikDemoSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------------------
        // 1. Roles & Permissions Granular
        // -------------------------------------------------------------------
        $permissions = [
            'surat-masuk.view',
            'surat-masuk.create',
            'surat-masuk.update',
            'surat-masuk.cancel',
            'disposisi.view',
            'disposisi.create',
            'disposisi.forward',
            'disposisi.process',
            'surat-keluar.view',
            'surat-keluar.create',
            'surat-keluar.review',
            'surat-keluar.numbering',
            'surat-keluar.sign',
            'arsip.view',
            'arsip.manage',
            'organisasi.manage',
            'pengaturan.manage',
            'lisensi.manage',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $rolesData = [
            'Admin Instansi' => $permissions,
            'Admin' => $permissions,
            'Pimpinan' => [
                'surat-masuk.view',
                'disposisi.view',
                'disposisi.create',
                'disposisi.forward',
                'surat-keluar.view',
                'surat-keluar.review',
                'surat-keluar.sign',
                'arsip.view',
            ],
            'Pejabat/Kepala Unit' => [
                'surat-masuk.view',
                'disposisi.view',
                'disposisi.forward',
                'disposisi.process',
                'surat-keluar.view',
                'surat-keluar.create',
                'surat-keluar.review',
                'arsip.view',
            ],
            'Staf/Tata Usaha' => [
                'surat-masuk.view',
                'surat-masuk.create',
                'surat-masuk.update',
                'surat-masuk.cancel',
                'disposisi.view',
                'disposisi.process',
                'surat-keluar.view',
                'surat-keluar.create',
                'surat-keluar.numbering',
                'arsip.view',
            ],
            'Sekretaris' => [
                'surat-masuk.view',
                'surat-masuk.create',
                'surat-keluar.view',
                'surat-keluar.create',
                'surat-keluar.numbering',
                'disposisi.view',
            ],
            'Petugas Arsip' => [
                'surat-masuk.view',
                'surat-keluar.view',
                'arsip.view',
                'arsip.manage',
            ],
            'Staf' => [
                'surat-masuk.view',
                'disposisi.view',
                'disposisi.process',
                'surat-keluar.view',
            ],
        ];

        foreach ($rolesData as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePerms);
        }

        // -------------------------------------------------------------------
        // 2. Profil Instansi (Politeknik Negeri Bandung)
        // -------------------------------------------------------------------
        $profile = InstitutionProfile::current();
        $profile->update([
            'name' => 'Politeknik Negeri Bandung',
            'alias' => 'POLBAN',
            'address' => 'Jl. Gegerkalong Hilir, Ds. Ciwaruga, Bandung 40012',
            'email' => 'tatausaha@polban.ac.id',
            'phone' => '(022) 2013789',
            'website' => 'https://www.polban.ac.id',
            'timezone' => 'Asia/Jakarta',
            'default_theme' => 'emerald',
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'unit_level_2' => 'Program Studi',
                'lead_level_1' => 'Direktur',
                'lead_level_2' => 'Ketua Jurusan',
                'lead_level_3' => 'Ketua Program Studi',
                'admin_office' => 'Bagian Tata Usaha',
            ],
            'is_configured' => true,
        ]);

        // -------------------------------------------------------------------
        // 3. Status Lisensi (Paket Standar Aktif)
        // -------------------------------------------------------------------
        $license = LicenseState::current();
        $license->update([
            'license_key' => 'LIC-POLBAN-STANDAR-2026-ACTIVE',
            'institution_slug' => 'polban-bandung',
            'plan' => 'standar',
            'status' => 'active',
            'expires_at' => Carbon::now()->addYear(),
            'grace_period_days' => 14,
            'max_users' => 100,
            'max_storage_bytes' => 53687091200, // 50 GB
            'active_features' => [
                'ocr' => true,
                'dashboard' => true,
                'delegations' => true,
                'tte' => true,
                'whatsapp' => true,
                'sso' => false,
                'api' => false,
            ],
            'last_verified_at' => Carbon::now(),
            'signature_hash' => hash('sha256', 'LIC-POLBAN-STANDAR-2026-ACTIVE|polban-bandung|active'),
        ]);

        // -------------------------------------------------------------------
        // 4. Struktur Organisasi Unit Kerja (Hierarkis)
        // -------------------------------------------------------------------
        $pimpinanUnit = OrgUnit::firstOrCreate(
            ['code' => 'DIR'],
            ['name' => 'Pimpinan Utama Politeknik', 'type' => 'pimpinan', 'order_index' => 1]
        );

        $tuUnit = OrgUnit::firstOrCreate(
            ['code' => 'TU'],
            ['name' => 'Bagian Tata Usaha & Kearsipan', 'type' => 'bagian', 'order_index' => 2, 'parent_id' => $pimpinanUnit->id]
        );

        $jtkUnit = OrgUnit::firstOrCreate(
            ['code' => 'JTK'],
            ['name' => 'Jurusan Teknik Komputer dan Informatika', 'type' => 'jurusan', 'order_index' => 3, 'parent_id' => $pimpinanUnit->id]
        );

        $d4TiUnit = OrgUnit::firstOrCreate(
            ['code' => 'D4-TI'],
            ['name' => 'Program Studi D4 Teknik Informatika', 'type' => 'prodi', 'order_index' => 1, 'parent_id' => $jtkUnit->id]
        );

        $jtmUnit = OrgUnit::firstOrCreate(
            ['code' => 'JTM'],
            ['name' => 'Jurusan Teknik Mesin', 'type' => 'jurusan', 'order_index' => 4, 'parent_id' => $pimpinanUnit->id]
        );

        // -------------------------------------------------------------------
        // 5. Jabatan Struktural (Positions)
        // -------------------------------------------------------------------
        $posDirektur = Position::firstOrCreate(
            ['org_unit_id' => $pimpinanUnit->id, 'name' => 'Direktur'],
            ['level' => 1, 'is_penandatangan' => true]
        );

        $posWadir1 = Position::firstOrCreate(
            ['org_unit_id' => $pimpinanUnit->id, 'name' => 'Wakil Direktur Bidang Akademik'],
            ['level' => 2, 'is_penandatangan' => true]
        );

        $posKabagTu = Position::firstOrCreate(
            ['org_unit_id' => $tuUnit->id, 'name' => 'Kepala Bagian Tata Usaha'],
            ['level' => 2, 'is_penandatangan' => false]
        );

        $posKajurJtk = Position::firstOrCreate(
            ['org_unit_id' => $jtkUnit->id, 'name' => 'Ketua Jurusan Teknik Komputer dan Informatika'],
            ['level' => 2, 'is_penandatangan' => true]
        );

        $posKaprodiD4 = Position::firstOrCreate(
            ['org_unit_id' => $d4TiUnit->id, 'name' => 'Ketua Program Studi D4 Teknik Informatika'],
            ['level' => 3, 'is_penandatangan' => false]
        );

        $posStafTu = Position::firstOrCreate(
            ['org_unit_id' => $tuUnit->id, 'name' => 'Staf Tata Usaha Persuratan'],
            ['level' => 4, 'is_penandatangan' => false]
        );

        $posPetugasArsip = Position::firstOrCreate(
            ['org_unit_id' => $tuUnit->id, 'name' => 'Petugas Arsip Dinamis'],
            ['level' => 4, 'is_penandatangan' => false]
        );

        // -------------------------------------------------------------------
        // 6. Akun Pengguna Demo (Password: 'password')
        // -------------------------------------------------------------------
        $demoUsers = [
            [
                'email' => 'admin@polban.ac.id',
                'name' => 'Administrator Instansi',
                'nip' => '198501012010121001',
                'phone' => '081234567890',
                'position_id' => null,
                'roles' => ['Admin Instansi', 'Admin'],
            ],
            [
                'email' => 'direktur@polban.ac.id',
                'name' => 'Dr. Ir. Marwanto, M.T.',
                'nip' => '196803151993031002',
                'phone' => '081234567891',
                'position_id' => $posDirektur->id,
                'roles' => ['Pimpinan'],
            ],
            [
                'email' => 'wadir1@polban.ac.id',
                'name' => 'Prof. Dr. Ir. Budi Santoso, M.Eng.',
                'nip' => '197105201997021003',
                'phone' => '081234567892',
                'position_id' => $posWadir1->id,
                'roles' => ['Pimpinan'],
            ],
            [
                'email' => 'kajur.jtk@polban.ac.id',
                'name' => 'Drs. Hendra Permana, M.Kom.',
                'nip' => '197508122001121001',
                'phone' => '081234567893',
                'position_id' => $posKajurJtk->id,
                'roles' => ['Pejabat/Kepala Unit'],
            ],
            [
                'email' => 'tu@polban.ac.id',
                'name' => 'Siti Rahayu, A.Md.',
                'nip' => '199002142014042001',
                'phone' => '081234567894',
                'position_id' => $posStafTu->id,
                'roles' => ['Staf/Tata Usaha', 'Sekretaris', 'Staf'],
            ],
            [
                'email' => 'arsip@polban.ac.id',
                'name' => 'Agus Budiman, S.Sos.',
                'nip' => '199209182018011002',
                'phone' => '081234567895',
                'position_id' => $posPetugasArsip->id,
                'roles' => ['Petugas Arsip'],
            ],
        ];

        foreach ($demoUsers as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'nama' => $u['name'],
                    'nip' => $u['nip'],
                    'phone' => $u['phone'],
                    'password' => Hash::make('password'),
                    'position_id' => $u['position_id'],
                    'is_active' => true,
                ]
            );

            $user->syncRoles($u['roles']);
        }

        // -------------------------------------------------------------------
        // 7. Contoh Delegasi Wewenang / Plt
        // -------------------------------------------------------------------
        $direkturUser = User::where('email', 'direktur@polban.ac.id')->first();
        $wadirUser = User::where('email', 'wadir1@polban.ac.id')->first();

        if ($direkturUser && $wadirUser) {
            PositionDelegation::firstOrCreate(
                ['position_id' => $posDirektur->id, 'start_date' => Carbon::now()->addDays(5)->toDateString()],
                [
                    'original_user_id' => $direkturUser->id,
                    'delegate_user_id' => $wadirUser->id,
                    'end_date' => Carbon::now()->addDays(12)->toDateString(),
                    'reason' => 'Dinas luar menghadiri Konferensi Poltek Nasional di Denpasar',
                    'surat_tugas_number' => 'ST/089/DIR/X/2026',
                    'is_active' => true,
                ]
            );
        }
    }
}
