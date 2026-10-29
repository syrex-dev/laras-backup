<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TandaTangan extends Model
{
    protected $table = 'tanda_tangan';

    protected $fillable = [
        'user_id',
        'path_tanda_tangan',
        'path_stempel',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
