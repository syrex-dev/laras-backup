<?php

namespace App\Domain\Pengguna\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';

    protected $fillable = [
        'org_unit_id',
        'name',
        'level',
        'is_penandatangan',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_penandatangan' => 'boolean',
    ];

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'position_id');
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(PositionDelegation::class, 'position_id');
    }

    public function activeDelegation(): ?PositionDelegation
    {
        $today = now()->toDateString();

        return $this->delegations()
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }
}
