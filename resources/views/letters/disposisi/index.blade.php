@extends('layouts.app')

@section('title', 'Disposisi')

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
                <span style="color:var(--color-primary); font-weight:600;">Disposisi</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Disposisi Surat
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Lacak instruksi pimpinan, delegasi tugas vertikal, dan tindak lanjut penanganan surat masuk.
            </p>
        </div>

        @role('Pimpinan')
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('disposisi.create') }}" class="btn btn-primary btn-sm" id="btn-create-disposisi">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Kirim Disposisi Baru</span>
            </a>
        </div>
        @endrole
    </div>

    {{-- ── 2. Data Table ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Daftar Instruksi Disposisi</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Surat Masuk</th>
                        <th style="min-width:150px;">Penerima</th>
                        <th style="min-width:110px;">Prioritas</th>
                        <th style="min-width:120px;">Status</th>
                        <th style="min-width:110px;">Tanggal</th>
                        <th style="min-width:80px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disposisi as $item)
                    <tr>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:2px;">
                                <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.75rem; width:fit-content;">
                                    {{ $item->suratMasuk->nomor_surat }}
                                </span>
                                <span style="font-size:0.78rem; color:var(--text-muted); max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $item->suratMasuk->perihal }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <strong style="color:var(--text-title); font-size:0.85rem; font-weight:600;">
                                {{ $item->penerima->nama }}
                            </strong>
                        </td>
                        <td>
                            @php
                                $prioBadge = match($item->prioritas) {
                                    'segera'  => 'badge-danger',
                                    'rahasia' => 'badge-warning',
                                    default   => 'badge-muted',
                                };
                            @endphp
                            <span class="badge {{ $prioBadge }}">
                                {{ ucfirst($item->prioritas) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusBadge = match($item->status) {
                                    'selesai'      => 'badge-success',
                                    'sudah_dibaca' => 'badge-info',
                                    default        => 'badge-warning',
                                };
                                $statusText = match($item->status) {
                                    'selesai'      => 'Selesai',
                                    'sudah_dibaca' => 'Dibaca',
                                    default        => 'Belum Dibaca',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td style="font-size:0.8rem; color:var(--text-muted);">
                            {{ $item->created_at?->format('d M Y') ?? '-' }}
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('disposisi.show', $item) }}" class="btn-icon" title="Lihat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada disposisi terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($disposisi, 'hasPages') && $disposisi->hasPages())
        <div class="table-footer">
            <div class="table-count">
                Menampilkan <strong>{{ $disposisi->firstItem() ?? 0 }}</strong> - <strong>{{ $disposisi->lastItem() ?? 0 }}</strong> dari <strong>{{ $disposisi->total() }}</strong> disposisi
            </div>
            <div>
                {{ $disposisi->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
