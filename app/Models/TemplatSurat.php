<?php

namespace App\Models;

use App\Domain\SuratKeluar\Models\TemplateSurat;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Gunakan App\Domain\SuratKeluar\Models\TemplateSurat sebagai satu-satunya model template surat resmi.
 */
class TemplatSurat extends Model
{
    protected $table = 'templat_surat';

    protected $fillable = [
        'nama_templat',
        'path_file',
    ];
}
