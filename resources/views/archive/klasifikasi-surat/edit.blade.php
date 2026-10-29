@extends('layouts.app')

@section('title', 'Edit Klasifikasi Surat')

@section('content')
<div style="max-width:680px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('klasifikasi-surat.index') }}" style="color:var(--text-muted); text-decoration:none;">Klasifikasi Surat</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Edit</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Edit Klasifikasi Surat
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Perbarui data kategori: <strong style="color:var(--color-primary);">{{ $klasifikasiSurat->kode_klasifikasi }}</strong> — {{ $klasifikasiSurat->nama }}
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Perbarui Data Klasifikasi</h2>
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

        <form action="{{ route('klasifikasi-surat.update', $klasifikasiSurat) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                {{-- Kode Klasifikasi --}}
                <div class="form-group" style="margin:0;">
                    <label for="kode_klasifikasi" class="form-label">
                        Kode Klasifikasi <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="kode_klasifikasi" name="kode_klasifikasi"
                        value="{{ old('kode_klasifikasi', $klasifikasiSurat->kode_klasifikasi) }}" required
                        class="form-control font-mono"
                        style="text-transform: uppercase;">
                    <span class="form-hint">Kode singkat unik untuk penomoran surat</span>
                    @error('kode_klasifikasi') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Nama Klasifikasi --}}
                <div class="form-group" style="margin:0;">
                    <label for="nama" class="form-label">
                        Nama Kategori / Klasifikasi <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="nama" name="nama"
                        value="{{ old('nama', $klasifikasiSurat->nama) }}" required
                        class="form-control">
                    @error('nama') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Keterangan --}}
                <div class="form-group" style="margin:0;">
                    <label for="keterangan" class="form-label">
                        Keterangan <span style="color:var(--text-muted); font-weight:normal;">(opsional)</span>
                    </label>
                    <textarea id="keterangan" name="keterangan" rows="3"
                        class="form-control">{{ old('keterangan', $klasifikasiSurat->keterangan) }}</textarea>
                    @error('keterangan') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <a href="{{ route('klasifikasi-surat.index') }}" class="btn btn-outline btn-sm">Batal</a>
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
