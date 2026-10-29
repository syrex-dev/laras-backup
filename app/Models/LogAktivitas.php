<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'aksi',
        'nama_tabel',
        'id_rekaman',
        'alamat_ip',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
