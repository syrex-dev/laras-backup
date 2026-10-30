<?php

namespace App\Domain\SuratKeluar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class SuratKeluarVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'surat_keluar_versions';

    protected $fillable = [
        'surat_keluar_id',
        'user_id',
        'version_number',
        'perihal',
        'isi_surat',
        'tujuan',
        'metadata',
        'catatan_perubahan',
        'created_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function suratKeluar(): BelongsTo
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
