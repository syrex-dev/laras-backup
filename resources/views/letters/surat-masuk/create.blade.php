@extends('layouts.app')

@section('title', 'Tambah Surat Masuk')

@section('content')
<div style="max-width:820px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('surat-masuk.index') }}" style="color:var(--text-muted); text-decoration:none;">Surat Masuk</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Tambah</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Catat Surat Masuk
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Registrasi surat masuk baru dari instansi luar atau pengirim internal kampus.
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Formulir Dokumen Masuk</h2>
            <span class="badge badge-primary">Arsip Baru</span>
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

        <form action="{{ route('surat-masuk.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                    {{-- Nomor Surat --}}
                    <div class="form-group" style="margin:0;">
                        <label for="nomor_surat" class="form-label">
                            Nomor Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="text" id="nomor_surat" name="nomor_surat" value="{{ old('nomor_surat') }}" required placeholder="Contoh: 120/PMI/EXT/2026" class="form-control">
                        @error('nomor_surat') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Klasifikasi --}}
                    <div class="form-group" style="margin:0;">
                        <label for="klasifikasi_id" class="form-label">
                            Klasifikasi Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="klasifikasi_id" name="klasifikasi_id" required class="form-control">
                            <option value="">— Pilih Klasifikasi —</option>
                            @foreach(\App\Models\KlasifikasiSurat::all() as $klas)
                                <option value="{{ $klas->id }}" {{ old('klasifikasi_id') == $klas->id ? 'selected' : '' }}>
                                    [{{ $klas->kode_klasifikasi }}] {{ $klas->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('klasifikasi_id') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                    {{-- Tanggal Surat --}}
                    <div class="form-group" style="margin:0;">
                        <label for="tanggal_surat" class="form-label">
                            Tanggal Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="date" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', date('Y-m-d')) }}" required class="form-control">
                        @error('tanggal_surat') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Tanggal Diterima --}}
                    <div class="form-group" style="margin:0;">
                        <label for="tanggal_diterima" class="form-label">
                            Tanggal Diterima <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="date" id="tanggal_diterima" name="tanggal_diterima" value="{{ old('tanggal_diterima', date('Y-m-d')) }}" required class="form-control">
                        @error('tanggal_diterima') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Pengirim --}}
                <div class="form-group" style="margin:0;">
                    <label for="pengirim" class="form-label">
                        Nama Pengirim / Instansi Asal <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="pengirim" name="pengirim" value="{{ old('pengirim') }}" required placeholder="Contoh: LLDIKTI Wilayah IV / Kementerian Pendidikan" class="form-control">
                    @error('pengirim') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Perihal --}}
                <div class="form-group" style="margin:0;">
                    <label for="perihal" class="form-label">
                        Perihal Surat <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="perihal" name="perihal" value="{{ old('perihal') }}" required placeholder="Contoh: Undangan Rapat Koordinasi Akademik" class="form-control">
                    @error('perihal') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Ringkasan --}}
                <div class="form-group" style="margin:0;">
                    <label for="ringkasan" class="form-label">
                        Ringkasan Isi / Catatan <span style="font-weight:normal; color:var(--text-muted);">(opsional)</span>
                    </label>
                    <textarea id="ringkasan" name="ringkasan" rows="4" placeholder="Tuliskan poin-poin penting isi surat masuk..." class="form-control" style="font-family:inherit;">{{ old('ringkasan') }}</textarea>
                    @error('ringkasan') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- File PDF --}}
                <div class="form-group" style="margin:0;">
                    <label for="file_surat" class="form-label">
                        File Scan Surat (PDF) <span style="font-weight:normal; color:var(--text-muted);">(opsional)</span>
                    </label>
                    <input type="file" id="file_surat" name="file_surat" accept="application/pdf" class="form-control">
                    <span class="form-hint">Format file PDF, maksimal 10MB</span>
                    @error('file_surat') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <a href="{{ route('surat-masuk.index') }}" class="btn btn-ghost btn-sm">
                    &larr; Batal &amp; Kembali
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Simpan Surat Masuk</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
