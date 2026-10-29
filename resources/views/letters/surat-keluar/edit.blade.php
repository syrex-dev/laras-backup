@extends('layouts.app')

@section('title', 'Edit Surat Keluar')

@section('content')
<div style="max-width:800px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('surat-keluar.index') }}" style="color:var(--text-muted); text-decoration:none;">Surat Keluar</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Edit</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Edit Surat Keluar
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Ubah rincian surat keluar <span style="font-family:ui-monospace, monospace; color:var(--color-primary); font-weight:600;">{{ $suratKeluar->nomor_surat ?? 'Draft' }}</span>.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Perbarui Informasi Dokumen</h2>
            <span class="badge badge-warning">Mode Edit</span>
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

        <form action="{{ route('surat-keluar.update', $suratKeluar) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                    {{-- Klasifikasi --}}
                    <div class="form-group" style="margin:0;">
                        <label for="klasifikasi_id" class="form-label">
                            Klasifikasi Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="klasifikasi_id" name="klasifikasi_id" required class="form-control">
                            <option value="">— Pilih Klasifikasi —</option>
                            @foreach($klasifikasi as $kl)
                                <option value="{{ $kl->id }}" {{ (old('klasifikasi_id', $suratKeluar->klasifikasi_id) == $kl->id) ? 'selected' : '' }}>
                                    [{{ $kl->kode_klasifikasi }}] {{ $kl->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Surat --}}
                    <div class="form-group" style="margin:0;">
                        <label for="tanggal_surat" class="form-label">
                            Tanggal Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <input type="date" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', $suratKeluar->tanggal_surat ? $suratKeluar->tanggal_surat->format('Y-m-d') : date('Y-m-d')) }}" required class="form-control">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                    {{-- Template Surat --}}
                    <div class="form-group" style="margin:0;">
                        <label for="templat_surat_id" class="form-label">
                            Template Surat <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="templat_surat_id" name="templat_surat_id" required class="form-control">
                            <option value="">— Pilih Template Surat —</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ (old('templat_surat_id', $suratKeluar->templat_surat_id) == $template->id) ? 'selected' : '' }}>
                                    {{ $template->nama_templat ?? ('Template Surat ' . $template->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Penyetuju --}}
                    <div class="form-group" style="margin:0;">
                        <label for="penyetuju_id" class="form-label">
                            Penyetuju (Pimpinan) <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="penyetuju_id" name="penyetuju_id" required class="form-control">
                            <option value="">— Pilih Pimpinan —</option>
                            @foreach($pimpinan as $p)
                                <option value="{{ $p->id }}" {{ (old('penyetuju_id', $suratKeluar->penyetuju_id) == $p->id) ? 'selected' : '' }}>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Ditujukan Kepada --}}
                <div class="form-group" style="margin:0;">
                    <label for="tujuan" class="form-label">
                        Ditujukan Kepada <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="tujuan" name="tujuan" value="{{ old('tujuan', $suratKeluar->tujuan) }}" required class="form-control">
                </div>

                {{-- Perihal --}}
                <div class="form-group" style="margin:0;">
                    <label for="perihal" class="form-label">
                        Perihal <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="perihal" name="perihal" value="{{ old('perihal', $suratKeluar->perihal) }}" required class="form-control">
                </div>

                {{-- Lampiran --}}
                <div class="form-group" style="margin:0;">
                    <label for="lampiran" class="form-label">
                        Lampiran <span style="font-weight:normal; color:var(--text-muted);">(opsional)</span>
                    </label>
                    <input type="text" id="lampiran" name="lampiran" value="{{ old('lampiran', $suratKeluar->lampiran) }}" placeholder="Contoh: 1 berkas, Terlampir, -" class="form-control">
                </div>

                {{-- Isi --}}
                <div class="form-group" style="margin:0;">
                    <label for="isi" class="form-label">
                        Isi / Keterangan Surat <span style="font-weight:normal; color:var(--text-muted);">(opsional)</span>
                    </label>
                    <textarea id="isi" name="isi" rows="6" class="form-control" style="font-family:inherit;">{{ old('isi', $suratKeluar->isi) }}</textarea>
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <a href="{{ route('surat-keluar.show', $suratKeluar) }}" class="btn btn-ghost btn-sm">
                    &larr; Kembali ke Detail
                </a>
                <div style="display:flex; align-items:center; gap:8px;">
                    <a href="{{ route('surat-keluar.index') }}" class="btn btn-outline btn-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
