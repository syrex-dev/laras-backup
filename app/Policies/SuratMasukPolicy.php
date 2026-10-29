<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuratMasukPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('surat-masuk.lihat');
    }

    public function view(User $user, $suratMasuk = null): bool
    {
        return $user->can('surat-masuk.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('surat-masuk.registrasi');
    }

    public function update(User $user, $suratMasuk = null): bool
    {
        return $user->can('surat-masuk.ubah');
    }

    public function cancel(User $user, $suratMasuk = null): bool
    {
        return $user->can('surat-masuk.batalkan');
    }

    public function delete(User $user, $suratMasuk = null): bool
    {
        return $user->hasAnyRole(['Admin Instansi', 'Admin']);
    }
}
