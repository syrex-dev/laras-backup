@extends('layouts.app')

@section('title', 'Buat Disposisi')

@section('content')
<div style="max-width:760px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('disposisi.index') }}" style="color:var(--text-muted); text-decoration:none;">Disposisi</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Buat</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Buat Disposisi Baru
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Teruskan surat masuk kepada unit/anggota terkait disertai arahan instruksi kerja.
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Instruksi Tindak Lanjut</h2>
            <span class="badge badge-primary">Form Disposisi</span>
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

        <form action="{{ route('disposisi.store') }}" method="POST">
            @csrf

            <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                {{-- Surat Masuk --}}
                <div class="form-group" style="margin:0;">
                    <label for="surat_masuk_id" class="form-label">
                        Surat Masuk Terkait <span style="color:var(--color-danger);">*</span>
                    </label>
                    <select id="surat_masuk_id" name="surat_masuk_id" required class="form-control">
                        <option value="">— Pilih Surat Masuk —</option>
                        @foreach($suratMasuk as $sm)
                            <option value="{{ $sm->id }}" {{ old('surat_masuk_id', request('surat_masuk_id')) == $sm->id ? 'selected' : '' }}>
                                [{{ $sm->nomor_surat }}] {{ Str::limit($sm->perihal, 70) }}
                            </option>
                        @endforeach
                    </select>
                    @if($suratMasuk->isEmpty())
                        <p style="font-size:0.75rem; color:var(--color-warning-dark); margin-top:4px;">
                            Belum ada surat masuk. <a href="{{ route('surat-masuk.create') }}" style="color:var(--color-primary);">Tambah sekarang &rarr;</a>
                        </p>
                    @endif
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                    {{-- Diteruskan Kepada --}}
                    <div class="form-group" style="margin:0;">
                        <label for="penerima_id" class="form-label">
                            Diteruskan Kepada (Penerima) <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="penerima_id" name="penerima_id" required class="form-control">
                            <option value="">— Pilih Anggota / Staf —</option>
                            @foreach($users as $u)
                                @if($u->id !== auth()->id())
                                    <option value="{{ $u->id }}" {{ old('penerima_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nama }} ({{ $u->roles->first()?->name ?? 'Pengguna' }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- Prioritas --}}
                    <div class="form-group" style="margin:0;">
                        <label for="prioritas" class="form-label">
                            Tingkat Prioritas <span style="color:var(--color-danger);">*</span>
                        </label>
                        <select id="prioritas" name="prioritas" required class="form-control">
                            <option value="biasa" {{ old('prioritas') == 'biasa' ? 'selected' : '' }}>Biasa / Standar</option>
                            <option value="segera" {{ old('prioritas') == 'segera' ? 'selected' : '' }}>Segera (Urgent)</option>
                            <option value="rahasia" {{ old('prioritas') == 'rahasia' ? 'selected' : '' }}>Rahasia / Penting</option>
                        </select>
                    </div>
                </div>

                {{-- Instruksi --}}
                <div class="form-group" style="margin:0;">
                    <label for="instruksi" class="form-label">
                        Instruksi / Catatan Arahan <span style="color:var(--color-danger);">*</span>
                    </label>
                    <textarea id="instruksi" name="instruksi" rows="5" required
                        placeholder="Tuliskan instruksi atau catatan yang perlu ditindaklanjuti oleh penerima disposisi..."
                        class="form-control" style="font-family:inherit;">{{ old('instruksi') }}</textarea>
                </div>

                {{-- Info Pengirim Otomatis --}}
                <div style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:var(--bg-surface-2); border-radius:var(--radius-base); border:1px solid var(--border-color); font-size:0.78rem; color:var(--text-muted);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Disposisi akan otomatis tercatat dikirim oleh: <strong style="color:var(--text-title);">{{ auth()->user()->nama }}</strong></span>
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <a href="{{ route('disposisi.index') }}" class="btn btn-ghost btn-sm">
                    &larr; Batal &amp; Kembali
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <span>Kirim Disposisi</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
