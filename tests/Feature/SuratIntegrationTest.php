<?php

namespace Tests\Feature;

use App\Models\KlasifikasiSurat;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;
use App\Models\TemplatSurat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SuratIntegrationTest
 *
 * Menguji alur integrasi end-to-end fitur Surat Keluar:
 *   1. Staf TU membuat draft → data tersimpan di database
 *   2. Pimpinan approve draft  → status berubah & qr_token ter-generate
 *   3. Pimpinan reject draft   → status berubah ke 'rejected'
 *   4. Pengguna tanpa izin buat (Petugas Arsip) tidak boleh POST store surat keluar (RBAC guard)
 *   5. Full E2E: Staf TU buat draft → Pimpinan approve
 */
class SuratIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Buat user dan assign role Spatie.
     */
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

    /**
     * Sediakan data pendukung minimal untuk membuat surat keluar:
     * KlasifikasiSurat, TemplatSurat, dan ProfilOrganisasi.
     */
    protected function seedSupportingData(): array
    {
        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'INT',
            'nama' => 'Integration Test',
            'keterangan' => 'Klasifikasi untuk integration test',
        ]);

        $template = TemplatSurat::create([
            'nama_templat' => 'Template Integration Test',
            'path_file' => 'templates/integration-test.html',
        ]);

        ProfilOrganisasi::create([
            'nama_instansi' => 'POLTEK Test',
            'kode_organisasi' => 'POLTEK-TEST',
            'alamat' => 'Jl. Test No. 1',
            'email' => 'test@contoh.ac.id',
            'telepon' => '081200000000',
        ]);

        return compact('klasifikasi', 'template');
    }

    /**
     * Skenario A: Staf TU berhasil membuat draft surat keluar
     * dan data tersimpan dengan benar di database.
     */
    public function test_staf_tu_can_create_draft_surat_keluar(): void
    {
        $stafTu = $this->createUser('Staf TU', 'tu.int@example.com', 'Staf TU Integration');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.int@example.com', 'Pimpinan Integration');
        $support = $this->seedSupportingData();

        $payload = [
            '_token' => csrf_token(),
            'klasifikasi_id' => $support['klasifikasi']->id,
            'penyetuju_id' => $pimpinan->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Dekan Fakultas Teknik',
            'perihal' => 'Undangan Rapat Koordinasi Institusi',
            'isi' => 'Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri rapat koordinasi.',
            'templat_surat_id' => $support['template']->id,
        ];

        $response = $this->actingAs($stafTu)
            ->post('/surat-keluar', $payload);

        $response->assertRedirect(route('surat-keluar.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surat_keluar', [
            'tujuan' => 'Dekan Fakultas Teknik',
            'perihal' => 'Undangan Rapat Koordinasi Institusi',
            'status_approval' => 'draft',
            'user_id' => $stafTu->id,
            'penyetuju_id' => $pimpinan->id,
            'templat_surat_id' => $support['template']->id,
        ]);

        $surat = SuratKeluar::where('perihal', 'Undangan Rapat Koordinasi Institusi')->first();
        $this->assertNotNull($surat);
        $this->assertNotNull($surat->nomor_surat, 'Nomor surat harus otomatis ter-generate');
        $this->assertNull($surat->qr_token, 'Draft belum boleh punya qr_token');
    }

    /**
     * Skenario B: Pimpinan approve surat keluar draft →
     * status berubah ke 'approved' dan qr_token ter-generate otomatis.
     */
    public function test_pimpinan_can_approve_draft_and_generate_qr_token(): void
    {
        Storage::fake('public');

        $stafTu = $this->createUser('Staf TU', 'tu.approve@example.com', 'Staf TU Approve');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.approve@example.com', 'Pimpinan Approve');
        $support = $this->seedSupportingData();

        $draft = SuratKeluar::create([
            'nomor_surat' => '001/INT/POLTEK/X/2026',
            'klasifikasi_id' => $support['klasifikasi']->id,
            'penyetuju_id' => $pimpinan->id,
            'user_id' => $stafTu->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Pihak Terkait',
            'perihal' => 'Surat yang Akan Diapprove',
            'isi' => 'Konten surat yang akan disetujui pimpinan.',
            'status_approval' => 'draft',
            'templat_surat_id' => $support['template']->id,
        ]);

        $response = $this->actingAs($pimpinan)
            ->patch('/surat-keluar/'.$draft->id.'/approve', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect(route('surat-keluar.show', $draft));
        $response->assertSessionHas('success');

        $draft->refresh();
        $this->assertEquals('approved', $draft->status_approval);
        $this->assertNotNull($draft->qr_token, 'qr_token harus ter-generate setelah approve');
        $this->assertEquals(32, strlen($draft->qr_token));

        Storage::disk('public')->assertExists($draft->path_qrcode);
    }

    /**
     * Skenario C: Pimpinan reject surat keluar draft →
     * status berubah ke 'rejected' dan qr_token TETAP null.
     */
    public function test_pimpinan_can_reject_draft_surat_keluar(): void
    {
        $stafTu = $this->createUser('Staf TU', 'tu.reject@example.com', 'Staf TU Reject');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.reject@example.com', 'Pimpinan Reject');
        $support = $this->seedSupportingData();

        $draft = SuratKeluar::create([
            'nomor_surat' => '002/INT/POLTEK/X/2026',
            'klasifikasi_id' => $support['klasifikasi']->id,
            'penyetuju_id' => $pimpinan->id,
            'user_id' => $stafTu->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Pihak Ditolak',
            'perihal' => 'Surat yang Akan Direject',
            'isi' => 'Konten surat yang tidak disetujui.',
            'status_approval' => 'draft',
            'templat_surat_id' => $support['template']->id,
        ]);

        $response = $this->actingAs($pimpinan)
            ->patch('/surat-keluar/'.$draft->id.'/reject', [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect(route('surat-keluar.show', $draft));
        $response->assertSessionHas('info');

        $draft->refresh();
        $this->assertEquals('rejected', $draft->status_approval);
        $this->assertNull($draft->qr_token, 'Surat rejected tidak boleh punya qr_token');
    }

    /**
     * Skenario D: Pengguna tanpa hak izin buat surat keluar (Petugas Arsip) TIDAK diizinkan POST store.
     */
    public function test_user_without_buat_permission_cannot_store_surat_keluar(): void
    {
        $petugasArsip = $this->createUser('Petugas Arsip', 'arsip.int@example.com', 'Petugas Arsip Integration');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.rbac@example.com', 'Pimpinan RBAC');
        $support = $this->seedSupportingData();

        $response = $this->actingAs($petugasArsip)
            ->post('/surat-keluar', [
                '_token' => csrf_token(),
                'klasifikasi_id' => $support['klasifikasi']->id,
                'penyetuju_id' => $pimpinan->id,
                'tanggal_surat' => now()->format('Y-m-d'),
                'tujuan' => 'Test RBAC',
                'perihal' => 'Arsip seharusnya tidak bisa buat surat',
                'isi' => 'Test body',
                'templat_surat_id' => $support['template']->id,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('surat_keluar', [
            'perihal' => 'Arsip seharusnya tidak bisa buat surat',
        ]);
    }

    /**
     * Skenario E: Alur end-to-end lengkap →
     * Staf TU buat draft → Pimpinan approve → qr_token valid di database.
     */
    public function test_full_integration_create_then_approve(): void
    {
        Storage::fake('public');

        $stafTu = $this->createUser('Staf TU', 'tu.e2e@example.com', 'Staf TU E2E');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.e2e@example.com', 'Pimpinan E2E');
        $support = $this->seedSupportingData();

        // Step 1: Staf TU membuat draft
        $createResponse = $this->actingAs($stafTu)->post('/surat-keluar', [
            '_token' => csrf_token(),
            'klasifikasi_id' => $support['klasifikasi']->id,
            'penyetuju_id' => $pimpinan->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Rektor Politeknik',
            'perihal' => 'E2E Integration Test',
            'isi' => 'End-to-end test: buat draft lalu approve.',
            'templat_surat_id' => $support['template']->id,
        ]);

        $createResponse->assertRedirect(route('surat-keluar.index'));

        $surat = SuratKeluar::where('perihal', 'E2E Integration Test')->firstOrFail();
        $this->assertEquals('draft', $surat->status_approval);

        // Step 2: Pimpinan approve
        $approveResponse = $this->actingAs($pimpinan)
            ->patch('/surat-keluar/'.$surat->id.'/approve', [
                '_token' => csrf_token(),
            ]);

        $approveResponse->assertRedirect(route('surat-keluar.show', $surat));

        $surat->refresh();
        $this->assertEquals('approved', $surat->status_approval);
        $this->assertNotNull($surat->qr_token);
        $this->assertEquals(32, strlen($surat->qr_token));

        Storage::disk('public')->assertExists($surat->path_qrcode);
    }
}
