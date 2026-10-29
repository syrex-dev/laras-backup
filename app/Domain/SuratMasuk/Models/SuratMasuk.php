<?php

namespace App\Domain\SuratMasuk\Models;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @mixin Builder
 */
class SuratMasuk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'surat_masuk';

    protected $fillable = [
        'nomor_agenda',
        'nomor_surat',
        'klasifikasi_id',
        'unit_tujuan_id',
        'tanggal_surat',
        'tanggal_diterima',
        'pengirim',
        'perihal',
        'sifat',
        'jenis',
        'status',
        'ringkasan',
        'keterangan',
        'alasan_batal',
        'dibatalkan_oleh_id',
        'dibatalkan_pada',
        'file_path',
        'status_ekstraksi_ai',
        'qr_token',
        'search_text',
        'user_id',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_diterima' => 'date',
        'dibatalkan_pada' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->qr_token)) {
                $model->qr_token = Str::random(32);
            }
        });

        static::saving(function ($model) {
            $model->search_text = trim(
                "{$model->nomor_agenda} {$model->nomor_surat} {$model->pengirim} {$model->perihal} {$model->keterangan}"
            );
        });
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'unit_tujuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SuratMasukAttachment::class, 'surat_masuk_id');
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'surat_masuk_id');
    }

    /**
     * Scope untuk pencarian dan filter server-side efisien tanpa N+1
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('search_text', 'LIKE', "%{$search}%")
                        ->orWhere('nomor_agenda', 'LIKE', "%{$search}%")
                        ->orWhere('nomor_surat', 'LIKE', "%{$search}%")
                        ->orWhere('pengirim', 'LIKE', "%{$search}%")
                        ->orWhere('perihal', 'LIKE', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'semua') {
                    $query->where('status', $status);
                }
            })
            ->when($filters['sifat'] ?? null, function ($query, $sifat) {
                if ($sifat !== 'semua') {
                    $query->where('sifat', $sifat);
                }
            })
            ->when($filters['unit_id'] ?? null, function ($query, $unitId) {
                if ($unitId !== 'semua') {
                    $query->where('unit_tujuan_id', $unitId);
                }
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->whereDate('tanggal_diterima', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->whereDate('tanggal_diterima', '<=', $endDate);
            });
    }

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan';
    }
}
