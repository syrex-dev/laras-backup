<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionProfileController extends Controller
{
    public function edit()
    {
        $profile = InstitutionProfile::current();

        return Inertia::render('Pengaturan/Profil', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'alias' => $profile->alias,
                'address' => $profile->address,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'website' => $profile->website,
                'timezone' => $profile->timezone,
                'default_theme' => $profile->default_theme,
                'terminology_dict' => $profile->terminology_dict ?? [],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:50',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:100',
            'timezone' => 'required|string|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
            'default_theme' => 'required|string',
            'terminology_dict' => 'required|array',
        ]);

        $profile = InstitutionProfile::current();
        $oldData = $profile->toArray();

        $profile->update($validated);

        AuditLogger::log(
            action: 'institution_profile.updated',
            subject: $profile,
            description: 'Profil instansi dan kamus istilah diperbarui oleh '.($request->user()?->name ?? 'Admin'),
            properties: [
                'old' => $oldData,
                'new' => $profile->toArray(),
            ]
        );

        return back()->with('success', 'Profil instansi dan kamus istilah berhasil diperbarui!');
    }
}
