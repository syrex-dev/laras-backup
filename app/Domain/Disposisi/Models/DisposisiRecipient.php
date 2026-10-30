<?php

namespace App\Domain\Disposisi\Models;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class DisposisiRecipient extends Model
{
    use HasFactory;

    protected $table = 'disposisi_recipients';

    public const STATUS_BELUM_DIBACA = 'belum_dibaca';
    public const STATUS_DIBACA = 'dibaca';
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITERUSKAN = 'diteruskan';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    protected $fillable = [
        'disposisi_id',
        'parent_recipient_id',
        'recipient_user_id',
        'recipient_position_id',
        'recipient_unit_id',
        'delegated_user_id',
        'is_delegated',
        'status',
        'dibaca_pada',
        'selesai_pada',
        'catatan_tindak_lanjut',
        'surat_keluar_id',
    ];

    protected $casts = [
        'is_delegated' => 'boolean',
        'dibaca_pada' => 'datetime',
        'selesai_pada' => 'datetime',
        'surat_keluar_id' => 'integer',
    ];

    public function disposisi(): BelongsTo
    {
        return $this->belongsTo(Disposisi::class, 'disposisi_id');
    }

    public function parentRecipient(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_recipient_id');
    }

    public function childRecipients(): HasMany
    {
        return $this->hasMany(self::class, 'parent_recipient_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function recipientPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'recipient_position_id');
    }

    public function recipientUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'recipient_unit_id');
    }

    public function delegatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_user_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(DisposisiFollowup::class, 'disposisi_recipient_id')->orderBy('created_at', 'desc');
    }

    public function suratKeluar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SuratKeluar\Models\SuratKeluar::class, 'surat_keluar_id');
    }

    /**
     * Pengguna efektif yang berhak mengeksekusi disposisi (Pejabat Definitif atau Pejabat Plt aktif)
     */
    public function effectiveUser(): ?User
    {
        if ($this->is_delegated && $this->delegated_user_id) {
            return $this->delegatedUser ?: User::find($this->delegated_user_id);
        }

        return $this->recipientUser ?: User::find($this->recipient_user_id);
    }

    /**
     * Label teks pendelegasian resmi "atas nama jabatan X"
     */
    public function getDelegationLabelAttribute(): string
    {
        if ($this->is_delegated && $this->recipientPosition) {
            return "atas nama jabatan {$this->recipientPosition->name}";
        }

        return '';
    }

    /**
     * Nama tampilan penerima disertai indikator Plt bila sedang didelegasikan
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_delegated && $this->delegatedUser) {
            $posName = $this->recipientPosition?->name ?? 'Jabatan';
            return "{$this->delegatedUser->name} (Plt. {$posName})";
        }

        return $this->recipientUser?->name ?? $this->recipientPosition?->name ?? 'Penerima';
    }

    /**
     * Memeriksa apakah tindak lanjut penerima telah melewati tenggat waktu
     */
    public function isOverdue(): bool
    {
        if (! $this->disposisi || ! $this->disposisi->tenggat_waktu) {
            return false;
        }

        $isFinished = in_array($this->status, [
            self::STATUS_SELESAI,
            self::STATUS_DITOLAK,
            self::STATUS_DIKEMBALIKAN,
        ], true);

        return ! $isFinished && now()->startOfDay()->gt($this->disposisi->tenggat_waktu->startOfDay());
    }

    /**
     * Memeriksa apakah tenggat waktu mendekat (misal H-1 atau hari ini)
     */
    public function isDueSoon(int $daysThreshold = 1): bool
    {
        if (! $this->disposisi || ! $this->disposisi->tenggat_waktu) {
            return false;
        }

        $isFinished = in_array($this->status, [
            self::STATUS_SELESAI,
            self::STATUS_DITOLAK,
            self::STATUS_DIKEMBALIKAN,
        ], true);

        if ($isFinished) {
            return false;
        }

        $deadline = $this->disposisi->tenggat_waktu->startOfDay();
        $today = now()->startOfDay();
        $diffDays = $today->diffInDays($deadline, false);

        return $diffDays >= 0 && $diffDays <= $daysThreshold;
    }
}
