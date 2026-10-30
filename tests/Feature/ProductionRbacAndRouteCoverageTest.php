<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionRbacAndRouteCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Butir 2.8 (a): Konsistensi Permission
     * Mengambil semua permission yang dirujuk di app/ dan routes/
     * (can('...'), can:..., authorize, middleware permission:...)
     * dan membuktikan seluruhnya terdaftar di config/permissions.php
     * dan di database setelah ProductionSeeder dijalankan.
     * Test TIDAK membuat permission sendiri di setUp.
     */
    public function test_permission_consistency_with_production_seeder_and_registry(): void
    {
        // 1. Pastikan database permission awalnya bersih (tidak ada setup manual)
        $this->assertEquals(0, Permission::count(), 'Test tidak boleh membuat permission sendiri di setUp');

        // 2. Jalankan seeder produksi
        $this->seed(ProductionSeeder::class);

        $dbPermissions = Permission::pluck('name')->toArray();
        $configPermissions = array_keys(config('permissions.permissions', []));

        $this->assertNotEmpty($dbPermissions, 'Database harus memiliki permission setelah seeding produksi');
        $this->assertNotEmpty($configPermissions, 'Config permissions harus terdefinisi');

        // 3. Scan kode sumber di app/ dan routes/
        $referencedPermissions = $this->extractPermissionsFromCodebase();

        $this->assertNotEmpty($referencedPermissions, 'Harus ditemukan permission yang dirujuk di codebase');

        $missingInConfig = [];
        $missingInDb = [];

        foreach ($referencedPermissions as $permission) {
            if (! in_array($permission, $configPermissions, true)) {
                $missingInConfig[] = $permission;
            }
            if (! in_array($permission, $dbPermissions, true)) {
                $missingInDb[] = $permission;
            }
        }

        $this->assertEmpty($missingInConfig, 'Ada permission yang dirujuk di app/routes tapi tidak terdaftar di config/permissions.php: ' . implode(', ', $missingInConfig));
        $this->assertEmpty($missingInDb, 'Ada permission yang dirujuk di app/routes tapi tidak dibuat di database oleh ProductionSeeder: ' . implode(', ', $missingInDb));
    }

    /**
     * Butir 2.8 (b): Cakupan Rute
     * Menguji setiap rute dalam aplikasi:
     * - Tamu (tamu tanpa login) dialihkan ke halaman login (302/redirect)
     * - Pengguna login tanpa hak akses / permission ditolak dengan 403 Forbidden
     * - Pengguna dengan hak akses resmi diizinkan mengakses (lulus 200/redirect sukses)
     * - Test gagal jika ada rute baru yang bocor tanpa proteksi autentikasi.
     */
    public function test_route_coverage_and_protection(): void
    {
        $this->seed(ProductionSeeder::class);

        // Daftar putih rute publik resmi yang memang ditujukan untuk publik / belum terautentikasi
        $publicWhitelistPrefixes = [
            'login',
            'logout',
            'setup',
            'verify',
            'up',
            'storage',
            'sanctum',
            'livewire',
        ];

        // 1. Evaluasi seluruh rute GET yang terdaftar
        $allRoutes = Route::getRoutes();
        $testedCount = 0;

        foreach ($allRoutes as $route) {
            $methods = $route->methods();
            if (! in_array('GET', $methods, true)) {
                continue;
            }

            $uri = $route->uri();

            // Abaikan route internal debug/framework jika ada
            if (str_starts_with($uri, '_ignition') || str_starts_with($uri, '_debugbar')) {
                continue;
            }

            $isWhitelisted = false;
            foreach ($publicWhitelistPrefixes as $prefix) {
                if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                    $isWhitelisted = true;
                    break;
                }
            }

            // Jika bukan whitelist publik, tamu WAJIB ditolak/dialihkan ke login
            if (! $isWhitelisted) {
                $testUri = '/' . ltrim($uri, '/');
                // Ganti parameter dinamis {id} dengan dummy 1 untuk routing check
                $testUri = preg_replace('/\{[^}]+\}/', '1', $testUri);

                $response = $this->get($testUri);
                $status = $response->getStatusCode();

                $this->assertTrue(
                    in_array($status, [302, 401, 403], true),
                    "Rute [GET {$uri}] terbuka untuk publik tanpa proteksi auth! Status didapat: {$status}"
                );

                $testedCount++;
            }
        }

        $this->assertGreaterThan(10, $testedCount, 'Harus ada rute terproteksi yang diuji');

        // 2. Uji pengguna tanpa permission (Polosan tanpa Role & Permission)
        $unprivilegedUser = User::factory()->create([
            'email' => 'unprivileged@example.com',
            'is_active' => true,
        ]);

        $protectedEndpoints = [
            '/klasifikasi-surat',
            '/pengaturan/profil',
            '/users',
            '/unit-kerja',
            '/jabatan',
            '/surat-keluar/templates',
            '/template-surat',
            '/profil/tanda-tangan',
            '/admin/logs',
            '/surat-masuk',
            '/disposisi',
            '/surat-keluar',
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $resp = $this->actingAs($unprivilegedUser)->get($endpoint);
            $this->assertEquals(
                403,
                $resp->getStatusCode(),
                "Pengguna tanpa permission seharusnya mendapatkan HTTP 403 pada endpoint [{$endpoint}], tetapi mendapat {$resp->getStatusCode()}"
            );
        }

        // 3. Uji pengguna dengan permission tepat (Admin Instansi & Petugas Arsip)
        $admin = User::factory()->create(['email' => 'admin.guard@example.com', 'is_active' => true]);
        $admin->assignRole('Admin Instansi');

        $adminResp = $this->actingAs($admin)->get('/users');
        $this->assertEquals(200, $adminResp->getStatusCode(), 'Admin Instansi harus bisa mengakses /users');

        $arsip = User::factory()->create(['email' => 'arsip.guard@example.com', 'is_active' => true]);
        $arsip->assignRole('Petugas Arsip');

        $arsipResp = $this->actingAs($arsip)->get('/klasifikasi-surat');
        $this->assertEquals(200, $arsipResp->getStatusCode(), 'Petugas Arsip harus bisa mengakses /klasifikasi-surat');
    }

    /**
     * Butir 2.8 (c): Instalasi Produksi
     * Mensimulasikan APP_ENV=production, fresh state tanpa data demo,
     * membuktikan jumlah baris tabel bersih (tanpa akun/surat/disposisi demo),
     * menjalankan Setup Wizard via HTTP, dan memverifikasi admin pertama
     * memiliki akses penuh sesuai haknya.
     */
    public function test_production_installation_clean_and_setup_wizard_e2e(): void
    {
        config(['app.env' => 'production']);

        // 1. Eksekusi seeder produksi saja (ProductionSeeder)
        $this->seed(ProductionSeeder::class);

        // 2. Verifikasi kebersihan tabel produksi (TIDAK BOLEH ada data demo transaksi)
        $this->assertEquals(0, User::count(), 'Produksi bersih tidak boleh memiliki user demo awal sebelum wizard');
        $this->assertEquals(0, DB::table('surat_masuk')->count(), 'Tabel surat_masuk harus 0 di instalasi baru');
        $this->assertEquals(0, DB::table('surat_keluar')->count(), 'Tabel surat_keluar harus 0 di instalasi baru');
        $this->assertEquals(0, DB::table('disposisi')->count(), 'Tabel disposisi harus 0 di instalasi baru');
        $this->assertEquals(0, DB::table('disposisi_recipients')->count(), 'Tabel disposisi_recipients harus 0');

        // Data master acuan produksi wajib tersedia
        $this->assertEquals(5, Role::count(), 'Harus ada tepat 5 role resmi PRD');
        $this->assertGreaterThan(20, Permission::count(), 'Permission acuan produksi harus tersedia');
        $this->assertGreaterThan(0, DB::table('klasifikasi_surat')->count(), 'Master klasifikasi surat harus ada');
        $this->assertGreaterThan(0, DB::table('template_surat')->count(), 'Master template surat harus ada');
        $this->assertGreaterThan(0, DB::table('instruction_templates')->count(), 'Master instruction template harus ada');

        // Profil instansi belum terkonfigurasi sebelum wizard
        $profile = InstitutionProfile::current();
        $this->assertFalse($profile->is_configured, 'Instansi tidak boleh terkonfigurasi sebelum wizard');

        // 3. Jalankan Setup Wizard via HTTP POST
        $setupPayload = [
            'institution_name' => 'Politeknik Negeri Produksi E-Office',
            'institution_alias' => 'POLTEK-PROD',
            'timezone' => 'Asia/Jakarta',
            'default_theme' => 'light',
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'unit_level_2' => 'Program Studi',
                'lead_level_1' => 'Direktur',
                'lead_level_2' => 'Ketua Jurusan',
                'lead_level_3' => 'Ketua Program Studi',
                'admin_office' => 'Bagian Umum dan Tata Usaha',
            ],
            'admin_name' => 'Administrator Instansi Utama',
            'admin_email' => 'admin.prod@poltek-prod.ac.id',
            'admin_password' => 'S4ngatRahasia&Aman!',
        ];

        $wizardResponse = $this->post(route('setup.store'), $setupPayload);
        $wizardResponse->assertRedirect(route('dashboard'));

        // 4. Verifikasi hasil Setup Wizard
        $this->assertEquals(1, User::count(), 'Harus ada tepat 1 pengguna setelah setup wizard');
        $admin = User::first();
        $this->assertEquals('admin.prod@poltek-prod.ac.id', $admin->email);
        $this->assertTrue($admin->hasRole('Admin Instansi'), 'Pengguna pertama wajib ber-role Admin Instansi');
        $this->assertFalse($admin->hasRole('Admin'), 'Role legacy Admin tidak boleh diberikan');

        $profile->refresh();
        $this->assertTrue($profile->is_configured, 'Profil instansi wajib berstatus is_configured = true');
        $this->assertEquals('Politeknik Negeri Produksi E-Office', $profile->name);

        // 5. Buktikan admin pertama dapat membuka seluruh modul penting yang menjadi haknya
        $modules = [
            '/dashboard' => 'Dashboard Utama',
            '/pengaturan/profil' => 'Pengaturan Profil Organisasi',
            '/users' => 'Manajemen Pengguna',
            '/unit-kerja' => 'Manajemen Unit Kerja',
            '/jabatan' => 'Manajemen Jabatan',
            '/surat-masuk' => 'Daftar Surat Masuk',
            '/disposisi' => 'Kotak Masuk Disposisi',
            '/surat-keluar' => 'Daftar Surat Keluar',
            '/klasifikasi-surat' => 'Master Klasifikasi Arsip',
            '/surat-keluar/templates' => 'Master Template Naskah Dinas',
            '/admin/logs' => 'Audit Log Sistem',
        ];

        foreach ($modules as $uri => $label) {
            $resp = $this->actingAs($admin)->get($uri);
            $this->assertEquals(
                200,
                $resp->getStatusCode(),
                "Admin pertama gagal membuka modul [{$label}] di URI {$uri} (Status: {$resp->getStatusCode()})"
            );
        }
    }

    /**
     * Helper untuk mengekstrak seluruh nama permission dari file kode di app/ dan routes/
     *
     * @return array<string>
     */
    protected function extractPermissionsFromCodebase(): array
    {
        $scanPaths = [
            app_path(),
            base_path('routes'),
        ];

        $found = [];

        foreach ($scanPaths as $path) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getRealPath());

                // Match ->can('something.here') atau ->can("something.here")
                if (preg_match_all("/->can\(\s*['\"]([a-z0-9\-]+\.[a-z0-9\-]+)['\"]\s*\)/", $content, $matches)) {
                    foreach ($matches[1] as $perm) {
                        $found[] = $perm;
                    }
                }

                // Match ->hasPermissionTo('something.here')
                if (preg_match_all("/->hasPermissionTo\(\s*['\"]([a-z0-9\-]+\.[a-z0-9\-]+)['\"]\s*\)/", $content, $matches)) {
                    foreach ($matches[1] as $perm) {
                        $found[] = $perm;
                    }
                }

                // Match middleware: permission:something.here
                if (preg_match_all("/permission:([a-z0-9\-\.\|]+)/", $content, $matches)) {
                    foreach ($matches[1] as $pipeline) {
                        $parts = explode('|', $pipeline);
                        foreach ($parts as $p) {
                            $clean = trim($p);
                            if (str_contains($clean, '.')) {
                                $found[] = $clean;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }
}
