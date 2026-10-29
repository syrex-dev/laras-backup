<?php

namespace App\Domain\Pengguna\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgUnit extends Model
{
    use HasFactory;

    protected $table = 'org_units';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'order_index',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_index');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'org_unit_id');
    }
}
