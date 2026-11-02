<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplatSurat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $role, string $email, string $nama): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'nama' => $nama,
                'name' => $nama,
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }

    protected function createKlasifikasi(): KlasifikasiSurat
    {
        return KlasifikasiSurat::create([
            'kode_klasifikasi' => 'TES',
            'nama' => 'Testing',
            'keterangan' => 'Data uji otomatis',
        ]);
    }

    public function test_staf_tu_and_kepala_unit_are_forbidden_from_pimpinan_and_admin_routes(): void
    {
        $stafTu = $this->createUser('Staf TU', 'tu.qa@example.com', 'Staf TU QA');
        $kepalaUnit = $this->createUser('Kepala Unit', 'kajur.qa@example.com', 'Kepala Unit QA');

        // Staf TU tidak boleh membuka tanda tangan pimpinan
        $this->actingAs($stafTu)
            ->get('/profil/tanda-tangan')
            ->assertStatus(403);

        // Kepala Unit tidak boleh membuat user baru (hanya Admin Instansi)
        $this->actingAs($kepalaUnit)
            ->get('/users/create')
            ->assertStatus(403);

        // Staf TU tidak boleh mengakses panel konfigurasi instansi
        $this->actingAs($stafTu)
            ->get('/admin/profile')
            ->assertStatus(403);
    }

    public function test_users_can_only_access_disposisi_assigned_to_their_own_id(): void
    {
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.qa@example.com', 'Pimpinan QA');
        $stafTu = $this->createUser('Staf TU', 'tu2.qa@example.com', 'Staf TU QA 2');
        $targetStaf = $this->createUser('Kepala Unit', 'target.qa@example.com', 'Target Kajur QA');

        $klasifikasi = $this->createKlasifikasi();

        $suratMasuk = SuratMasuk::create([
            'nomor_surat' => 'SM-QA-001',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->subDay(),
            'tanggal_diterima' => now(),
            'pengirim' => 'Instansi QA',
            'perihal' => 'Surat untuk uji disposisi',
            'ringkasan' => 'Uji isolasi data disposisi',
            'status_ekstraksi_ai' => 'done',
            'user_id' => $stafTu->id,
        ]);

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $suratMasuk->id,
            'pengirim_id' => $pimpinan->id,
            'penerima_id' => $targetStaf->id,
            'instruksi' => 'Tindak lanjut dan selesaikan tugas ini.',
            'prioritas' => 'segera',
            'status' => 'belum_dibaca',
        ]);

        $this->actingAs($stafTu)
            ->get('/disposisi/'.$disposisi->id)
            ->assertStatus(403);

        $this->actingAs($stafTu)
            ->patch('/disposisi/'.$disposisi->id.'/status', [
                '_token' => csrf_token(),
                'status' => 'selesai',
            ])
            ->assertStatus(403);

        $this->actingAs($targetStaf)
            ->get('/disposisi/'.$disposisi->id)
            ->assertOk();

        $this->actingAs($targetStaf)
            ->patch('/disposisi/'.$disposisi->id.'/status', [
                '_token' => csrf_token(),
                'status' => 'selesai',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disposisi', [
            'id' => $disposisi->id,
            'status' => 'selesai',
            'penerima_id' => $targetStaf->id,
        ]);
    }

    public function test_staf_tu_edit_form_includes_template_selection_for_surat_keluar(): void
    {
        $stafTu = $this->createUser('Staf TU', 'tu.template@example.com', 'Staf TU Template');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.template@example.com', 'Pimpinan Template');
        $klasifikasi = $this->createKlasifikasi();

        $template = TemplatSurat::create([
            'nama_templat' => 'Template Uji QA',
            'path_file' => 'templates/qa-template.html',
        ]);

        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => 'SK-EDIT-TEMPLATE-001',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->subDay(),
            'tujuan' => 'Pihak Terkait',
            'perihal' => 'Template edit test',
            'isi' => 'Isi surat untuk validasi template edit',
            'status_approval' => 'draft',
            'file_pdf' => 'surat/edit.pdf',
            'user_id' => $stafTu->id,
            'penyetuju_id' => $pimpinan->id,
            'templat_surat_id' => $template->id,
        ]);

        $this->actingAs($stafTu)
            ->get('/surat-keluar/'.$suratKeluar->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuratKeluar/Edit')
                ->has('templates')
                ->where('templates.0.nama', 'Template Uji QA')
            );
    }

    public function test_public_verification_route_returns_200_for_valid_token_and_404_for_invalid_token(): void
    {
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.verify@example.com', 'Pimpinan Verify');
        $stafTu = $this->createUser('Staf TU', 'tu.verify@example.com', 'Staf TU Verify');
        $klasifikasi = $this->createKlasifikasi();

        $surat = SuratKeluar::create([
            'nomor_surat' => 'SK-VERIFY-001',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->subDay(),
            'tujuan' => 'Pihak Terkait',
            'perihal' => 'Verifikasi publik',
            'isi' => 'Contoh isi surat untuk validasi QR',
            'status_approval' => 'approved',
            'file_pdf' => 'surat/verify.pdf',
            'user_id' => $stafTu->id,
            'penyetuju_id' => $pimpinan->id,
            'qr_token' => 'VALID-QA-TOKEN-123',
        ]);

        $this->get('/verify/'.$surat->qr_token)
            ->assertOk();

        $this->get('/verify/not-real-token-999')
            ->assertNotFound();
    }
}
