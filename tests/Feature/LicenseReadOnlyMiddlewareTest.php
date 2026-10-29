<?php

namespace Tests\Feature;

use App\Domain\Lisensi\Models\LicenseState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LicenseReadOnlyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rute pengujian dummy untuk memverifikasi middleware
        Route::middleware(['web'])->group(function () {
            Route::get('/test-document', function () {
                return response()->json(['status' => 'success', 'data' => 'Isi Surat']);
            });

            Route::post('/test-document', function () {
                return response()->json(['status' => 'created']);
            });

            Route::post('/admin/license/renew', function () {
                return response()->json(['status' => 'renewed']);
            });
        });
    }

    public function test_active_license_allows_both_get_and_post_requests(): void
    {
        $license = LicenseState::current();
        $license->update([
            'status' => 'active',
            'expires_at' => Carbon::now()->addMonths(6),
        ]);

        $this->get('/test-document')->assertOk();
        $this->post('/test-document')->assertOk();
    }

    public function test_expired_license_allows_get_read_only_access(): void
    {
        $license = LicenseState::current();
        $license->update([
            'status' => 'expired',
            'expires_at' => Carbon::now()->subDays(30),
            'grace_period_days' => 14,
        ]);

        $this->assertTrue($license->isReadOnly());

        // Operasi baca tetap diizinkan 100%
        $response = $this->get('/test-document');
        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }

    public function test_expired_license_blocks_mutating_post_requests_with_403_and_explanation(): void
    {
        $license = LicenseState::current();
        $license->update([
            'status' => 'expired',
            'expires_at' => Carbon::now()->subDays(30),
            'grace_period_days' => 14,
        ]);

        $response = $this->postJson('/test-document');
        $response->assertStatus(403);
        $response->assertJson([
            'is_read_only' => true,
            'status' => 'expired',
        ]);
        $this->assertStringContainsString('Mode Baca-Saja', $response->json('message'));
    }

    public function test_exempt_routes_such_as_license_renew_are_allowed_even_when_expired(): void
    {
        $license = LicenseState::current();
        $license->update([
            'status' => 'expired',
            'expires_at' => Carbon::now()->subDays(30),
        ]);

        $response = $this->postJson('/admin/license/renew');
        $response->assertOk();
        $response->assertJson(['status' => 'renewed']);
    }

    public function test_license_in_grace_period_still_allows_mutations(): void
    {
        $license = LicenseState::current();
        // Kedaluwarsa 3 hari lalu, tapi grace period 14 hari
        $license->update([
            'status' => 'grace_period',
            'expires_at' => Carbon::now()->subDays(3),
            'grace_period_days' => 14,
        ]);

        $this->assertTrue($license->isInGracePeriod());
        $this->assertFalse($license->isReadOnly());

        $this->get('/test-document')->assertOk();
        $this->post('/test-document')->assertOk();
    }
}
