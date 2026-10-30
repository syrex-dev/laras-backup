<?php

namespace App\Policies;

use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplateSuratPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('surat-keluar.buat') || $user->can('surat-keluar.template') || $user->can('organisasi.kelola');
    }

    public function view(User $user, TemplateSurat $template): bool
    {
        return $user->can('surat-keluar.buat') || $user->can('surat-keluar.template') || $user->can('organisasi.kelola');
    }

    public function create(User $user): bool
    {
        return $user->can('surat-keluar.template') || $user->can('organisasi.kelola');
    }

    public function update(User $user, TemplateSurat $template): bool
    {
        return $user->can('surat-keluar.template') || $user->can('organisasi.kelola');
    }

    public function delete(User $user, TemplateSurat $template): bool
    {
        return $user->can('surat-keluar.template') || $user->can('organisasi.kelola');
    }
}
