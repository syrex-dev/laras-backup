<?php

namespace App\Models;

use App\Domain\Instansi\Models\InstitutionProfile;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Gunakan App\Domain\Instansi\Models\InstitutionProfile sebagai satu-satunya model profil instansi resmi.
 */
class ProfilOrganisasi extends Model
{
    protected $table = 'profil_organisasi';

    protected $fillable = [
        'nama_instansi',
        'kode_organisasi',
        'alamat',
        'email',
        'telepon',
        'path_logo',
        'path_stempel',
        'tema_default',
    ];
}
