<?php

namespace App\Policies;

use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuratKeluarPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('surat-keluar.lihat') ||
               $user->can('surat-keluar.buat') ||
               $user->can('surat-keluar.periksa') ||
               $user->can('surat-keluar.kelola');
    }

    public function view(User $user, SuratKeluar $suratKeluar): bool
    {
        // Admin pengelola surat keluar selalu berhak
        if ($user->can('surat-keluar.kelola')) {
            return true;
        }

        // Pembuat surat keluar
        if ($suratKeluar->user_id === $user->id) {
            return true;
        }

        // Pejabat penandatangan
        if ($suratKeluar->penandatangan_id === $user->id) {
            return true;
        }

        // Terlibat dalam rantai persetujuan (pemeriksa, paraf, atau Plt)
        $isInApprovals = $suratKeluar->approvals->contains(function ($approval) use ($user) {
            return $approval->user_id === $user->id ||
                   $approval->delegated_user_id === $user->id ||
                   ($approval->position_id && $user->position_id === $approval->position_id);
        });

        if ($isInApprovals) {
            return true;
        }

        // Jika surat telah diterbitkan nomornya dan bukan rahasia, pegawai satu unit berhak melihat
        if ($suratKeluar->isBernomor() && $suratKeluar->sifat !== 'rahasia') {
            if ($user->position && $user->position->org_unit_id === $suratKeluar->org_unit_id) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('surat-keluar.buat') || $user->can('surat-keluar.kelola');
    }

    public function update(User $user, SuratKeluar $suratKeluar): bool
    {
        if ($user->can('surat-keluar.kelola')) {
            return $suratKeluar->canBeEdited();
        }

        // Hanya pembuat yang berhak mengedit dan hanya saat draf atau diminta revisi
        return $suratKeluar->user_id === $user->id && $suratKeluar->canBeEdited();
    }

    public function delete(User $user, SuratKeluar $suratKeluar): bool
    {
        if ($user->can('surat-keluar.kelola')) {
            return $suratKeluar->status === SuratKeluar::STATUS_DRAF;
        }

        // Hanya pembuat yang boleh menghapus dan hanya draf awal
        return $suratKeluar->user_id === $user->id && $suratKeluar->status === SuratKeluar::STATUS_DRAF;
    }

    public function cancel(User $user, SuratKeluar $suratKeluar): bool
    {
        return ($user->can('surat-keluar.batalkan') || $user->can('surat-keluar.kelola')) &&
               $suratKeluar->isBernomor();
    }
}
