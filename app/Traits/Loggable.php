<?php

namespace App\Traits;

use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Loggable
{
    public function logActivity($aksi, $namaTabel = null, $idRekaman = null)
    {
        LogAktivitas::create([
            'user_id'    => Auth::id(),
            'aksi'       => $aksi,
            'nama_tabel' => $namaTabel,
            'id_rekaman' => $idRekaman,
            'alamat_ip'  => Request::ip(),
        ]);
    }
}
