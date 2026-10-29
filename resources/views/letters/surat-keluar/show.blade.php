@extends('layouts.app')

@section('title', 'Detail Surat Keluar')

@section('content')
<div class="space-y-6">

    {{-- Header & Breadcrumb --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <a href="{{ route('surat-keluar.index') }}" style="color:var(--text-muted); text-decoration:none;">Surat Keluar</a>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Detail</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Detail Surat Keluar
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Nomor Surat: <span style="font-family:ui-monospace, monospace; color:var(--color-primary); font-weight:600;">{{ $suratKeluar->nomor_surat }}</span>
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <a href="{{ route('surat-keluar.index') }}" class="btn btn-outline btn-sm">
                &larr; Kembali ke Daftar
            </a>
            @if(auth()->user() && auth()->user()->hasRole('Sekretaris') && $suratKeluar->status_approval === 'draft')
            <a href="{{ route('surat-keluar.edit', $suratKeluar) }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span>Edit Surat</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Content Grid --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; align-items:start;">
        
        {{-- Left Info Column --}}
        <div style="display:flex; flex-direction:column; gap:20px;">
            {{-- Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dokumen</h3>
                    @php
                        $badgeVariant = match($suratKeluar->status_approval) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            default    => 'badge-warning',
                        };
                        $statusLabel = match($suratKeluar->status_approval) {
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default    => 'Draft Menunggu',
                        };
                    @endphp
                    <span class="badge {{ $badgeVariant }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Nomor Surat</div>
                        <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.8rem;">
                            {{ $suratKeluar->nomor_surat }}
                        </span>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Penyetuju (Pimpinan)</div>
                        <div style="font-size:0.88rem; font-weight:600; color:var(--text-title);">
                            {{ $suratKeluar->penyetuju->nama ?? 'Belum Ditentukan' }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Tujuan / Penerima</div>
                        <div style="font-size:0.88rem; color:var(--text-title);">
                            {{ $suratKeluar->tujuan }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Perihal</div>
                        <div style="font-size:0.88rem; color:var(--text-title); font-weight:500;">
                            {{ $suratKeluar->perihal }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Tanggal Surat</div>
                        <div style="font-size:0.85rem; color:var(--text-primary);">
                            {{ $suratKeluar->tanggal_surat?->format('d F Y') ?? '-' }}
                        </div>
                    </div>

                    @if(!empty($suratKeluar->lampiran))
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Lampiran</div>
                        <div style="font-size:0.85rem; color:var(--text-primary);">
                            {{ $suratKeluar->lampiran }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Approval Actions for Pimpinan --}}
            @if(auth()->user()->hasRole('Pimpinan') && $suratKeluar->status_approval === 'draft')
            <div class="card" style="border-color:rgba(22, 119, 255, 0.35); background:var(--color-primary-soft);">
                <div class="card-header" style="background:transparent;">
                    <h3 class="card-title" style="color:var(--color-primary);">Tindakan Persetujuan</h3>
                </div>
                <div class="card-body">
                    <p style="font-size:0.82rem; color:var(--text-primary); margin:0 0 16px;">
                        Sebagai pimpinan, Anda dapat menyetujui dokumen ini untuk ditandatangani secara elektronik (TTE) atau menolaknya.
                    </p>
                    <div style="display:flex; gap:10px;">
                        <form action="{{ route('surat-keluar.approve', $suratKeluar) }}" method="POST" style="flex:1;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; background:var(--color-success); border-color:var(--color-success);" onclick="return confirm('Setujui dan terbitkan surat keluar ini?')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Setujui (Approve)</span>
                            </button>
                        </form>
                        <form action="{{ route('surat-keluar.reject', $suratKeluar) }}" method="POST" style="flex:1;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-danger" style="width:100%; justify-content:center;" onclick="return confirm('Tolak surat keluar ini?')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Tolak (Reject)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- QR Code Verification Card --}}
            @if($suratKeluar->path_qrcode)
            <div class="card" style="text-align:center;">
                <div class="card-header">
                    <h3 class="card-title" style="margin:0 auto;">Verifikasi TTE &amp; QR Code</h3>
                </div>
                <div class="card-body">
                    <div style="background:#ffffff; padding:16px; border-radius:var(--radius-md); display:inline-block; border:1px solid var(--border-color); margin-bottom:12px;">
                        <img src="{{ asset('storage/' . $suratKeluar->path_qrcode) }}" width="140" alt="QR Code" style="display:block;">
                    </div>
                    <p style="font-size:0.75rem; color:var(--text-muted); margin:0;">
                        Pindai kode QR untuk memverifikasi keabsahan dokumen resmi ini secara publik.
                    </p>
                </div>
            </div>
            @endif
        </div>

        {{-- Right PDF Preview Column --}}
        <div style="grid-column: span 1; @media(min-width:1024px){ grid-column: span 2; }">
            <div class="card" style="height:820px; display:flex; flex-direction:column;">
                <div class="card-header">
                    <h3 class="card-title">Pratinjau Dokumen PDF</h3>
                    @php
                        $pdfUrl = $suratKeluar->file_pdf 
                            ? asset('storage/' . $suratKeluar->file_pdf) 
                            : route('surat-keluar.draft-pdf', $suratKeluar);
                    @endphp
                    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Buka Tab Baru</span>
                    </a>
                </div>
                <div style="flex:1; background:var(--bg-surface-2);">
                    <iframe src="{{ $pdfUrl }}" style="width:100%; height:100%; border:none;"></iframe>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
