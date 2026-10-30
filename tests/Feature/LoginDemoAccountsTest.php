<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginDemoAccountsTest extends TestCase
{
    public function test_login_page_includes_demo_accounts_prop_in_local_or_testing_environment(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('showDemoAccounts', true)
        );
    }

    public function test_login_page_does_not_have_demo_accounts_prop_in_production_environment(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->missing('showDemoAccounts')
        );
    }
}
