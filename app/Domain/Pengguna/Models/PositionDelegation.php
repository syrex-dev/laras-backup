<?php

namespace App\Domain\Pengguna\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionDelegation extends Model
{
    use HasFactory;

    protected $table = 'position_delegations';

    protected $fillable = [
        'position_id',
        'original_user_id',
        'delegate_user_id',
        'start_date',
        'end_date',
        'reason',
        'surat_tugas_number',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function originalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_user_id');
    }

    public function delegateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }
}
