<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class SetupWizardController extends Controller
{
    /**
     * Memeriksa token setup opsional (SETUP_TOKEN di .env)
     */
    protected function validateSetupToken(Request $request): void
    {
        $requiredToken = env('SETUP_TOKEN');
        if (! empty($requiredToken)) {
            $providedToken = $request->query('token')
                ?? $request->input('setup_token')
                ?? $request->header('X-Setup-Token');

            if ($providedToken !== $requiredToken) {
                abort(403, 'Akses Setup Wizard ditolak. Token setup tidak valid atau belum diberikan.');
            }
        }
    }

    public function index(Request $request)
    {
        $this->validateSetupToken($request);

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
        $this->validateSetupToken($request);

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

        // 1. Tolak jika email sudah terdaftar di sistem (jangan update password akun yang ada)
        if (User::where('email', $validated['admin_email'])->exists()) {
            throw ValidationException::withMessages([
                'admin_email' => 'Email administrator sudah terdaftar di sistem. Gunakan email lain.',
            ]);
        }

        // 2. Inisialisasi Role & Permission resmi PRD sebelum membuat user
        (new RolePermissionSeeder())->run();

        // 3. Perbarui profil instansi dan tandai terkonfigurasi
        $profile = InstitutionProfile::current();
        $profile->update([
            'name' => $validated['institution_name'],
            'alias' => $validated['institution_alias'],
            'timezone' => $validated['timezone'],
            'default_theme' => $validated['default_theme'],
            'terminology_dict' => $validated['terminology_dict'],
            'is_configured' => true,
        ]);

        // 4. Buat akun Administrator Utama HANYA dengan peran 'Admin Instansi'
        $adminUser = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'is_active' => true,
        ]);

        $adminRole = Role::where('name', 'Admin Instansi')->firstOrFail();
        $adminUser->syncRoles([$adminRole]);

        // 5. Catat log audit inisialisasi sistem
        AuditLogger::log(
            'system.setup_completed',
            $profile,
            "Wizard Setup Awal berhasil diselesaikan oleh {$adminUser->name}",
            [
                'institution' => $profile->name,
                'admin_email' => $adminUser->email,
            ],
            $adminUser
        );

        Auth::login($adminUser);

        return redirect()->route('dashboard')->with('success', 'Instalasi E-Office berhasil disetup! Selamat datang di dashboard.');
    }
}
