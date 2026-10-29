@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
<div style="max-width:680px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('users.index') }}" style="color:var(--text-muted); text-decoration:none;">Pengguna</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Edit</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Edit Pengguna
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Perbarui data akun dan hak akses: <strong style="color:var(--color-primary);">{{ $user->nama }}</strong>
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Perbarui Data Akun</h2>
            <span class="badge badge-warning">Edit Mode</span>
        </div>

        @if($errors->any())
        <div style="padding:16px 20px 0;">
            <div class="alert alert-danger" style="margin:0;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                {{-- Nama Lengkap --}}
                <div class="form-group" style="margin:0;">
                    <label for="nama" class="form-label">
                        Nama Lengkap <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $user->nama) }}" required
                        placeholder="Nama lengkap"
                        class="form-control">
                    @error('nama') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Email --}}
                <div class="form-group" style="margin:0;">
                    <label for="email" class="form-label">
                        Alamat Email <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        placeholder="nama@email.com"
                        class="form-control">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Role --}}
                <div class="form-group" style="margin:0;">
                    <label for="role_id" class="form-label">
                        Hak Akses (Role) <span style="color:var(--color-danger);">*</span>
                    </label>
                    <select id="role_id" name="role_id" required class="form-control">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ strtoupper($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Password Grid (Optional on edit) --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                    <div class="form-group" style="margin:0;">
                        <label for="password" class="form-label">
                            Kata Sandi Baru <span style="color:var(--text-muted); font-weight:normal;">(opsional)</span>
                        </label>
                        <input type="password" id="password" name="password"
                            placeholder="Kosongkan jika tidak diubah"
                            class="form-control">
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="password_confirmation" class="form-label">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="Ulangi kata sandi baru"
                            class="form-control">
                    </div>
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <a href="{{ route('users.index') }}" class="btn btn-outline btn-sm">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
