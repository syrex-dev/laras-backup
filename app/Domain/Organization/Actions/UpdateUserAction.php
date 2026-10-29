<?php

namespace App\Domain\Organization\Actions;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    public function execute(User $user, array $data): bool
    {
        $updateData = [
            'nama' => $data['nama'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $updated = $user->update($updateData);

        $role = Role::find($data['role_id']);
        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return $updated;
    }
}
