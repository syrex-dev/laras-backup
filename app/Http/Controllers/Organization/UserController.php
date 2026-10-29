<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\Position;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['position.orgUnit', 'roles'])
            ->latest()
            ->paginate(15);

        $positions = Position::with('orgUnit')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $license = LicenseState::current();
        $activeUsersCount = User::where('is_active', true)->count();

        if ($request->header('X-Inertia')) {
            return Inertia::render('Organisasi/Pengguna', [
                'users' => $users,
                'positions' => $positions,
                'roles' => $roles,
                'quota' => [
                    'active_count' => $activeUsersCount,
                    'max_users' => $license->max_users,
                ],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'quota' => [
                    'active_count' => $activeUsersCount,
                    'max_users' => $license->max_users,
                ],
            ]);
        }

        return view('organization.users.index', compact('users', 'positions', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        $positions = Position::all();

        return view('organization.users.create', compact('roles', 'positions'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $positions = Position::all();

        return view('organization.users.edit', compact('user', 'roles', 'positions'));
    }

    public function store(Request $request)
    {
        $license = LicenseState::current();
        $isActive = $request->boolean('is_active', true);

        // Validasi kuota user dari entitlement lisensi
        if ($isActive) {
            $currentActiveCount = User::where('is_active', true)->count();
            if ($currentActiveCount >= $license->max_users) {
                throw ValidationException::withMessages([
                    'is_active' => "Batas kuota pengguna aktif telah tercapai ({$license->max_users} pengguna). Tingkatkan paket lisensi untuk menambah pengguna baru.",
                ]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6',
            'position_id' => 'nullable|exists:positions,id',
            'role' => 'required|string|exists:roles,name',
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar pada pengguna lain.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'position_id.exists' => 'Jabatan yang dipilih tidak valid.',
            'role.required' => 'Peran (role) pengguna wajib dipilih.',
            'role.exists' => 'Peran (role) tidak valid.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'nama' => $validated['name'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'position_id' => $validated['position_id'] ?? null,
            'is_active' => $isActive,
        ]);

        $user->assignRole($validated['role']);

        AuditLogger::log(
            action: 'user.created',
            subject: $user,
            description: "Pengguna baru '{$user->name}' ({$user->email}) berhasil didaftarkan dengan peran '{$validated['role']}'",
            properties: [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $validated['role'],
                'position_id' => $user->position_id,
            ]
        );

        return back()->with('success', "Pengguna {$user->name} berhasil ditambahkan!");
    }

    public function update(Request $request, User $user)
    {
        $license = LicenseState::current();
        $isActive = $request->boolean('is_active', true);

        // Jika mengubah status dari tidak aktif menjadi aktif, cek kuota
        if ($isActive && ! $user->is_active) {
            $currentActiveCount = User::where('is_active', true)->count();
            if ($currentActiveCount >= $license->max_users) {
                throw ValidationException::withMessages([
                    'is_active' => "Batas kuota pengguna aktif telah tercapai ({$license->max_users} pengguna). Tidak dapat mengaktifkan pengguna ini.",
                ]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50|unique:users,nip,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6',
            'position_id' => 'nullable|exists:positions,id',
            'role' => 'required|string|exists:roles,name',
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'role.required' => 'Peran (role) pengguna wajib dipilih.',
        ]);

        $oldData = [
            'name' => $user->name,
            'email' => $user->email,
            'position_id' => $user->position_id,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];

        $updateData = [
            'name' => $validated['name'],
            'nama' => $validated['name'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'position_id' => $validated['position_id'] ?? null,
            'is_active' => $isActive,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        AuditLogger::log(
            action: 'user.updated',
            subject: $user,
            description: "Data pengguna '{$user->name}' ({$user->email}) berhasil diperbarui",
            properties: [
                'old' => $oldData,
                'new' => array_merge($updateData, ['role' => $validated['role']]),
            ]
        );

        return back()->with('success', "Pengguna {$user->name} berhasil diperbarui!");
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        $user->delete();

        AuditLogger::log(
            action: 'user.deleted',
            subject: 'User#'.$userData['id'],
            description: "Pengguna '{$userData['name']}' ({$userData['email']}) telah dihapus dari sistem",
            properties: $userData
        );

        return back()->with('success', "Pengguna {$userData['name']} berhasil dihapus!");
    }
}
