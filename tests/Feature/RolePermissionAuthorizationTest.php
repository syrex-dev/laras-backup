<?php

namespace Tests\Feature;

use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Models\SuratMasuk;
use App\Models\User;
use Database\Seeders\PoliteknikDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PoliteknikDemoSeeder::class);
    }

    public function test_staf_tu_has_permission_to_register_surat_masuk_while_petugas_arsip_is_denied(): void
    {
        $stafTu = User::where('email', 'tu@contoh.ac.id')->firstOrFail();
        $petugasArsip = User::where('email', 'arsip@contoh.ac.id')->firstOrFail();

        $this->assertTrue($stafTu->can('surat-masuk.registrasi'));
        $this->assertFalse($petugasArsip->can('surat-masuk.registrasi'));

        // Verifikasi via Gate Policy
        $this->assertTrue(Gate::forUser($stafTu)->allows('create', SuratMasuk::class));
        $this->assertFalse(Gate::forUser($petugasArsip)->allows('create', SuratMasuk::class));
    }

    public function test_staf_tu_can_cancel_surat_masuk_while_kepala_unit_is_denied(): void
    {
        $stafTu = User::where('email', 'tu@contoh.ac.id')->firstOrFail();
        $kepalaUnit = User::where('email', 'kajur.jtk@contoh.ac.id')->firstOrFail();

        $this->assertTrue($stafTu->can('surat-masuk.batalkan'));
        $this->assertFalse($kepalaUnit->can('surat-masuk.batalkan'));

        $this->assertTrue(Gate::forUser($stafTu)->allows('cancel', SuratMasuk::class));
        $this->assertFalse(Gate::forUser($kepalaUnit)->allows('cancel', SuratMasuk::class));
    }

    public function test_admin_instansi_can_access_organization_crud_while_non_admin_is_forbidden(): void
    {
        $admin = User::where('email', 'admin@contoh.ac.id')->firstOrFail();
        $stafTu = User::where('email', 'tu@contoh.ac.id')->firstOrFail();

        // Admin diizinkan
        $this->actingAs($admin)->get('/unit-kerja')->assertOk();
        $this->actingAs($admin)->get('/jabatan')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();

        // Staf TU ditolak (403)
        $this->actingAs($stafTu)->get('/unit-kerja')->assertForbidden();
        $this->actingAs($stafTu)->get('/jabatan')->assertForbidden();
        $this->actingAs($stafTu)->get('/users')->assertForbidden();
    }

    public function test_admin_instansi_can_create_unit_kerja_and_audit_log_is_recorded(): void
    {
        $admin = User::where('email', 'admin@contoh.ac.id')->firstOrFail();

        $response = $this->actingAs($admin)->post('/unit-kerja', [
            'name' => 'Unit Pelaksana Teknis Bahasa',
            'code' => 'UPT-BAHASA',
            'type' => 'unit_pelaksana',
            'order_index' => 10,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('org_units', [
            'code' => 'UPT-BAHASA',
            'name' => 'Unit Pelaksana Teknis Bahasa',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'org_unit.created',
        ]);
    }

    public function test_admin_instansi_can_create_jabatan_and_audit_log_is_recorded(): void
    {
        $admin = User::where('email', 'admin@contoh.ac.id')->firstOrFail();
        $tuUnit = OrgUnit::where('code', 'TU')->firstOrFail();

        $response = $this->actingAs($admin)->post('/jabatan', [
            'org_unit_id' => $tuUnit->id,
            'name' => 'Koordinator Rumah Tangga & BMN',
            'level' => 4,
            'is_penandatangan' => false,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('positions', [
            'org_unit_id' => $tuUnit->id,
            'name' => 'Koordinator Rumah Tangga & BMN',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'position.created',
        ]);
    }

    public function test_user_creation_is_rejected_when_active_users_reach_license_quota(): void
    {
        $admin = User::where('email', 'admin@contoh.ac.id')->firstOrFail();
        $license = LicenseState::current();

        // Set kuota lisensi sama dengan jumlah user aktif saat ini
        $activeUsers = User::where('is_active', true)->count();
        $license->update(['max_users' => $activeUsers]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Pegawai Baru Melebihi Kuota',
            'nip' => '199501012020121005',
            'email' => 'overquota@contoh.ac.id',
            'password' => 'password123',
            'role' => 'Staf TU',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors(['is_active']);
        $this->assertDatabaseMissing('users', [
            'email' => 'overquota@contoh.ac.id',
        ]);
    }

    public function test_user_creation_succeeds_when_quota_available_and_assigns_role_and_position(): void
    {
        $admin = User::where('email', 'admin@contoh.ac.id')->firstOrFail();
        $pos = Position::firstOrFail();
        $role = Role::where('name', 'Petugas Arsip')->firstOrFail();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Rina Kartika, S.Kom.',
            'nip' => '199805122022032001',
            'email' => 'rina.kartika@contoh.ac.id',
            'phone' => '081298765432',
            'password' => 'secret123',
            'position_id' => $pos->id,
            'role' => 'Petugas Arsip',
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');

        $newUser = User::where('email', 'rina.kartika@contoh.ac.id')->firstOrFail();
        $this->assertEquals($pos->id, $newUser->position_id);
        $this->assertTrue($newUser->hasRole('Petugas Arsip'));
        $this->assertTrue($newUser->can('arsip.kelola'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
        ]);
    }
}
