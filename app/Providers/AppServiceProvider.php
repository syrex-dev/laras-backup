<?php

namespace App\Providers;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\SuratMasuk\Models\SuratMasuk as DomainSuratMasuk;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Policies\DisposisiPolicy;
use App\Policies\SuratMasukPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(SuratMasuk::class, SuratMasukPolicy::class);
        Gate::policy(DomainSuratMasuk::class, SuratMasukPolicy::class);
        Gate::policy(Disposisi::class, DisposisiPolicy::class);
    }
}
