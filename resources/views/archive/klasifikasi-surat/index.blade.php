@extends('layouts.app')

@section('title', 'Klasifikasi Surat')

@section('content')
<div class="space-y-6">

    {{-- Header & Breadcrumb --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Kearsipan</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Klasifikasi Surat</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Klasifikasi Surat
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Kelola master kode dan kategori pengarsipan dokumen surat organisasi.
            </p>
        </div>

        <div>
            <a href="{{ route('klasifikasi-surat.create') }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Klasifikasi</span>
            </a>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Daftar Kategori &amp; Kode Klasifikasi</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">#</th>
                        <th style="min-width:140px;">Kode Klasifikasi</th>
                        <th style="min-width:220px;">Nama Kategori</th>
                        <th style="min-width:280px;">Keterangan</th>
                        <th style="min-width:100px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($klasifikasi as $kl)
                    <tr>
                        <td style="text-align:center; color:var(--text-muted); font-size:0.8rem;">
                            {{ $loop->iteration }}
                        </td>
                        <td>
                            <span class="badge badge-primary font-mono" style="font-size:0.75rem;">
                                {{ $kl->kode_klasifikasi }}
                            </span>
                        </td>
                        <td>
                            <strong style="color:var(--text-title); font-size:0.88rem; font-weight:600;">
                                {{ $kl->nama }}
                            </strong>
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-muted);">
                            {{ $kl->keterangan ?: '—' }}
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <a href="{{ route('klasifikasi-surat.edit', $kl) }}" class="btn-icon" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('klasifikasi-surat.destroy', $kl) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus klasifikasi \'{{ $kl->nama }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus" style="color:var(--color-danger);">
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
                        <td colspan="5" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada klasifikasi surat. <a href="{{ route('klasifikasi-surat.create') }}" style="color:var(--color-primary);">Tambah sekarang</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($klasifikasi, 'hasPages') && $klasifikasi->hasPages())
        <div class="table-footer">
            <div class="table-count">
                Menampilkan <strong>{{ $klasifikasi->firstItem() ?? 0 }}</strong> - <strong>{{ $klasifikasi->lastItem() ?? 0 }}</strong> dari <strong>{{ $klasifikasi->total() }}</strong> klasifikasi
            </div>
            <div>
                {{ $klasifikasi->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
