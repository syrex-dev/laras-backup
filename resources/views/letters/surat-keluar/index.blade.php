@extends('layouts.app')

@section('title', 'Surat Keluar')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Persuratan</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Surat Keluar</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Surat Keluar
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Drafting dokumen resmi, alur persetujuan pimpinan, validasi TTE &amp; penerbitan QR token.
            </p>
        </div>

        @role('Sekretaris')
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('surat-keluar.create') }}" class="btn btn-primary btn-sm" id="btn-create-surat-keluar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Buat Surat Keluar</span>
            </a>
        </div>
        @endrole
    </div>

    {{-- ── 2. Mantis Metric Cards ── --}}
    @php
        $totalKeluar = \App\Models\SuratKeluar::count();
        $approvedKeluar = \App\Models\SuratKeluar::where('status_approval', 'approved')->count();
        $draftKeluar = \App\Models\SuratKeluar::where('status_approval', 'draft')->count();
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Total Surat Keluar</span>
                <div class="stat-card-icon primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $totalKeluar }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>+70.5%</span>
                </span>
                <span class="trend-desc">Dokumen keluar</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Disetujui &amp; Tervalidasi</span>
                <div class="stat-card-icon success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" style="color:var(--color-success-dark);">{{ $approvedKeluar }}</div>
            <div class="stat-card-trend">
                <span class="badge badge-success" style="font-size:0.7rem; padding:1px 6px;">
                    Aktif dengan QR
                </span>
                <span class="trend-desc">Siap diterbitkan</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Menunggu Persetujuan</span>
                <div class="stat-card-icon warning">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" style="color:var(--color-warning-dark);">{{ $draftKeluar }}</div>
            <div class="stat-card-trend">
                <span class="badge badge-warning" style="font-size:0.7rem; padding:1px 6px;">
                    Draft
                </span>
                <span class="trend-desc">Perlu review pimpinan</span>
            </div>
        </div>
    </div>

    {{-- ── 3. Mantis Data Table ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Daftar Dokumen Surat Keluar</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Nomor Surat</th>
                        <th style="min-width:180px;">Tujuan &amp; Perihal</th>
                        <th style="min-width:150px;">Penyetuju</th>
                        <th style="min-width:120px;">Status</th>
                        <th style="min-width:110px;">Tanggal</th>
                        <th style="min-width:100px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratKeluar as $surat)
                    <tr>
                        <td>
                            <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.75rem;">
                                {{ $surat->nomor_surat }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:2px;">
                                <strong style="color:var(--text-title); font-size:0.85rem; font-weight:600;">
                                    {{ $surat->tujuan }}
                                </strong>
                                @if(!empty($surat->perihal))
                                    <span style="font-size:0.75rem; color:var(--text-muted); max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $surat->perihal }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span style="font-size:0.85rem; color:var(--text-title); font-weight:500;">
                                {{ $surat->penyetuju->nama ?? 'Belum Ditentukan' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $badgeVariant = match($surat->status_approval) {
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    default    => 'badge-warning',
                                };
                                $statusLabel = match($surat->status_approval) {
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    default    => 'Draft',
                                };
                            @endphp
                            <span class="badge {{ $badgeVariant }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td style="font-size:0.8rem; color:var(--text-muted);">
                            {{ $surat->tanggal_surat?->format('d M Y') ?? '-' }}
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <a href="{{ route('surat-keluar.show', $surat) }}" class="btn-icon" title="Lihat">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                @if(auth()->user() && auth()->user()->hasRole('Sekretaris') && $surat->status_approval === 'draft')
                                <a href="{{ route('surat-keluar.edit', $surat) }}" class="btn-icon" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada surat keluar terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($suratKeluar, 'hasPages') && $suratKeluar->hasPages())
        <div class="table-footer">
            <div class="table-count">
                Menampilkan <strong>{{ $suratKeluar->firstItem() ?? 0 }}</strong> - <strong>{{ $suratKeluar->lastItem() ?? 0 }}</strong> dari <strong>{{ $suratKeluar->total() }}</strong> surat keluar
            </div>
            <div>
                {{ $suratKeluar->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
