<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Disposisi extends Model
{
    protected $table = 'disposisi';

    protected $fillable = [
        'surat_masuk_id',
        'pengirim_id',
        'penerima_id',
        'instruksi',
        'prioritas',
        'status',
        'dibaca_pada',
        'selesai_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }
}
