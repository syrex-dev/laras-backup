<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiCepatTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $subordinateUser;
    protected Position $leaderPosition;
    protected Position $subordinatePosition;
    protected SuratMasuk $suratMasuk;
    protected InstructionTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Siapkan permission
        Permission::firstOrCreate(['name' => 'disposisi.lihat']);
        Permission::firstOrCreate(['name' => 'disposisi.buat']);
        Permission::firstOrCreate(['name' => 'surat-masuk.lihat']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan']);
        $rolePimpinan->givePermissionTo(['disposisi.lihat', 'disposisi.buat', 'surat-masuk.lihat']);

        // Hierarki Organisasi: Induk & Sub-unit
        $parentUnit = OrgUnit::create([
            'name' => 'Direktorat Utama',
            'code' => 'DIR',
            'type' => 'direktorat',
            'order_index' => 1,
        ]);

        $childUnit = OrgUnit::create([
            'parent_id' => $parentUnit->id,
            'name' => 'Bagian Akademik & Kemahasiswaan',
            'code' => 'BAK',
            'type' => 'bagian',
            'order_index' => 2,
        ]);

        $this->leaderPosition = Position::create([
            'org_unit_id' => $parentUnit->id,
            'name' => 'Direktur Utama',
            'level' => 1,
            'is_penandatangan' => true,
        ]);

        $this->subordinatePosition = Position::create([
            'org_unit_id' => $childUnit->id,
            'name' => 'Kepala Bagian Akademik',
            'level' => 2,
            'is_penandatangan' => false,
        ]);

        $this->leader = User::factory()->create([
            'name' => 'Prof. Direktur',
            'position_id' => $this->leaderPosition->id,
        ]);
        $this->leader->assignRole($rolePimpinan);

        $this->subordinateUser = User::factory()->create([
            'name' => 'Budi Kabag',
            'position_id' => $this->subordinatePosition->id,
        ]);

        $this->template = InstructionTemplate::create([
            'kode' => 'TL-CEPAT',
            'nama' => 'Segera koordinasikan dengan seluruh prodi',
            'deskripsi' => 'Instruksi koordinasi cepat',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'user_id' => $this->leader->id,
            'nomor_agenda' => 'AGD-2026-0001',
            'nomor_surat' => '001/KEMENDIKBUD/X/2026',
            'tanggal_surat' => '2026-10-25',
            'tanggal_diterima' => '2026-10-26',
            'pengirim' => 'Kementerian Pendidikan',
            'perihal' => 'Undangan Rapat Koordinasi Nasional',
            'ringkasan' => 'Rapat koordinasi akreditasi dan kurikulum nasional.',
            'sifat' => 'penting',
            'status' => 'terdaftar',
        ]);
    }

    public function test_disposisi_index_provides_dynamic_database_presets_without_hardcoded_titles(): void
    {
        $response = $this->actingAs($this->leader)->get('/disposisi');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Disposisi/Index')
                ->has('quickPresets')
                ->where('quickPresets.0.position_id', $this->subordinatePosition->id)
                ->where('quickPresets.0.position_name', 'Kepala Bagian Akademik')
                ->where('quickPresets.0.unit_name', 'Bagian Akademik & Kemahasiswaan')
                ->where('quickPresets.0.template_id', $this->template->id)
                ->where('quickPresets.0.template_nama', 'Segera koordinasikan dengan seluruh prodi')
                ->where('quickPresets.0.template_kode', 'TL-CEPAT');
        });
    }

    public function test_quick_disposisi_submission_creates_disposisi_and_recipient(): void
    {
        $payload = [
            'surat_masuk_id' => $this->suratMasuk->id,
            'instruction_template_id' => $this->template->id,
            'instruksi' => $this->template->nama,
            'prioritas' => 'biasa',
            'recipients' => [
                [
                    'position_id' => $this->subordinatePosition->id,
                    'user_id' => $this->subordinateUser->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->leader)->post('/disposisi', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('disposisi', [
            'surat_masuk_id' => $this->suratMasuk->id,
            'pemberi_id' => $this->leader->id,
            'instruksi' => 'Segera koordinasikan dengan seluruh prodi',
        ]);

        $this->assertDatabaseHas('disposisi_recipients', [
            'recipient_user_id' => $this->subordinateUser->id,
            'recipient_position_id' => $this->subordinatePosition->id,
            'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
        ]);
    }

    public function test_unauthorized_user_cannot_submit_quick_disposisi(): void
    {
        $guestUser = User::factory()->create();

        $payload = [
            'surat_masuk_id' => $this->suratMasuk->id,
            'instruksi' => 'Instruksi tidak sah',
            'recipients' => [
                ['position_id' => $this->subordinatePosition->id],
            ],
        ];

        $response = $this->actingAs($guestUser)->post('/disposisi', $payload);

        $response->assertStatus(403);
    }
}
