<?php

namespace App\Domain\Lisensi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaUsage extends Model
{
    use HasFactory;

    protected $table = 'quota_usages';

    public $timestamps = false;

    protected $fillable = [
        'recorded_at',
        'active_users_count',
        'storage_used_bytes',
        'files_count',
        'details',
        'created_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'active_users_count' => 'integer',
        'storage_used_bytes' => 'integer',
        'files_count' => 'integer',
        'details' => 'array',
    ];
}
