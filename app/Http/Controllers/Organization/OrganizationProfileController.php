<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Actions\UpdateOrganizationProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationProfileRequest;
use App\Models\ProfilOrganisasi;

class OrganizationProfileController extends Controller
{
    public function index()
    {
        $profile = \App\Domain\Instansi\Models\InstitutionProfile::first() ?? \App\Domain\Instansi\Models\InstitutionProfile::current();

        return view('organization.profile.index', compact('profile'));
    }

    public function update(UpdateOrganizationProfileRequest $request, UpdateOrganizationProfileAction $updateAction)
    {
        $updateAction->execute(
            $request->validated(),
            $request->file('logo'),
            $request->file('stempel')
        );

        return back()->with('success', 'Profil organisasi berhasil diperbarui.');
    }
}
