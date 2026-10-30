<?php

namespace Tests\Feature;

use App\Domain\Letters\Actions\ApproveSuratKeluarAction as LegacyApproveAction;
use App\Domain\Letters\Actions\CreateSuratKeluarAction as LegacyCreateAction;
use App\Domain\Letters\Actions\RejectSuratKeluarAction as LegacyRejectAction;
use App\Domain\SuratKeluar\Models\SuratKeluar as CanonicalSuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar as ProxySuratKeluar;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrototypeAndCanonicalCoexistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductionSeeder::class);
    }

    /**
     * Membuktikan model prototipe lama (App\Models\SuratKeluar) dan adapter actions
     * mendelegasikan secara transparan ke Domain Kanonik baru (App\Domain\SuratKeluar).
     */
    public function test_prototype_models_and_legacy_actions_seamlessly_delegate_to_canonical_domain(): void
    {
        $staf = User::factory()->create(['name' => 'Staf TU Draf', 'email' => 'tu.adapter@example.com']);
        $staf->assignRole('Staf TU');

        $pimpinan = User::factory()->create(['name' => 'Pimpinan Penyetuju', 'email' => 'pimpinan.adapter@example.com']);
        $pimpinan->assignRole('Pimpinan');

        $klasifikasi = KlasifikasiSurat::first();
        $template = TemplateSurat::first();

        // 1. Eksekusi Legacy CreateSuratKeluarAction
        $legacyCreateAction = app(LegacyCreateAction::class);
        $draft = $legacyCreateAction->execute([
            'klasifikasi_id' => $klasifikasi->id,
            'penyetuju_id' => $pimpinan->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Mitra Industri Kerjasama',
            'perihal' => 'Pengujian Legacy Action Adapter',
            'isi' => '<p>Konten naskah dinas via adapter.</p>',
            'templat_surat_id' => $template->id,
        ], $staf->id);

        $this->assertInstanceOf(CanonicalSuratKeluar::class, $draft);
        $foundViaProxy = ProxySuratKeluar::find($draft->id);
        $this->assertNotNull($foundViaProxy, 'Model proxy lama harus bisa membaca data canonical');
        $this->assertEquals('draf', $draft->status);
        $this->assertEquals('draft', $draft->status_approval);
        $this->assertEquals(1, $draft->versions()->count(), 'Draft via adapter wajib membuat version 1 di pipeline kanonik');

        // Ajukan draf ke alur pemeriksaan agar berstatus 'diperiksa'
        app(\App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction::class)->execute($staf, $draft);

        // 2. Eksekusi Legacy RejectSuratKeluarAction
        $legacyRejectAction = app(LegacyRejectAction::class);
        $rejected = $legacyRejectAction->execute($draft, $pimpinan->id, 'Perlu perbaikan format lampiran');

        $this->assertEquals('ditolak', $rejected->status);
        $this->assertEquals('rejected', $rejected->status_approval);

        // 3. Eksekusi Legacy ApproveSuratKeluarAction
        $draft2 = $legacyCreateAction->execute([
            'klasifikasi_id' => $klasifikasi->id,
            'penyetuju_id' => $pimpinan->id,
            'tanggal_surat' => now()->format('Y-m-d'),
            'tujuan' => 'Kementerian Terkait',
            'perihal' => 'Pengujian Legacy Approve Adapter',
            'isi' => '<p>Konten naskah dinas siap setujui.</p>',
            'templat_surat_id' => $template->id,
        ], $staf->id);

        app(\App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction::class)->execute($staf, $draft2);

        $legacyApproveAction = app(LegacyApproveAction::class);
        $approveResult = $legacyApproveAction->execute($draft2, $pimpinan->id);

        $this->assertTrue($approveResult['success']);
        $this->assertEquals('disetujui', $approveResult['surat']->status);
    }

    /**
     * Membuktikan rute prototipe Dashboard (/dashboard) berfungsi dan terotorisasi benar.
     */
    public function test_prototype_dashboard_is_functional_and_authorized(): void
    {
        // Tamu ditolak dan dialihkan ke login
        $guestResp = $this->get('/dashboard');
        $guestResp->assertRedirect('/login');

        // Pengguna terautentikasi dapat membuka dashboard
        $user = User::factory()->create();
        $authResp = $this->actingAs($user)->get('/dashboard');
        $authResp->assertOk();
        $authResp->assertViewIs('dashboard');
        $authResp->assertViewHas('stats');
    }

    /**
     * Membuktikan rute daftar Disposisi (/disposisi) berfungsi dan terotorisasi benar.
     */
    public function test_prototype_disposisi_is_functional_and_authorized(): void
    {
        // Tamu ditolak
        $guestResp = $this->get('/disposisi');
        $guestResp->assertRedirect('/login');

        // Pengguna tanpa permission ditolak (403)
        $unauth = User::factory()->create();
        $forbidResp = $this->actingAs($unauth)->get('/disposisi');
        $forbidResp->assertStatus(403);

        // Pengguna dengan permission disposisi.lihat (Pimpinan) diizinkan (200)
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('Pimpinan');

        $okResp = $this->actingAs($pimpinan)->get('/disposisi');
        $okResp->assertOk();
    }

    /**
     * Membuktikan rute tong sampah (/admin/recycle-bin) berfungsi dan terotorisasi benar.
     */
    public function test_prototype_tong_sampah_recycle_bin_is_functional_and_authorized(): void
    {
        // Tamu ditolak
        $guestResp = $this->get('/admin/recycle-bin');
        $guestResp->assertRedirect('/login');

        // Pengguna non-admin ditolak (403)
        $staf = User::factory()->create();
        $staf->assignRole('Staf TU');

        $forbidResp = $this->actingAs($staf)->get('/admin/recycle-bin');
        $forbidResp->assertStatus(403);

        // Admin Instansi (pemegang permission organisasi.kelola | sistem.pemeliharaan) diizinkan (200)
        $admin = User::factory()->create();
        $admin->assignRole('Admin Instansi');

        $adminResp = $this->actingAs($admin)->get('/admin/recycle-bin');
        $adminResp->assertOk();
        $adminResp->assertViewIs('operations.recycle-bin.index');
    }

    /**
     * Membuktikan modul Surat Keluar (/surat-keluar) berfungsi dan terotorisasi benar.
     */
    public function test_surat_keluar_routes_are_functional_and_authorized(): void
    {
        // Tamu ditolak
        $guestResp = $this->get('/surat-keluar');
        $guestResp->assertRedirect('/login');

        // Pengguna tanpa permission ditolak (403)
        $unauth = User::factory()->create();
        $forbidResp = $this->actingAs($unauth)->get('/surat-keluar');
        $forbidResp->assertStatus(403);

        // Staf TU diizinkan
        $staf = User::factory()->create();
        $staf->assignRole('Staf TU');

        $stafResp = $this->actingAs($staf)->get('/surat-keluar');
        $stafResp->assertOk();
    }
}
