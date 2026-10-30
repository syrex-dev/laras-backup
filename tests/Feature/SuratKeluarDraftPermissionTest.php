<?php

namespace Tests\Feature;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuratKeluarDraftPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_draft_creator_can_edit_and_delete_their_own_draft(): void
    {
        $unitA = OrgUnit::create(['code' => 'UNIT-A', 'name' => 'Unit Kerja A', 'type' => 'academic']);
        $posA = Position::create(['name' => 'Staf A', 'org_unit_id' => $unitA->id, 'level' => 4]);

        $creator = User::factory()->create(['position_id' => $posA->id]);
        $creator->givePermissionTo('surat-keluar.buat');

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $surat = SuratKeluar::create([
            'user_id' => $creator->id,
            'org_unit_id' => $unitA->id,
            'klasifikasi_id' => $klasifikasi->id,
            'perihal' => 'Draf Surat Keluar Sendiri',
            'tujuan' => 'Rektor',
            'isi_surat' => '<p>Konten draf</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'sifat' => 'biasa',
            'jenis' => 'nota_dinas',
        ]);

        $this->assertTrue(Gate::forUser($creator)->allows('update', $surat));
        $this->assertTrue(Gate::forUser($creator)->allows('delete', $surat));
    }

    public function test_other_user_without_permission_cannot_edit_or_delete_draft(): void
    {
        $unitA = OrgUnit::create(['code' => 'UNIT-A', 'name' => 'Unit Kerja A', 'type' => 'academic']);
        $posA1 = Position::create(['name' => 'Staf A1', 'org_unit_id' => $unitA->id, 'level' => 4]);
        $posA2 = Position::create(['name' => 'Staf A2', 'org_unit_id' => $unitA->id, 'level' => 4]);

        $creator = User::factory()->create(['position_id' => $posA1->id]);
        $colleague = User::factory()->create(['position_id' => $posA2->id]);
        $colleague->givePermissionTo('surat-keluar.buat');

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $surat = SuratKeluar::create([
            'user_id' => $creator->id,
            'org_unit_id' => $unitA->id,
            'klasifikasi_id' => $klasifikasi->id,
            'perihal' => 'Draf Rekan Satu Unit',
            'tujuan' => 'Rektor',
            'isi_surat' => '<p>Konten draf</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'sifat' => 'biasa',
            'jenis' => 'nota_dinas',
        ]);

        $this->assertFalse(Gate::forUser($colleague)->allows('update', $surat));
        $this->assertFalse(Gate::forUser($colleague)->allows('delete', $surat));
    }

    public function test_user_with_ubah_semua_can_edit_and_delete_draft_in_same_unit(): void
    {
        $unitA = OrgUnit::create(['code' => 'UNIT-A', 'name' => 'Unit Kerja A', 'type' => 'academic']);
        $posA1 = Position::create(['name' => 'Staf A1', 'org_unit_id' => $unitA->id, 'level' => 4]);
        $posA2 = Position::create(['name' => 'Kepala TU A', 'org_unit_id' => $unitA->id, 'level' => 3]);

        $creator = User::factory()->create(['position_id' => $posA1->id]);
        $unitLead = User::factory()->create(['position_id' => $posA2->id]);
        $unitLead->givePermissionTo('surat-keluar.ubah-semua');

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $surat = SuratKeluar::create([
            'user_id' => $creator->id,
            'org_unit_id' => $unitA->id,
            'klasifikasi_id' => $klasifikasi->id,
            'perihal' => 'Draf Satu Unit',
            'tujuan' => 'Rektor',
            'isi_surat' => '<p>Konten draf</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'sifat' => 'biasa',
            'jenis' => 'nota_dinas',
        ]);

        $this->assertTrue(Gate::forUser($unitLead)->allows('update', $surat));
        $this->assertTrue(Gate::forUser($unitLead)->allows('delete', $surat));
    }

    public function test_user_with_ubah_semua_cannot_edit_or_delete_draft_cross_unit(): void
    {
        $unitA = OrgUnit::create(['code' => 'UNIT-A', 'name' => 'Unit Kerja A', 'type' => 'academic']);
        $unitB = OrgUnit::create(['code' => 'UNIT-B', 'name' => 'Unit Kerja B', 'type' => 'academic']);

        $posA = Position::create(['name' => 'Staf A', 'org_unit_id' => $unitA->id, 'level' => 4]);
        $posB = Position::create(['name' => 'Kepala TU B', 'org_unit_id' => $unitB->id, 'level' => 3]);

        $creator = User::factory()->create(['position_id' => $posA->id]);
        $crossUnitLead = User::factory()->create(['position_id' => $posB->id]);
        $crossUnitLead->givePermissionTo('surat-keluar.ubah-semua');

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $surat = SuratKeluar::create([
            'user_id' => $creator->id,
            'org_unit_id' => $unitA->id,
            'klasifikasi_id' => $klasifikasi->id,
            'perihal' => 'Draf Lintas Unit',
            'tujuan' => 'Rektor',
            'isi_surat' => '<p>Konten draf</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'sifat' => 'biasa',
            'jenis' => 'nota_dinas',
        ]);

        $this->assertFalse(Gate::forUser($crossUnitLead)->allows('update', $surat));
        $this->assertFalse(Gate::forUser($crossUnitLead)->allows('delete', $surat));
    }

    public function test_admin_with_kelola_can_edit_and_delete_draft_across_any_unit(): void
    {
        $unitA = OrgUnit::create(['code' => 'UNIT-A', 'name' => 'Unit Kerja A', 'type' => 'academic']);
        $posA = Position::create(['name' => 'Staf A', 'org_unit_id' => $unitA->id, 'level' => 4]);

        $creator = User::factory()->create(['position_id' => $posA->id]);
        $admin = User::factory()->create();
        $admin->givePermissionTo('surat-keluar.kelola');

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $surat = SuratKeluar::create([
            'user_id' => $creator->id,
            'org_unit_id' => $unitA->id,
            'klasifikasi_id' => $klasifikasi->id,
            'perihal' => 'Draf Instansi',
            'tujuan' => 'Rektor',
            'isi_surat' => '<p>Konten draf</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'sifat' => 'biasa',
            'jenis' => 'nota_dinas',
        ]);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $surat));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $surat));
    }
}
