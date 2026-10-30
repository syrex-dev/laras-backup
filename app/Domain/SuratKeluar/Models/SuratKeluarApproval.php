<?php

namespace App\Domain\SuratKeluar\Models;

use App\Domain\Pengguna\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class SuratKeluarApproval extends Model
{
    use HasFactory;

    protected $table = 'surat_keluar_approvals';

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DIPARAF = 'diparaf';
    public const STATUS_REVISI = 'revisi';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DILEWATI = 'dilewati';

    public const ROLE_PEMERIKSA = 'pemeriksa';
    public const ROLE_PARAF = 'paraf';
    public const ROLE_PENANDATANGAN = 'penandatangan';

    protected $fillable = [
        'surat_keluar_id',
        'step_order',
        'role_type',
        'user_id',
        'position_id',
        'delegated_user_id',
        'status',
        'catatan',
        'acted_at',
        'acted_user_id',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function suratKeluar(): BelongsTo
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function delegatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_user_id');
    }

    public function actedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_user_id');
    }

    /**
     * User yang berwenang bertindak saat ini (menghormati Plt/delegasi)
     */
    public function effectiveUser(): ?User
    {
        if ($this->delegated_user_id) {
            return $this->delegatedUser ?: User::find($this->delegated_user_id);
        }

        if ($this->user_id) {
            return $this->user ?: User::find($this->user_id);
        }

        // Jika hanya ditentukan berdasarkan jabatan, cari pejabat aktif atau Plt pada jabatan tersebut
        if ($this->position_id && $this->position) {
            $delegation = $this->position->activeDelegation();
            if ($delegation && $delegation->delegateUser) {
                return $delegation->delegateUser;
            }
            return $this->position->users->first();
        }

        return null;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function isDone(): bool
    {
        return in_array($this->status, [self::STATUS_DISETUJUI, self::STATUS_DIPARAF, self::STATUS_DILEWATI]);
    }
}
