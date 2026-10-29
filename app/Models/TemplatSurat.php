<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method bool|null delete()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TemplatSurat extends Model
{
    protected $table = 'templat_surat';

    protected $fillable = [
        'nama_templat',
        'path_file',
    ];
}
