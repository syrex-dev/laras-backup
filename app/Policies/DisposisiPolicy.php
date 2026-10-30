<?php

namespace App\Policies;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisposisiPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Instansi', 'Admin', 'Pimpinan', 'Kepala Unit', 'Staf TU', 'Sekretaris', 'Staf'])
            || $user->can('disposisi.lihat');
    }

    public function view(User $user, ?Disposisi $disposisi = null): bool
    {
        if ($user->hasAnyRole(['Admin Instansi', 'Admin']) || $user->can('disposisi.lihat')) {
            return true;
        }

        if ($disposisi) {
            if ($disposisi->pemberi_id === $user->id) {
                return true;
            }

            return $disposisi->recipients->contains(function (DisposisiRecipient $rec) use ($user) {
                return $rec->effectiveUser()?->id === $user->id;
            });
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Instansi', 'Admin', 'Pimpinan']) || $user->can('disposisi.buat');
    }

    public function forward(User $user, ?DisposisiRecipient $recipient = null): bool
    {
        if (! $user->hasAnyRole(['Admin Instansi', 'Admin', 'Pimpinan', 'Kepala Unit']) && ! $user->can('disposisi.teruskan')) {
            return false;
        }

        if ($recipient) {
            if ($user->hasAnyRole(['Admin Instansi', 'Admin'])) {
                return true;
            }

            return $recipient->effectiveUser()?->id === $user->id;
        }

        return true;
    }

    public function followup(User $user, ?DisposisiRecipient $recipient = null): bool
    {
        if (! $user->hasAnyRole(['Admin Instansi', 'Admin', 'Pimpinan', 'Kepala Unit', 'Staf']) && ! $user->can('disposisi.tindaklanjut')) {
            return false;
        }

        if ($recipient) {
            if ($user->hasAnyRole(['Admin Instansi', 'Admin'])) {
                return true;
            }

            return $recipient->effectiveUser()?->id === $user->id;
        }

        return true;
    }
}
