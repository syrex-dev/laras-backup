<?php

namespace App\Domain\Organization\Actions;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::create([
            'nama'     => $data['nama'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $data['role_id'],
        ]);

        $role = Role::find($data['role_id']);
        if ($role) {
            $user->assignRole($role->name);
        }

        return $user;
    }
}
