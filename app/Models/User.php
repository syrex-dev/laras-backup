<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role_id',
    ];

    // Removed custom role() to avoid shadowing Spatie's scopeRole()

    public function userRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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
