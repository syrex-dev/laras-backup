@extends('layouts.app')

@section('title', 'Detail Templat Surat')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <a href="{{ route('template-surat.index') }}" style="color:var(--text-muted); text-decoration:none;">Templat Surat</a>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Detail</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                {{ $templateSurat->nama_templat }}
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Informasi metadata berkas dan pratinjau struktur dokumen HTML.
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('template-surat.index') }}" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Kembali</span>
            </a>
            <a href="{{ route('template-surat.edit', $templateSurat) }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span>Edit Templat</span>
            </a>
        </div>
    </div>

    {{-- ── 2. Two-Column Grid: Metadata & Preview ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; align-items:start;">
        {{-- Metadata Card --}}
        <div class="card" style="grid-column: span 1;">
            <div class="card-header">
                <h2 class="card-title">Informasi Templat</h2>
                <span class="badge badge-primary">HTML Dokumen</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:600; color:var(--text-muted); display:block; margin-bottom:2px;">Nama Templat</span>
                    <strong style="font-size:0.95rem; color:var(--text-title);">{{ $templateSurat->nama_templat }}</strong>
                </div>

                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">Path Berkas Sistem</span>
                    <code class="font-mono badge badge-info" style="font-size:0.78rem; word-break:break-all;">
                        {{ $templateSurat->path_file }}
                    </code>
                </div>

                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:600; color:var(--text-muted); display:block; margin-bottom:2px;">Tanggal Dibuat</span>
                    <span style="font-size:0.85rem; color:var(--text-title);">{{ $templateSurat->created_at->format('d M Y, H:i:s') }}</span>
                </div>

                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:600; color:var(--text-muted); display:block; margin-bottom:2px;">Terakhir Diperbarui</span>
                    <span style="font-size:0.85rem; color:var(--text-title);">{{ $templateSurat->updated_at->format('d M Y, H:i:s') }}</span>
                </div>

                @if($templateSurat->path_file)
                <div style="padding-top:10px; border-top:1px solid var(--border-color);">
                    <a href="{{ asset('storage/' . $templateSurat->path_file) }}" target="_blank" class="btn btn-outline btn-sm" style="width:100%; justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Buka Berkas di Tab Baru</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Preview Code Card --}}
        <div class="card" style="grid-column: span 2; min-width:0;">
            <div class="card-header">
                <h2 class="card-title">Struktur Kode HTML</h2>
                <span class="badge badge-muted">Source Code</span>
            </div>
            <div class="card-body" style="padding:0;">
                @if(!empty($htmlContent))
                    <pre style="margin:0; padding:20px; font-size:0.8rem; font-family:var(--font-mono); line-height:1.6; max-height:550px; overflow:auto; background:var(--bg-surface-2); color:var(--text-title); border-bottom-left-radius:var(--radius-lg); border-bottom-right-radius:var(--radius-lg); white-space:pre-wrap; word-break:break-all;">{{ $htmlContent }}</pre>
                @else
                    <div style="padding:48px 20px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px; opacity:0.4;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p style="margin:0;">Konten file kosong atau berkas fisik tidak ditemukan di storage.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
