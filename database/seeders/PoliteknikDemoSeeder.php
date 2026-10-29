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
        // Pengaman: Seeder demo HANYA boleh berjalan di environment local atau testing
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('PoliteknikDemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');

            return;
        }

        // -------------------------------------------------------------------
        // 1. Roles & Permissions Granular (Sesuai SOP Persuratan)
        // -------------------------------------------------------------------
        $permissions = [
            'surat-masuk.lihat',
            'surat-masuk.registrasi',
            'surat-masuk.ubah',
            'surat-masuk.batalkan',
            'disposisi.lihat',
            'disposisi.buat',
            'disposisi.teruskan',
            'disposisi.tindaklanjut',
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.periksa',
            'surat-keluar.penomoran',
            'surat-keluar.tandatangan',
            'arsip.lihat',
            'arsip.kelola',
            'organisasi.kelola',
            'pengaturan.kelola',
            'lisensi.kelola',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Sesuai PRD Bagian 6: Admin Instansi, Pimpinan, Kepala Unit, Staf TU, Petugas Arsip
        $rolesData = [
            'Admin Instansi' => $permissions,
            'Admin' => $permissions,
            'Pimpinan' => [
                'surat-masuk.lihat',
                'disposisi.lihat',
                'disposisi.buat',
                'disposisi.teruskan',
                'surat-keluar.lihat',
                'surat-keluar.periksa',
                'surat-keluar.tandatangan',
                'arsip.lihat',
            ],
            'Kepala Unit' => [
                'surat-masuk.lihat',
                'disposisi.lihat',
                'disposisi.teruskan',
                'disposisi.tindaklanjut',
                'surat-keluar.lihat',
                'surat-keluar.buat',
                'surat-keluar.periksa',
                'arsip.lihat',
            ],
            'Staf TU' => [
                'surat-masuk.lihat',
                'surat-masuk.registrasi',
                'surat-masuk.ubah',
                'surat-masuk.batalkan',
                'disposisi.lihat',
                'disposisi.tindaklanjut',
                'surat-keluar.lihat',
                'surat-keluar.buat',
                'surat-keluar.penomoran',
                'arsip.lihat',
            ],
            'Sekretaris' => [
                'surat-masuk.lihat',
                'surat-masuk.registrasi',
                'surat-keluar.lihat',
                'surat-keluar.buat',
                'surat-keluar.penomoran',
                'disposisi.lihat',
            ],
            'Petugas Arsip' => [
                'surat-masuk.lihat',
                'surat-keluar.lihat',
                'arsip.lihat',
                'arsip.kelola',
            ],
            'Staf' => [
                'surat-masuk.lihat',
                'disposisi.lihat',
                'disposisi.tindaklanjut',
                'surat-keluar.lihat',
            ],
        ];

        foreach ($rolesData as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePerms);
        }

        // -------------------------------------------------------------------
        // 2. Profil Instansi Fiktif ("Politeknik Contoh", contoh.ac.id)
        // -------------------------------------------------------------------
        $profile = InstitutionProfile::current();
        $profile->update([
            'name' => 'Politeknik Contoh Indonesia',
            'alias' => 'POLTEK-CONTOH',
            'address' => 'Jl. Pendidikan Nasional No. 123, Kota Contoh 12345',
            'email' => 'tatausaha@contoh.ac.id',
            'phone' => '(021) 555-1234',
            'website' => 'https://contoh.ac.id',
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
            'license_key' => 'LIC-CONTOH-STANDAR-2026-ACTIVE',
            'institution_slug' => 'politeknik-contoh',
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
            'signature_hash' => hash('sha256', 'LIC-CONTOH-STANDAR-2026-ACTIVE|politeknik-contoh|active'),
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
        // 6. Akun Pengguna Demo Fiktif (Password: 'password')
        // -------------------------------------------------------------------
        $demoUsers = [
            [
                'email' => 'admin@contoh.ac.id',
                'name' => 'Administrator Instansi',
                'nip' => '198501012010121001',
                'phone' => '081234567890',
                'position_id' => null,
                'roles' => ['Admin Instansi', 'Admin'],
            ],
            [
                'email' => 'direktur@contoh.ac.id',
                'name' => 'Dr. Ir. Budi Santoso, M.T.',
                'nip' => '196803151993031002',
                'phone' => '081234567891',
                'position_id' => $posDirektur->id,
                'roles' => ['Pimpinan'],
            ],
            [
                'email' => 'wadir1@contoh.ac.id',
                'name' => 'Prof. Dr. Ir. Haryanto, M.Eng.',
                'nip' => '197105201997021003',
                'phone' => '081234567892',
                'position_id' => $posWadir1->id,
                'roles' => ['Pimpinan'],
            ],
            [
                'email' => 'kajur.jtk@contoh.ac.id',
                'name' => 'Drs. Hendra Permana, M.Kom.',
                'nip' => '197508122001121001',
                'phone' => '081234567893',
                'position_id' => $posKajurJtk->id,
                'roles' => ['Kepala Unit'],
            ],
            [
                'email' => 'tu@contoh.ac.id',
                'name' => 'Siti Rahayu, A.Md.',
                'nip' => '199002142014042001',
                'phone' => '081234567894',
                'position_id' => $posStafTu->id,
                'roles' => ['Staf TU', 'Sekretaris', 'Staf'],
            ],
            [
                'email' => 'arsip@contoh.ac.id',
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
        $direkturUser = User::where('email', 'direktur@contoh.ac.id')->first();
        $wadirUser = User::where('email', 'wadir1@contoh.ac.id')->first();

        if ($direkturUser && $wadirUser) {
            PositionDelegation::firstOrCreate(
                ['position_id' => $posDirektur->id, 'start_date' => Carbon::now()->addDays(5)->toDateString()],
                [
                    'original_user_id' => $direkturUser->id,
                    'delegate_user_id' => $wadirUser->id,
                    'end_date' => Carbon::now()->addDays(12)->toDateString(),
                    'reason' => 'Dinas luar menghadiri Koordinasi Nasional di Jakarta',
                    'surat_tugas_number' => 'ST/089/DIR/X/2026',
                    'is_active' => true,
                ]
            );
        }
    }
}
