<?php

namespace App\Policies;

use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisposisiPolicy
{
    use HandlesAuthorization;

    /**
     * Hak akses membuka daftar / kotak masuk disposisi
     */
    public function viewAny(User $user): bool
    {
        return $user->can('disposisi.lihat');
    }

    /**
     * Hak akses melihat detail satu naskah disposisi tertentu
     */
    public function view(User $user, ?Disposisi $disposisi = null): bool
    {
        if (! $user->can('disposisi.lihat')) {
            return false;
        }

        if ($user->can('disposisi.kelola')) {
            return true;
        }

        if (! $disposisi) {
            return true;
        }

        // Pembuat disposisi selalu berhak melihat
        if ($disposisi->pemberi_id === $user->id) {
            return true;
        }

        // Penerima aktif atau penerima Plt berhak melihat
        $isRecipient = $disposisi->recipients->contains(function (DisposisiRecipient $rec) use ($user) {
            return $rec->effectiveUser()?->id === $user->id;
        });

        if ($isRecipient) {
            return true;
        }

        // Periksa rantai leluhur disposisi jika merupakan hasil penerusan
        $parent = $disposisi->parentDisposisi;
        while ($parent) {
            if ($parent->pemberi_id === $user->id) {
                return true;
            }
            if ($parent->recipients->contains(fn ($r) => $r->effectiveUser()?->id === $user->id)) {
                return true;
            }
            $parent = $parent->parentDisposisi;
        }

        return false;
    }

    /**
     * Hak akses membuat naskah disposisi baru
     */
    public function create(User $user): bool
    {
        return $user->can('disposisi.buat');
    }

    /**
     * Hak akses meneruskan disposisi ke bawahan / penerima lanjutan
     */
    public function forward(User $user, ?DisposisiRecipient $recipient = null): bool
    {
        if (! $user->can('disposisi.teruskan')) {
            return false;
        }

        if ($recipient) {
            if ($user->can('disposisi.kelola')) {
                return true;
            }

            return $recipient->effectiveUser()?->id === $user->id;
        }

        return true;
    }

    /**
     * Hak akses menindaklanjuti dan mengunggah bukti penyelesaian disposisi
     */
    public function followup(User $user, ?DisposisiRecipient $recipient = null): bool
    {
        if (! $user->can('disposisi.tindaklanjut')) {
            return false;
        }

        if ($recipient) {
            if ($user->can('disposisi.kelola')) {
                return true;
            }

            return $recipient->effectiveUser()?->id === $user->id;
        }

        return true;
    }
}
