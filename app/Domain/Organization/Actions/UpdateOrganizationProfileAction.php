<?php

namespace App\Domain\Organization\Actions;

use App\Models\ProfilOrganisasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateOrganizationProfileAction
{
    public function execute(array $data, ?UploadedFile $logo, ?UploadedFile $stempel): ProfilOrganisasi
    {
        $profile = ProfilOrganisasi::first() ?: new ProfilOrganisasi;

        $updateData = [
            'nama_instansi' => $data['nama_instansi'],
            'kode_organisasi' => $data['kode_organisasi'],
            'alamat' => $data['alamat'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
            'tema_default' => $data['tema_default'] ?? 'light',
        ];

        if ($logo) {
            if ($profile->path_logo && Storage::disk('public')->exists($profile->path_logo)) {
                Storage::disk('public')->delete($profile->path_logo);
            }
            $updateData['path_logo'] = $logo->store('organization', 'public');
        }

        if ($stempel) {
            if ($profile->path_stempel && Storage::disk('public')->exists($profile->path_stempel)) {
                Storage::disk('public')->delete($profile->path_stempel);
            }
            $updateData['path_stempel'] = $stempel->store('organization', 'public');
        }

        $profile->fill($updateData);
        $profile->save();

        return $profile;
    }
}
