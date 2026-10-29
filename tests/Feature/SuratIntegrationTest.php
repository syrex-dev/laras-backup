<?php

namespace Tests\Feature;

use App\Models\KlasifikasiSurat;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;
use App\Models\TemplatSurat;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SuratIntegrationTest
 *
 * Menguji alur integrasi end-to-end fitur Surat Keluar:
 *   1. Sekretaris membuat draft → data tersimpan di database
 *   2. Pimpinan approve draft  → status berubah & qr_token ter-generate
 *   3. Pimpinan reject draft   → status berubah ke 'rejected'
 *   4. Staf tidak boleh POST store surat keluar (RBAC guard)
 *   5. Full E2E: Sekretaris buat draft → Pimpinan approve
 */
class SuratIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    // -----------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------

    /**
     * Buat role Spatie jika belum ada.
     */
    protected function ensureRolesExist(): void
    {
        foreach (['Admin', 'Sekretaris', 'Pimpinan', 'Staf'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Buat user dan assign role Spatie.
     */
    protected function createUser(string $role, string $email, string $nama): User
    {
        $this->ensureRolesExist();

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'nama' => $nama,
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
            'nama_instansi' => 'HIMASE Test',
            'kode_organisasi' => 'HIMA-TEST',
            'alamat' => 'Jl. Test No. 1',
            'email' => 'test@himase.ac.id',
            'telepon' => '081200000000',
        ]);

        return compact('klasifikasi', 'template');
    }

    // -----------------------------------------------------------------
    // TEST SCENARIOS
    // -----------------------------------------------------------------

    /**
     * Skenario A: Sekretaris berhasil membuat draft surat keluar
     * dan data tersimpan dengan benar di database.
     */
    public function test_sekretaris_can_create_draft_surat_keluar(): void
    {
        // Arrange
        $sekretaris = $this->createUser('Sekretaris', 'sekretaris.int@example.com', 'Sekretaris Integration');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.int@example.com', 'Pimpinan Integration');
        $support = $this->seedSupportingData();

        $payload = [
            '_token' => csrf_token(),
            'klasifikasi_id' => $support['klasifikasi']->id,
            'penyetuju_id' => $pimpinan->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Dekan Fakultas Teknik',
            'perihal' => 'Undangan Rapat Koordinasi HIMASE',
            'isi' => 'Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri rapat koordinasi.',
            'templat_surat_id' => $support['template']->id,
        ];

        // Act
        $response = $this->actingAs($sekretaris)
            ->post('/surat-keluar', $payload);

        // Assert – redirect ke index setelah berhasil
        $response->assertRedirect(route('surat-keluar.index'));
        $response->assertSessionHas('success');

        // Assert – data tersimpan di database
        $this->assertDatabaseHas('surat_keluar', [
            'tujuan' => 'Dekan Fakultas Teknik',
            'perihal' => 'Undangan Rapat Koordinasi HIMASE',
            'status_approval' => 'draft',
            'user_id' => $sekretaris->id,
            'penyetuju_id' => $pimpinan->id,
            'templat_surat_id' => $support['template']->id,
        ]);

        // Assert – nomor surat otomatis ter-generate (bukan null)
        $surat = SuratKeluar::where('perihal', 'Undangan Rapat Koordinasi HIMASE')->first();
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
        // Arrange – Fake storage agar QR Code bisa di-"write"
        Storage::fake('public');

        $sekretaris = $this->createUser('Sekretaris', 'sekretaris.approve@example.com', 'Sekretaris Approve');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.approve@example.com', 'Pimpinan Approve');
        $support = $this->seedSupportingData();

        // Buat draft terlebih dahulu (simulasi output dari Sekretaris)
        $draft = SuratKeluar::create([
            'nomor_surat' => '001/INT/HIMA-TEST/X/2026',
            'klasifikasi_id' => $support['klasifikasi']->id,
            'tanggal_surat' => now()->subDay(),
            'tujuan' => 'Kepala Lab',
            'perihal' => 'Pengajuan Peminjaman Lab',
            'isi' => 'Isi surat untuk keperluan approve test.',
            'status_approval' => 'draft',
            'user_id' => $sekretaris->id,
            'penyetuju_id' => $pimpinan->id,
            'templat_surat_id' => $support['template']->id,
        ]);

        $this->assertNull($draft->qr_token, 'Sebelum approve qr_token harus null');

        // Act – Pimpinan approve
        $response = $this->actingAs($pimpinan)
            ->patch('/surat-keluar/'.$draft->id.'/approve', [
                '_token' => csrf_token(),
            ]);

        // Assert – redirect berhasil
        $response->assertRedirect(route('surat-keluar.show', $draft));
        $response->assertSessionHas('success');

        // Assert – database terupdate
        $draft->refresh();
        $this->assertEquals('approved', $draft->status_approval, 'Status harus berubah menjadi approved');
        $this->assertNotNull($draft->qr_token, 'qr_token harus otomatis ter-generate setelah approve');
        $this->assertNotEmpty($draft->qr_token, 'qr_token tidak boleh kosong');
        $this->assertNotNull($draft->path_qrcode, 'path_qrcode harus terisi setelah approve');
        // Pipeline baru juga mengisi file_pdf (Signed PDF atau fallback copy)
        $this->assertNotNull($draft->file_pdf, 'file_pdf harus terisi setelah approve pipeline');

        // Assert – File QR Code tersimpan di storage
        Storage::disk('public')->assertExists($draft->path_qrcode);
    }

    /**
     * Skenario C: Pimpinan reject surat keluar draft →
     * status berubah ke 'rejected', qr_token tetap null.
     */
    public function test_pimpinan_can_reject_draft_surat_keluar(): void
    {
        // Arrange
        $sekretaris = $this->createUser('Sekretaris', 'sekretaris.reject@example.com', 'Sekretaris Reject');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.reject@example.com', 'Pimpinan Reject');
        $support = $this->seedSupportingData();

        $draft = SuratKeluar::create([
            'nomor_surat' => '002/INT/HIMA-TEST/X/2026',
            'klasifikasi_id' => $support['klasifikasi']->id,
            'tanggal_surat' => now()->subDays(2),
            'tujuan' => 'Wakil Rektor',
            'perihal' => 'Surat yang Akan Ditolak',
            'isi' => 'Isi surat ini sengaja untuk pengujian reject.',
            'status_approval' => 'draft',
            'user_id' => $sekretaris->id,
            'penyetuju_id' => $pimpinan->id,
            'templat_surat_id' => $support['template']->id,
        ]);

        // Act – Pimpinan reject
        $response = $this->actingAs($pimpinan)
            ->patch('/surat-keluar/'.$draft->id.'/reject', [
                '_token' => csrf_token(),
            ]);

        // Assert
        $response->assertRedirect(route('surat-keluar.show', $draft));

        $draft->refresh();
        $this->assertEquals('rejected', $draft->status_approval, 'Status harus berubah menjadi rejected');
        $this->assertNull($draft->qr_token, 'Surat rejected tidak boleh punya qr_token');
    }

    /**
     * Skenario D: Staf TIDAK diizinkan POST store surat keluar (RBAC guard).
     *
     * Catatan: GET /surat-keluar/create ditangkap oleh resource route show (404)
     * karena route ordering. Oleh karena itu kita uji POST store yang memang
     * dilindungi middleware role:Sekretaris.
     */
    public function test_staf_cannot_store_surat_keluar(): void
    {
        // Arrange
        $staf = $this->createUser('Staf', 'staf.int@example.com', 'Staf Integration');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.rbac@example.com', 'Pimpinan RBAC');
        $support = $this->seedSupportingData();

        // Act – Staf mencoba POST store surat keluar
        $response = $this->actingAs($staf)
            ->post('/surat-keluar', [
                '_token' => csrf_token(),
                'klasifikasi_id' => $support['klasifikasi']->id,
                'penyetuju_id' => $pimpinan->id,
                'tanggal_surat' => now()->format('Y-m-d'),
                'tujuan' => 'Test RBAC',
                'perihal' => 'Staf seharusnya tidak bisa',
                'isi' => 'Test body',
                'templat_surat_id' => $support['template']->id,
            ]);

        // Assert – 403 Forbidden
        $response->assertStatus(403);

        // Assert – data TIDAK tersimpan
        $this->assertDatabaseMissing('surat_keluar', [
            'perihal' => 'Staf seharusnya tidak bisa',
        ]);
    }

    /**
     * Skenario E: Alur end-to-end lengkap →
     * Sekretaris buat draft → Pimpinan approve → qr_token valid di database.
     */
    public function test_full_integration_create_then_approve(): void
    {
        Storage::fake('public');

        $sekretaris = $this->createUser('Sekretaris', 'sekretaris.e2e@example.com', 'Sekretaris E2E');
        $pimpinan = $this->createUser('Pimpinan', 'pimpinan.e2e@example.com', 'Pimpinan E2E');
        $support = $this->seedSupportingData();

        // Step 1: Sekretaris membuat draft
        $createResponse = $this->actingAs($sekretaris)->post('/surat-keluar', [
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
        $this->assertEquals(32, strlen($surat->qr_token), 'qr_token harus 32 karakter (hex dari 16 random bytes)');

        Storage::disk('public')->assertExists($surat->path_qrcode);
    }
}
