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

    public function setIsiSuratAttribute(?string $value): void
    {
        $this->attributes['isi_surat'] = app(\App\Services\HtmlSanitizer::class)->sanitize($value);
    }

    public function setPerihalAttribute(?string $value): void
    {
        $this->attributes['perihal'] = $value !== null ? strip_tags($value) : null;
    }

    public function setTujuanAttribute(?string $value): void
    {
        $this->attributes['tujuan'] = $value !== null ? strip_tags($value) : null;
    }
}
