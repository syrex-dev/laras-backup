@extends('layouts.app')

@section('title', 'Profil Organisasi & Kop Surat')

@section('content')
<div style="max-width:880px; margin:0 auto;" class="space-y-6">

    {{-- ── 1. Page Header ── --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <span style="color:var(--text-title); font-weight:600;">Organisasi</span>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Profil &amp; Kop</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Profil Organisasi &amp; Kop Surat
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Kelola identitas resmi instansi, logo, stempel digital, dan tema visual default aplikasi.
        </p>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Grid: Informasi Umum & Media Identitas --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:20px;">
            {{-- Informasi Umum --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Informasi Resmi</h2>
                    <span class="badge badge-primary">Instansi</span>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="form-group" style="margin:0;">
                        <label for="nama_instansi" class="form-label">
                            Nama Instansi / Perguruan Tinggi <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="text" id="nama_instansi" name="nama_instansi"
                            value="{{ old('nama_instansi', $profile->nama_instansi) }}" required
                            placeholder="Contoh: Politeknik Mardira Indonesia"
                            class="form-control">
                        @error('nama_instansi') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="kode_organisasi" class="form-label">
                            Singkatan / Kode Organisasi <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="text" id="kode_organisasi" name="kode_organisasi"
                            value="{{ old('kode_organisasi', $profile->kode_organisasi) }}" required
                            placeholder="Contoh: POLTEK-MI"
                            class="form-control font-mono"
                            style="text-transform: uppercase;">
                        @error('kode_organisasi') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="email" class="form-label">
                            Email Resmi Instansi <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                            value="{{ old('email', $profile->email) }}" required
                            placeholder="info@poltek-mardira.ac.id"
                            class="form-control">
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="telepon" class="form-label">
                            Nomor Telepon / Hotline
                        </label>
                        <input type="text" id="telepon" name="telepon"
                            value="{{ old('telepon', $profile->telepon) }}"
                            placeholder="Contoh: (022) 1234567"
                            class="form-control">
                        @error('telepon') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label for="tema_default" class="form-label">
                            Tema Bawaan (Default Theme)
                        </label>
                        <select id="tema_default" name="tema_default" class="form-control">
                            <option value="light" {{ old('tema_default', $profile->tema_default ?? 'light') === 'light' ? 'selected' : '' }}>Light Mode (Mantis Terang)</option>
                            <option value="dark" {{ old('tema_default', $profile->tema_default ?? 'light') === 'dark' ? 'selected' : '' }}>Dark Mode (Mantis Gelap)</option>
                        </select>
                        <span class="form-hint">Halaman login dan sesi tamu akan mengacu pada pilihan tema ini.</span>
                    </div>
                </div>
            </div>

            {{-- Media Identitas: Logo & Stempel --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Media &amp; Stempel Resmi</h2>
                    <span class="badge badge-info">Format Gambar</span>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:20px;">
                    {{-- Logo --}}
                    <div>
                        <label class="form-label">Logo Instansi</label>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:72px; height:72px; border-radius:var(--radius-base); border:1px solid var(--border-color); background:var(--bg-surface-2); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;">
                                @if($profile->path_logo)
                                    <img src="{{ asset('storage/' . $profile->path_logo) }}" alt="Logo" style="max-width:100%; max-height:100%; object-fit:contain; padding:6px;">
                                @else
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                @endif
                            </div>
                            <div style="flex:1;">
                                <input type="file" name="logo" accept="image/*" class="form-control" style="font-size:0.8rem; padding:6px 10px;">
                                <span class="form-hint">Format PNG/JPG transparan, rasio kotak disarankan</span>
                            </div>
                        </div>
                        @error('logo') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Stempel --}}
                    <div>
                        <label class="form-label">Stempel Resmi (Transparan)</label>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:72px; height:72px; border-radius:var(--radius-base); border:1px solid var(--border-color); background:var(--bg-surface-2); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;">
                                @if($profile->path_stempel)
                                    <img src="{{ asset('storage/' . $profile->path_stempel) }}" alt="Stempel" style="max-width:100%; max-height:100%; object-fit:contain; padding:6px;">
                                @else
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                @endif
                            </div>
                            <div style="flex:1;">
                                <input type="file" name="stempel" accept="image/png" class="form-control" style="font-size:0.8rem; padding:6px 10px;">
                                <span class="form-hint">PNG transparan tanpa latar belakang (maks. 2MB)</span>
                            </div>
                        </div>
                        @error('stempel') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Alamat Kop Card --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Alamat Resmi untuk Kop Surat</h2>
            </div>
            <div class="card-body">
                <div class="form-group" style="margin:0;">
                    <label for="alamat" class="form-label">
                        Alamat Lengkap &amp; Kontak Kop <span style="color:var(--color-danger);">*</span>
                    </label>
                    <textarea id="alamat" name="alamat" rows="3" required
                        placeholder="Contoh: Jl. Soekarno-Hatta No. 211 Bandung 40235, Telp: (022) 5231234, Website: www.poltek-mardira.ac.id"
                        class="form-control">{{ old('alamat', $profile->alamat) }}</textarea>
                    <span class="form-hint">Teks ini akan dicetak otomatis di bagian bawah kop surat resmi seluruh surat keluar.</span>
                    @error('alamat') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Pengaturan Organisasi</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
