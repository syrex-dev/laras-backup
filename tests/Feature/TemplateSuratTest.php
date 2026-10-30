<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use App\Models\User;
use Database\Seeders\TemplateSuratSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemplateSuratTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'organisasi.kelola']);
        Permission::firstOrCreate(['name' => 'surat-keluar.buat']);
        Permission::firstOrCreate(['name' => 'surat-keluar.template']);

        $roleAdmin = Role::firstOrCreate(['name' => 'Admin Instansi']);
        $roleAdmin->givePermissionTo(['organisasi.kelola', 'surat-keluar.template', 'surat-keluar.buat']);

        $roleStaff = Role::firstOrCreate(['name' => 'Staf']);
        $roleStaff->givePermissionTo(['surat-keluar.buat']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Persuratan',
            'email' => 'admin.persuratan@contoh.ac.id',
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->staffUser = User::factory()->create([
            'name' => 'Staf Biasa',
            'email' => 'staff@contoh.ac.id',
        ]);
        $this->staffUser->assignRole($roleStaff);

        InstitutionProfile::create([
            'name' => 'Politeknik Negeri Contoh',
            'alias' => 'PNC',
            'address' => 'Jl. Pendidikan Tinggi No. 10',
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'lead_level_1' => 'Direktur',
                'Kajur' => 'Ketua Jurusan',
            ],
        ]);
    }

    public function test_admin_can_create_template_with_automatic_variable_extraction(): void
    {
        $payload = [
            'nama' => 'Surat Undangan Senat',
            'jenis' => 'undangan',
            'kode' => 'UND-SENAT',
            'format_nomor' => '{urut}/UND-SENAT/{tahun}',
            'konten_default' => '<p>Nomor: {nomor_surat}</p><p>Kepada Yth. {tujuan}</p><p>Perihal: {perihal}</p><p>Tanggal: {tanggal_surat}</p><p>Hormat kami, {nama_penandatangan} ({jabatan_penandatangan})</p>',
            'use_institution_header' => true,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)->post('/surat-keluar/templates', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('template_surat', [
            'nama' => 'Surat Undangan Senat',
            'kode' => 'UND-SENAT',
            'jenis' => 'undangan',
        ]);

        $template = TemplateSurat::where('kode', 'UND-SENAT')->first();
        $this->assertNotNull($template);
        $this->assertContains('{nomor_surat}', $template->variabel);
        $this->assertContains('{tujuan}', $template->variabel);
        $this->assertContains('{perihal}', $template->variabel);
    }

    public function test_template_creation_rejects_unsupported_variables(): void
    {
        $payload = [
            'nama' => 'Template Variabel Salah',
            'jenis' => 'biasa',
            'konten_default' => '<p>Nomor: {nomor_surat}</p><p>Data asing: {variabel_liar_xyz}</p>',
        ];

        $response = $this->actingAs($this->adminUser)->post('/surat-keluar/templates', $payload);

        $response->assertSessionHasErrors(['konten_default']);
        $this->assertDatabaseMissing('template_surat', [
            'nama' => 'Template Variabel Salah',
        ]);
    }

    public function test_preview_endpoint_renders_variables_with_terminology(): void
    {
        $payload = [
            'konten_default' => '<p>Nomor Surat: {nomor_surat}</p><p>Ditetapkan oleh: {jabatan_penandatangan} {nama_instansi}</p><p>Pejabat: {nama_penandatangan}</p>',
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/surat-keluar/templates/preview', $payload);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('rendered', $data);
        $this->assertStringContainsString('0123/PL.7/KP.01.02/X/2026', $data['rendered']);
        $this->assertStringContainsString('Direktur Politeknik Negeri Contoh', $data['rendered']);
        $this->assertStringNotContainsString('{nomor_surat}', $data['rendered']);
    }

    public function test_template_seeder_creates_standard_templates(): void
    {
        $this->seed(TemplateSuratSeeder::class);

        $this->assertDatabaseHas('template_surat', ['kode' => 'ND-STD', 'jenis' => 'nota_dinas']);
        $this->assertDatabaseHas('template_surat', ['kode' => 'ST-STD', 'jenis' => 'surat_tugas']);
        $this->assertDatabaseHas('template_surat', ['kode' => 'SE-STD', 'jenis' => 'edaran']);
        $this->assertDatabaseHas('template_surat', ['kode' => 'SK-STD', 'jenis' => 'sk']);
    }

    public function test_non_admin_cannot_manage_templates(): void
    {
        $payload = [
            'nama' => 'Template Ilegal',
            'jenis' => 'nota_dinas',
            'konten_default' => '<p>Isi template</p>',
        ];

        $response = $this->actingAs($this->staffUser)->post('/surat-keluar/templates', $payload);
        $response->assertStatus(403);
    }
}
