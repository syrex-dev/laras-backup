<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 */
class KlasifikasiSurat extends Model
{
    protected $table = 'klasifikasi_surat';

    protected $fillable = [
        'kode_klasifikasi',
        'nama',
        'keterangan',
    ];

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class, 'klasifikasi_id');
    }

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class, 'klasifikasi_id');
    }
}
