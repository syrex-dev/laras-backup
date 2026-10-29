@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div style="max-width:680px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('users.index') }}" style="color:var(--text-muted); text-decoration:none;">Pengguna</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Tambah</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Tambah Pengguna Baru
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Daftarkan akun staf/pejabat baru dan tetapkan peran hak akses ke dalam sistem.
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Formulir Akun Pengguna</h2>
            <span class="badge badge-primary">Baru</span>
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

        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                {{-- Nama Lengkap --}}
                <div class="form-group" style="margin:0;">
                    <label for="nama" class="form-label">
                        Nama Lengkap <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                        placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Kom"
                        class="form-control">
                    @error('nama') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Email --}}
                <div class="form-group" style="margin:0;">
                    <label for="email" class="form-label">
                        Alamat Email <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        placeholder="nama@poltek-mardira.ac.id"
                        class="form-control">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Role --}}
                <div class="form-group" style="margin:0;">
                    <label for="role_id" class="form-label">
                        Hak Akses (Role) <span style="color:var(--color-danger);">*</span>
                    </label>
                    <select id="role_id" name="role_id" required class="form-control">
                        <option value="">— Pilih Role Akses —</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ strtoupper($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Password Grid --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                    <div class="form-group" style="margin:0;">
                        <label for="password" class="form-label">
                            Kata Sandi <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                            placeholder="Minimal 8 karakter"
                            class="form-control">
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="password_confirmation" class="form-label">
                            Konfirmasi Kata Sandi <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            placeholder="Ulangi kata sandi"
                            class="form-control">
                    </div>
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <a href="{{ route('users.index') }}" class="btn btn-outline btn-sm">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span>Simpan Pengguna</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
