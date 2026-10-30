<?php

namespace App\Domain\SuratKeluar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class TemplateSurat extends Model
{
    use HasFactory;

    protected $table = 'template_surat';

    public const JENIS_NOTA_DINAS = 'nota_dinas';
    public const JENIS_SURAT_TUGAS = 'surat_tugas';
    public const JENIS_EDARAN = 'edaran';
    public const JENIS_SK = 'sk';
    public const JENIS_UNDANGAN = 'undangan';
    public const JENIS_BIASA = 'biasa';

    protected $fillable = [
        'nama',
        'jenis',
        'kode',
        'format_nomor',
        'konten_default',
        'variabel',
        'use_institution_header',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variabel' => 'array',
        'use_institution_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suratKeluar(): HasMany
    {
        return $this->hasMany(SuratKeluar::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('nama');
    }

    public function scopeJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }
}
