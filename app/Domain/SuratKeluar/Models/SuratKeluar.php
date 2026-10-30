<?php

namespace App\Domain\SuratKeluar\Models;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Builder
 */
class SuratKeluar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'surat_keluar';

    public const STATUS_DRAF = 'draf';
    public const STATUS_DIPERIKSA = 'diperiksa';
    public const STATUS_REVISI = 'revisi';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_BERNOMOR = 'bernomor';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const SIFAT_BIASA = 'biasa';
    public const SIFAT_PENTING = 'penting';
    public const SIFAT_SEGERA = 'segera';
    public const SIFAT_RAHASIA = 'rahasia';

    protected $fillable = [
        'nomor_surat',
        'nomor_agenda',
        'tanggal_surat',
        'template_id',
        'klasifikasi_id',
        'org_unit_id',
        'user_id',
        'penandatangan_id',
        'reply_to_surat_masuk_id',
        'tujuan',
        'perihal',
        'isi_surat',
        'isi',              // Kolom warisan prototipe
        'status',
        'status_approval',  // Kolom warisan prototipe
        'sifat',
        'current_approval_step',
        'alasan_batal',
        'alasan_tolak',
        'catatan_revisi',
        'metadata',
        'qr_token',
        'file_pdf',
        'lampiran',
        'templat_surat_id',
        'penyetuju_id',
        'path_qrcode',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'metadata' => 'array',
        'current_approval_step' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateSurat::class, 'template_id');
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->pembuat();
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'reply_to_surat_masuk_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SuratKeluarVersion::class, 'surat_keluar_id')->orderBy('version_number', 'desc');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SuratKeluarApproval::class, 'surat_keluar_id')->orderBy('step_order', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SuratKeluarAttachment::class, 'surat_keluar_id');
    }

    public function disposisiRecipient(): HasOne
    {
        return $this->hasOne(DisposisiRecipient::class, 'surat_keluar_id');
    }

    public function currentApproval(): ?SuratKeluarApproval
    {
        return $this->approvals()
            ->where('step_order', $this->current_approval_step)
            ->where('status', SuratKeluarApproval::STATUS_MENUNGGU)
            ->first();
    }

    public function isBernomor(): bool
    {
        return $this->status === self::STATUS_BERNOMOR && ! empty($this->nomor_surat);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAF, self::STATUS_REVISI]);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->penandatangan();
    }

    public function templatSurat(): BelongsTo
    {
        return $this->template();
    }

    public function getPenyetujuIdAttribute(): ?int
    {
        return $this->attributes['penandatangan_id'] ?? null;
    }

    public function setPenyetujuIdAttribute($value): void
    {
        $this->attributes['penandatangan_id'] = $value;
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'penyetuju_id')) {
            $this->attributes['penyetuju_id'] = $value;
        }
    }

    public function getTemplatSuratIdAttribute(): ?int
    {
        return $this->attributes['templat_surat_id'] ?? $this->attributes['template_id'] ?? null;
    }

    public function setTemplatSuratIdAttribute($value): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'templat_surat_id')) {
            $this->attributes['templat_surat_id'] = $value;
        }
    }

    /**
     * Scope query untuk filter pencarian, status, sifat, unit, dan rentang tanggal
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = trim($filters['search']);
                $q->where(function ($sub) use ($search) {
                    $sub->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('tujuan', 'like', "%{$search}%")
                        ->orWhere('nomor_agenda', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['sifat']), function ($q) use ($filters) {
                $q->where('sifat', $filters['sifat']);
            })
            ->when(! empty($filters['org_unit_id']), function ($q) use ($filters) {
                $q->where('org_unit_id', $filters['org_unit_id']);
            })
            ->when(! empty($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('tanggal_surat', '>=', $filters['start_date']);
            })
            ->when(! empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('tanggal_surat', '<=', $filters['end_date']);
            });
    }
}
