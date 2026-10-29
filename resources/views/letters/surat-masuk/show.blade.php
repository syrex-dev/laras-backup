@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content')
<div class="space-y-6">

    {{-- Header & Breadcrumbs --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <a href="{{ route('surat-masuk.index') }}" style="color:var(--text-muted); text-decoration:none;">Surat Masuk</a>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Detail</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Detail Surat Masuk
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Nomor: <span style="font-family:ui-monospace, monospace; color:var(--color-primary); font-weight:600;">{{ $suratMasuk->nomor_surat }}</span>
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <a href="{{ route('surat-masuk.index') }}" class="btn btn-outline btn-sm">
                &larr; Kembali ke Daftar
            </a>
            @role('Admin|Sekretaris')
            <a href="{{ route('surat-masuk.edit', $suratMasuk) }}" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span>Edit Surat</span>
            </a>
            @endrole
            @role('Admin|Pimpinan')
            <a href="{{ route('disposisi.create', ['surat_masuk_id' => $suratMasuk->id]) }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                <span>Buat Disposisi</span>
            </a>
            @endrole
        </div>
    </div>

    {{-- Content Grid --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; align-items:start;">
        
        {{-- Left Info Column --}}
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dokumen Masuk</h3>
                    @if($suratMasuk->disposisi && $suratMasuk->disposisi->count() > 0)
                        <span class="badge badge-success">Didisposisi ({{ $suratMasuk->disposisi->count() }})</span>
                    @else
                        <span class="badge badge-warning">Belum Didisposisi</span>
                    @endif
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Nomor Surat</div>
                        <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.8rem;">
                            {{ $suratMasuk->nomor_surat }}
                        </span>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Klasifikasi</div>
                        <div style="font-size:0.88rem; color:var(--text-title); font-weight:500;">
                            {{ $suratMasuk->klasifikasi->nama ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Pengirim / Instansi Asal</div>
                        <div style="font-size:0.88rem; font-weight:600; color:var(--text-title);">
                            {{ $suratMasuk->pengirim }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Tanggal Surat &amp; Diterima</div>
                        <div style="font-size:0.85rem; color:var(--text-title);">
                            {{ $suratMasuk->tanggal_surat?->format('d F Y') ?? '-' }}
                            @if($suratMasuk->tanggal_diterima)
                                <span style="color:var(--text-muted);"> • Diterima: {{ $suratMasuk->tanggal_diterima->format('d F Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Perihal</div>
                        <div style="font-size:0.88rem; color:var(--text-title); font-weight:500;">
                            {{ $suratMasuk->perihal }}
                        </div>
                    </div>

                    @if(!empty($suratMasuk->ringkasan))
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Ringkasan / Catatan</div>
                        <div style="font-size:0.85rem; color:var(--text-primary); line-height:1.5;">
                            {{ $suratMasuk->ringkasan }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Disposisi History Card --}}
            @if($suratMasuk->disposisi && $suratMasuk->disposisi->count() > 0)
            <div class="card" id="disposisi">
                <div class="card-header">
                    <h3 class="card-title">Instruksi Disposisi Terkait</h3>
                    <span class="badge badge-info">{{ $suratMasuk->disposisi->count() }} Instruksi</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <div style="display:flex; flex-direction:column;">
                        @foreach($suratMasuk->disposisi as $disp)
                        <div style="padding:14px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; gap:12px;">
                            <div>
                                <div style="font-size:0.85rem; font-weight:600; color:var(--text-title);">
                                    Kepada: {{ $disp->penerima->nama ?? 'Staf' }}
                                </div>
                                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                                    "{{ Str::limit($disp->instruksi, 50) }}"
                                </div>
                            </div>
                            <a href="{{ route('disposisi.show', $disp) }}" class="btn-icon" title="Lihat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right PDF Preview Column --}}
        <div style="grid-column: span 1; @media(min-width:1024px){ grid-column: span 2; }">
            <div class="card" style="height:820px; display:flex; flex-direction:column;">
                <div class="card-header">
                    <h3 class="card-title">Pratinjau Dokumen PDF</h3>
                    @if($suratMasuk->file_path)
                    <a href="{{ asset('storage/' . $suratMasuk->file_path) }}" target="_blank" class="btn btn-outline btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Buka Tab Baru</span>
                    </a>
                    @endif
                </div>
                <div style="flex:1; background:var(--bg-surface-2);">
                    @if($suratMasuk->file_path)
                        <iframe src="{{ asset('storage/' . $suratMasuk->file_path) }}" style="width:100%; height:100%; border:none;"></iframe>
                    @else
                        <div style="width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:var(--text-muted);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <span style="font-size:0.85rem;">File dokumen PDF belum diunggah untuk surat ini</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
