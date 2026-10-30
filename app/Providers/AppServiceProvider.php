<?php

namespace App\Providers;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\SuratKeluar\Models\SuratKeluar as DomainSuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratMasuk\Models\SuratMasuk as DomainSuratMasuk;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Policies\DisposisiPolicy;
use App\Policies\SuratKeluarPolicy;
use App\Policies\SuratMasukPolicy;
use App\Policies\TemplateSuratPolicy;
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
        Gate::policy(TemplateSurat::class, TemplateSuratPolicy::class);
        Gate::policy(SuratKeluar::class, SuratKeluarPolicy::class);
        Gate::policy(DomainSuratKeluar::class, SuratKeluarPolicy::class);
    }
}
