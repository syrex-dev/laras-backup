@extends('layouts.app')

@section('title', 'Recycle Bin')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Operasional</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Recycle Bin</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Recycle Bin (Tempat Sampah)
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Kelola dokumen yang telah dihapus sementara (soft-deleted). Pulihkan arsip atau bersihkan secara permanen.
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:20px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- ── 2. Data Table ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Daftar Dokumen Terhapus</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Jenis Dokumen</th>
                        <th style="min-width:240px;">Nomor Dokumen / Informasi</th>
                        <th style="min-width:160px;">Waktu Dihapus</th>
                        <th style="min-width:120px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trashedItems as $item)
                    <tr>
                        <td>
                            @if($item->type === 'surat-masuk')
                                <span class="badge badge-primary">Surat Masuk</span>
                            @else
                                <span class="badge badge-info">Surat Keluar</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color:var(--text-title); font-size:0.88rem; font-weight:600;">
                                {{ $item->nomor_surat ?? ($item->instruksi ?? 'Dokumen tanpa nomor') }}
                            </strong>
                            @if(isset($item->perihal))
                            <span style="display:block; font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                                {{ Str::limit($item->perihal, 75) }}
                            </span>
                            @endif
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-muted);">
                            <span class="font-mono">{{ $item->deleted_at ? $item->deleted_at->format('d M Y, H:i') : '—' }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                {{-- Restore --}}
                                <form action="{{ route('admin.recycle-bin.restore', ['type' => $item->type, 'id' => $item->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Pulihkan dokumen ini kembali ke arsip aktif?');">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Pulihkan Dokumen" style="color:var(--color-success-dark);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="1 4 1 10 7 10"></polyline>
                                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                        </svg>
                                    </button>
                                </form>

                                {{-- Force Delete --}}
                                <form action="{{ route('admin.recycle-bin.force-delete', ['type' => $item->type, 'id' => $item->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('PERINGATAN: Dokumen ini akan dihapus secara permanen dan tidak dapat dikembalikan. Lanjutkan?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus Permanen" style="color:var(--color-danger);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding:40px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px; opacity:0.4;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <p style="margin:0;">Recycle bin bersih. Tidak ada berkas yang berada di tempat sampah.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
