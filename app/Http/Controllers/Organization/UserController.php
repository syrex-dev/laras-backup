<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Actions\CreateUserAction;
use App\Domain\Organization\Actions\DeleteUserAction;
use App\Domain\Organization\Actions\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreUserRequest;
use App\Http\Requests\Organization\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::query()->with('userRole')->latest()->paginate(10);

            return view('organization.users.index', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function create()
    {
        try {
            $roles = Role::all();

            return view('organization.users.create', compact('roles'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function store(StoreUserRequest $request, CreateUserAction $createAction)
    {
        try {
            $validated = $request->validated();
            $user = $createAction->execute($validated);
            $roleName = optional(Role::find($validated['role_id']))->name ?? '';

            return redirect()->route('users.index')->with('success', "User {$user->nama} berhasil ditambahkan dengan role {$roleName}.");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan user: '.$e->getMessage());
        }
    }

    public function edit(User $user)
    {
        try {
            $roles = Role::all();

            return view('organization.users.edit', compact('user', 'roles'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $updateAction)
    {
        try {
            $updateAction->execute($user, $request->validated());

            return redirect()->route('users.index')->with('success', 'User berhasil diperbarui dan role telah disinkronkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui user: '.$e->getMessage());
        }
    }

    public function destroy(User $user, DeleteUserAction $deleteAction)
    {
        try {
            $deleteAction->execute($user, auth()->id());

            return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus user: '.$e->getMessage());
        }
    }
}
