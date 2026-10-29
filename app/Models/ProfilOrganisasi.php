<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
