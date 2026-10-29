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
        $profile = ProfilOrganisasi::first() ?: new ProfilOrganisasi;

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
