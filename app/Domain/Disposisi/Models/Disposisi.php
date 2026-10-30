<?php

namespace App\Domain\Disposisi\Models;

use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class Disposisi extends Model
{
    use HasFactory;

    protected $table = 'disposisi';

    public const STATUS_DIDISPOSISIKAN = 'didisposisikan';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    public const PRIORITAS_BIASA = 'biasa';
    public const PRIORITAS_SEGERA = 'segera';
    public const PRIORITAS_SANGAT_SEGERA = 'sangat_segera';

    protected $fillable = [
        'surat_masuk_id',
        'parent_disposisi_id',
        'pemberi_id',
        'pengirim_id',
        'penerima_id',
        'pemberi_position_id',
        'instruksi',
        'catatan',
        'tenggat_waktu',
        'prioritas',
        'status',
        'dibaca_pada',
        'selesai_pada',
    ];

    protected $casts = [
        'tenggat_waktu' => 'date',
        'dibaca_pada' => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_disposisi_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_disposisi_id');
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemberi_id')->withDefault(function ($user, $disposisi) {
            return $disposisi->pengirim_id ? User::find($disposisi->pengirim_id) : null;
        });
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function pemberiPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'pemberi_position_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DisposisiRecipient::class, 'disposisi_id');
    }

    /**
     * Cek apakah seluruh penerima pada lembar disposisi ini telah menyelesaikan atau mengembalikan tugasnya
     */
    public function isAllRecipientsFinished(): bool
    {
        $recipients = $this->recipients;
        if ($recipients->isEmpty()) {
            return true;
        }

        return $recipients->every(function ($recipient) {
            return in_array($recipient->status, [
                DisposisiRecipient::STATUS_SELESAI,
                DisposisiRecipient::STATUS_DIKEMBALIKAN,
                DisposisiRecipient::STATUS_DITOLAK,
            ], true);
        });
    }
}
