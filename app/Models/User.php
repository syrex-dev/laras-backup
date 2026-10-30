<?php

namespace App\Models;

use App\Domain\Pengguna\Models\Position;
use App\Domain\Pengguna\Models\PositionDelegation;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'nama',
        'nip',
        'email',
        'phone',
        'password',
        'role_id',
        'position_id',
        'is_active',
        'signature_image_path',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $value ?? $attributes['nama'] ?? '',
            set: fn ($value) => [
                'name' => $value,
                'nama' => $value,
            ]
        );
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function delegations()
    {
        return $this->hasMany(PositionDelegation::class, 'delegate_user_id');
    }

    /**
     * @deprecated Relasi lama role_id TIDAK BOLEH digunakan untuk otorisasi sistem.
     * Seluruh otorisasi sistem wajib menggunakan Spatie Permission (HasRoles) via $user->can('...').
     */
    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function suratMasuk()
    {
        return $this->hasMany(SuratMasuk::class);
    }

    public function suratKeluar()
    {
        return $this->hasMany(SuratKeluar::class);
    }

    public function disposisiDikirim()
    {
        return $this->hasMany(Disposisi::class, 'pengirim_id');
    }

    public function disposisiDiterima()
    {
        return $this->hasMany(Disposisi::class, 'penerima_id');
    }

    public function tandaTangan()
    {
        return $this->hasOne(TandaTangan::class);
    }
}
