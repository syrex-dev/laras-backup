<?php

namespace Tests\Feature;

use App\Models\ProfilOrganisasi;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModularViewRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        ProfilOrganisasi::create([
            'nama_instansi' => 'HIMASE Politeknik',
            'kode_organisasi' => 'HIMASE',
            'email' => 'admin@contoh.ac.id',
            'alamat' => 'Jl. Pendidikan No. 1 Sumedang',
        ]);
    }

    protected function createUserWithRole(string $role): User
    {
        $user = User::query()->create([
            'nama' => "User {$role}",
            'email' => strtolower(str_replace(' ', '.', $role)).'@example.com',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_all_modular_views_render_successfully_for_authorized_users(): void
    {
        $admin = $this->createUserWithRole('Admin Instansi');
        $stafTu = $this->createUserWithRole('Staf TU');
        $pimpinan = $this->createUserWithRole('Pimpinan');

        // 1. Letters Views (letters.surat-masuk, letters.surat-keluar, letters.disposisi)
        $this->actingAs($stafTu)->get(route('surat-masuk.index'))->assertOk();
        $this->actingAs($stafTu)->get(route('surat-masuk.create'))->assertOk();
        $this->actingAs($stafTu)->get(route('surat-keluar.index'))->assertOk();
        $this->actingAs($stafTu)->get(route('surat-keluar.create'))->assertOk();
        $this->actingAs($stafTu)->get(route('disposisi.index'))->assertOk();
        $this->actingAs($pimpinan)->get(route('disposisi.create'))->assertOk();

        // 2. Organization Views (organization.users, organization.profile, organization.signature)
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.profile.index'))->assertOk();
        $this->actingAs($pimpinan)->get(route('profile.signature.index'))->assertOk();

        // 3. Archive Views (archive.klasifikasi-surat, archive.template-surat)
        $this->actingAs($admin)->get(route('klasifikasi-surat.index'))->assertOk();
        $this->actingAs($admin)->get(route('klasifikasi-surat.create'))->assertOk();
        $this->actingAs($admin)->get(route('template-surat.index'))->assertOk();
        $this->actingAs($admin)->get(route('template-surat.create'))->assertOk();

        // 4. Operations Views (operations.recycle-bin, operations.logs)
        $this->actingAs($admin)->get(route('admin.recycle-bin.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.logs.index'))->assertOk();

        // 5. Auth Views (auth.login)
        auth()->logout();
        $this->get(route('login'))->assertOk();
    }
}
