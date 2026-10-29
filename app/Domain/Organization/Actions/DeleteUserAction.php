<?php

namespace App\Domain\Organization\Actions;

use App\Models\User;

class DeleteUserAction
{
    public function execute(User $user, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            throw new \DomainException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        return (bool) $user->delete();
    }
}
