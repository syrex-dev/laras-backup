<?php

namespace App\Http\Middleware;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Lisensi\Models\LicenseState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $userData = null;

        if ($user) {
            $user->loadMissing(['position.orgUnit', 'roles.permissions']);
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nip' => $user->nip,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'position_id' => $user->position_id,
                'position' => $user->position ? [
                    'id' => $user->position->id,
                    'name' => $user->position->name,
                    'level' => $user->position->level,
                    'is_penandatangan' => $user->position->is_penandatangan,
                    'org_unit' => $user->position->orgUnit ? [
                        'id' => $user->position->orgUnit->id,
                        'name' => $user->position->orgUnit->name,
                        'code' => $user->position->orgUnit->code,
                        'type' => $user->position->orgUnit->type,
                    ] : null,
                ] : null,
                'roles' => $user->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                ]),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ];
        }

        $institutionData = null;
        $isConfigured = false;
        if (Schema::hasTable('institution_profiles')) {
            $profile = InstitutionProfile::current();
            $isConfigured = (bool) $profile->is_configured;
            $institutionData = [
                'id' => $profile->id,
                'name' => $profile->name,
                'alias' => $profile->alias,
                'address' => $profile->address,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'website' => $profile->website,
                'logo_path' => $profile->logo_path,
                'header_image_path' => $profile->header_image_path,
                'timezone' => $profile->timezone,
                'default_theme' => $profile->default_theme,
                'terminology_dict' => $profile->terminology_dict ?? [],
                'is_configured' => $isConfigured,
            ];
        }

        $licenseData = null;
        if (Schema::hasTable('license_states')) {
            $license = LicenseState::current();
            $licenseData = [
                'id' => $license->id,
                'license_key' => $license->license_key,
                'institution_slug' => $license->institution_slug,
                'plan' => $license->plan,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'grace_period_days' => $license->grace_period_days,
                'max_users' => $license->max_users,
                'max_storage_bytes' => $license->max_storage_bytes,
                'active_features' => $license->active_features ?? [],
                'is_read_only' => $license->isReadOnly(),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
            'institution' => $institutionData,
            'license' => $licenseData,
            'is_configured' => $isConfigured,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
