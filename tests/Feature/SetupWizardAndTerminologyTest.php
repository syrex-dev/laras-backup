<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupWizardAndTerminologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unconfigured_system_redirects_to_setup_wizard(): void
    {
        $profile = InstitutionProfile::current();
        $profile->update(['is_configured' => false]);

        $response = $this->withHeader('X-Test-Enforce-Setup', 'true')->get('/login');
        $response->assertRedirect('/setup');
    }

    public function test_setup_wizard_can_be_completed_and_creates_admin_user(): void
    {
        $profile = InstitutionProfile::current();
        $profile->update(['is_configured' => false]);

        $response = $this->post('/setup', [
            'institution_name' => 'Politeknik Negeri Subang',
            'institution_alias' => 'POLSUB',
            'timezone' => 'Asia/Jakarta',
            'default_theme' => 'emerald',
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'unit_level_2' => 'Program Studi',
                'lead_level_1' => 'Direktur',
                'lead_level_2' => 'Ketua Jurusan',
            ],
            'admin_name' => 'Budi Administrator',
            'admin_email' => 'admin@polsub.ac.id',
            'admin_password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        // Verifikasi profil instansi tersimpan dan ditandai configured
        $this->assertDatabaseHas('institution_profiles', [
            'name' => 'Politeknik Negeri Subang',
            'alias' => 'POLSUB',
            'is_configured' => true,
        ]);

        // Verifikasi akun admin terbentuk dan langsung login
        $this->assertDatabaseHas('users', [
            'email' => 'admin@polsub.ac.id',
            'name' => 'Budi Administrator',
        ]);

        $admin = User::where('email', 'admin@polsub.ac.id')->first();
        $this->assertTrue($admin->hasRole('Admin Instansi'));
        $this->assertAuthenticatedAs($admin);

        // Verifikasi log audit setup tercatat
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'system.setup_completed',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_configured_system_redirects_setup_route_to_dashboard(): void
    {
        $profile = InstitutionProfile::current();
        $profile->update(['is_configured' => true]);

        $response = $this->withHeader('X-Test-Enforce-Setup', 'true')->get('/setup');
        $response->assertRedirect('/dashboard');
    }

    public function test_updating_terminology_in_settings_immediately_updates_ui_shared_props(): void
    {
        $profile = InstitutionProfile::current();
        $profile->update([
            'name' => 'Politeknik Negeri Bandung',
            'alias' => 'POLBAN',
            'is_configured' => true,
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'lead_level_1' => 'Direktur',
                'lead_level_2' => 'Ketua Jurusan',
            ],
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin Instansi']);
        $admin = User::factory()->create(['name' => 'Admin Polban', 'email' => 'admin@polban.ac.id']);
        $admin->assignRole($adminRole);

        // Ubah istilah dinamis: "Jurusan" -> "Departemen", "Direktur" -> "Rektor", "Ketua Jurusan" -> "Kepala Departemen"
        $response = $this->actingAs($admin)->put('/pengaturan/profil', [
            'name' => 'Institut Teknologi Bandung',
            'alias' => 'ITB',
            'address' => 'Jl. Ganesha No. 10',
            'email' => 'info@itb.ac.id',
            'phone' => '022-123456',
            'website' => 'https://itb.ac.id',
            'timezone' => 'Asia/Jakarta',
            'default_theme' => 'emerald',
            'terminology_dict' => [
                'unit_level_1' => 'Departemen',
                'lead_level_1' => 'Rektor',
                'lead_level_2' => 'Kepala Departemen',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Buka halaman profil dengan Inertia testing helper
        $viewResponse = $this->actingAs($admin)->get('/pengaturan/profil');
        $viewResponse->assertOk();

        $viewResponse->assertInertia(fn (Assert $page) => $page
            ->component('Pengaturan/Profil')
            ->has('profile')
            ->where('profile.name', 'Institut Teknologi Bandung')
            ->where('profile.alias', 'ITB')
            ->where('profile.terminology_dict.unit_level_1', 'Departemen')
            ->where('profile.terminology_dict.lead_level_1', 'Rektor')
            ->where('profile.terminology_dict.lead_level_2', 'Kepala Departemen')
        );

        // Verifikasi log audit pembaharuan profil tercatat
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'institution_profile.updated',
            'causer_id' => $admin->id,
        ]);
    }
}
