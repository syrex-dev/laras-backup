<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_includes_demo_accounts_prop_in_local_or_testing_environment(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->has('demoAccounts')
            ->where('demoAccounts.password', 'password')
            ->has('demoAccounts.accounts', 3)
            ->where('demoAccounts.accounts.0.email', 'admin@contoh.ac.id')
        );
    }

    public function test_login_page_does_not_have_demo_accounts_prop_in_production_environment(): void
    {
        InstitutionProfile::updateOrCreate(
            [],
            [
                'name' => 'Instansi Pengujian',
                'is_configured' => true,
            ]
        );

        $this->app['env'] = 'production';

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->missing('demoAccounts')
        );
    }
}
