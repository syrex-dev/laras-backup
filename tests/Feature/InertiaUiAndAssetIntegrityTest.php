<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaUiAndAssetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductionSeeder::class);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Penguji UI',
            'email' => 'admin.ui@polsub.ac.id',
        ]);
        $this->adminUser->assignRole('Admin Instansi');
    }

    /**
     * Memverifikasi integritas manifest bundle Vite dan berkas aset fisik.
     */
    public function test_vite_manifest_integrity_and_physical_assets_exist(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath, 'Manifest Vite public/build/manifest.json wajib tersedia.');

        $manifestContent = File::get($manifestPath);
        $manifest = json_decode($manifestContent, true);

        $this->assertIsArray($manifest, 'Manifest Vite harus berupa JSON array valid.');
        $this->assertArrayHasKey('resources/js/app.tsx', $manifest, 'Manifest wajib mendaftarkan entri resources/js/app.tsx.');
        $this->assertArrayHasKey('resources/css/app.css', $manifest, 'Manifest wajib mendaftarkan entri resources/css/app.css.');

        // Verifikasi keberadaan berkas kompilasi fisik
        $jsFile = public_path('build/' . $manifest['resources/js/app.tsx']['file']);
        $cssFile = public_path('build/' . $manifest['resources/css/app.css']['file']);

        $this->assertFileExists($jsFile, "Berkas JS yang dirujuk manifest wajib ada di disk: {$jsFile}");
        $this->assertFileExists($cssFile, "Berkas CSS yang dirujuk manifest wajib ada di disk: {$cssFile}");
    }

    /**
     * Memverifikasi login dan akses dashboard mengembalikan halaman Inertia dengan metadata dan props lengkap.
     */
    public function test_dashboard_renders_via_inertia_with_live_statistics_and_volume_chart(): void
    {
        $klasifikasi = \App\Models\KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'UM'], ['nama' => 'Umum']);
        \App\Models\SuratKeluar::create([
            'nomor_surat' => 'SK-DASH-001',
            'klasifikasi_id' => $klasifikasi->id,
            'user_id' => $this->adminUser->id,
            'tujuan' => 'Mitra Kampus',
            'perihal' => 'Pengujian Dashboard',
            'isi' => 'Konten pengujian relasi recent surat keluar',
            'status_approval' => 'draft',
            'status' => 'draf',
            'tanggal_surat' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('stats.surat_masuk')
            ->has('stats.surat_keluar')
            ->has('stats.disposisi')
            ->has('stats.klasifikasi')
            ->has('monthlyVolume')
            ->has('monthlyVolume.labels')
            ->has('monthlyVolume.surat_masuk')
            ->has('monthlyVolume.surat_keluar')
            ->has('recentSuratMasuk')
            ->has('recentSuratKeluar')
            ->has('pendingDisposisi')
        );

        // Verifikasi root HTML memuat tag aset Vite hasil kompilasi dan meta notranslate
        $html = $response->getContent();
        $this->assertStringContainsString('notranslate', $html);
        $this->assertStringContainsString('/build/assets/app-', $html);
        $this->assertStringContainsString('.css', $html);
        $this->assertStringContainsString('.js', $html);
    }

    /**
     * Memverifikasi seluruh tautan menu navigasi utama mengembalikan Inertia Response tanpa whiteout atau error Blade.
     */
    public function test_all_navigation_routes_return_valid_inertia_responses(): void
    {
        $adminRoutes = [
            '/dashboard',
            '/surat-masuk',
            '/surat-keluar',
            '/disposisi',
            '/klasifikasi-surat',
            '/unit-kerja',
            '/users',
            '/pengaturan/profil',
            '/admin/logs',
            '/admin/recycle-bin',
        ];

        foreach ($adminRoutes as $uri) {
            $response = $this->actingAs($this->adminUser)->get($uri);

            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Rute {$uri} harus mengembalikan status 200 OK untuk Admin Instansi."
            );

            $response->assertInertia(fn (Assert $page) => $page);
        }

        // Rute Tanda Tangan Digital Pimpinan (khusus pemegang hak surat-keluar.setujui)
        $pimpinanUser = User::factory()->create([
            'name' => 'Direktur Penguji TTE',
            'email' => 'direktur.ui@polsub.ac.id',
        ]);
        $pimpinanUser->assignRole('Pimpinan');

        $pimpinanResponse = $this->actingAs($pimpinanUser)->get('/profil/tanda-tangan');
        $this->assertEquals(200, $pimpinanResponse->getStatusCode());
        $pimpinanResponse->assertInertia(fn (Assert $page) => $page->component('Organisasi/TandaTangan'));
    }

    /**
     * Memverifikasi perintah eoffice:doctor berjalan sukses dengan exit code 0.
     */
    public function test_eoffice_doctor_artisan_command_passes(): void
    {
        $this->artisan('eoffice:doctor')
            ->assertExitCode(0)
            ->expectsOutputToContain('DIAGNOSTIK KESEHATAN SISTEM E-OFFICE')
            ->expectsOutputToContain('SEHAT');
    }
}
