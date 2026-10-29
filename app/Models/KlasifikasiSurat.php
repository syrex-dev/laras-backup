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

    public function getKodeAttribute(): string
    {
        return $this->attributes['kode_klasifikasi'] ?? '';
    }

    public function setKodeAttribute($value): void
    {
        $this->attributes['kode_klasifikasi'] = $value;
    }

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class, 'klasifikasi_id');
    }

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class, 'klasifikasi_id');
    }
}
