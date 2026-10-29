<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class SetupWizardController extends Controller
{
    public function index()
    {
        $profile = InstitutionProfile::current();

        return Inertia::render('Setup/Wizard', [
            'initialProfile' => [
                'name' => $profile->name,
                'alias' => $profile->alias,
                'timezone' => $profile->timezone,
                'default_theme' => $profile->default_theme,
                'terminology_dict' => $profile->terminology_dict ?? [
                    'unit_level_1' => 'Jurusan',
                    'unit_level_2' => 'Program Studi',
                    'lead_level_1' => 'Direktur',
                    'lead_level_2' => 'Ketua Jurusan',
                    'lead_level_3' => 'Ketua Program Studi',
                    'admin_office' => 'Tata Usaha',
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_name' => 'required|string|max:255',
            'institution_alias' => 'required|string|max:50',
            'timezone' => 'required|string|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
            'default_theme' => 'required|string',
            'terminology_dict' => 'required|array',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:6',
        ]);

        $profile = InstitutionProfile::current();
        $profile->update([
            'name' => $validated['institution_name'],
            'alias' => $validated['institution_alias'],
            'timezone' => $validated['timezone'],
            'default_theme' => $validated['default_theme'],
            'terminology_dict' => $validated['terminology_dict'],
            'is_configured' => true,
        ]);

        // Pastikan role Admin / Admin Instansi tersedia
        $adminRole = Role::firstOrCreate(['name' => 'Admin Instansi']);
        $legacyAdminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Buat atau perbarui akun Administrator Utama
        $adminUser = User::where('email', $validated['admin_email'])->first();
        if ($adminUser) {
            $adminUser->update([
                'name' => $validated['admin_name'],
                'password' => Hash::make($validated['admin_password']),
                'is_active' => true,
            ]);
        } else {
            $adminUser = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'is_active' => true,
            ]);
        }

        $adminUser->assignRole([$adminRole, $legacyAdminRole]);

        // Catat log audit inisialisasi sistem
        AuditLogger::log(
            action: 'system.setup_completed',
            subject: $profile,
            description: "Wizard Setup Awal berhasil diselesaikan oleh {$adminUser->name}",
            properties: [
                'institution' => $profile->name,
                'admin_email' => $adminUser->email,
            ],
            causer: $adminUser
        );

        Auth::login($adminUser);

        return redirect()->route('dashboard')->with('success', 'Instalasi E-Office berhasil disetup! Selamat datang di dashboard.');
    }
}
