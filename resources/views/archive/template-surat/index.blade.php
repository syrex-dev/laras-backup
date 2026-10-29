@extends('layouts.app')

@section('title', 'Templat Surat')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header & Actions ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Kearsipan</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Templat Surat</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Templat Surat
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Kelola master tata letak HTML dan format baku untuk otomasi pembuatan dokumen resmi.
            </p>
        </div>

        <div>
            <a href="{{ route('template-surat.create') }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Templat Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        {{ session('success') }}
    </div>
    @endif

    {{-- ── 2. Data Table ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Daftar Berkas &amp; Struktur Templat</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:220px;">Nama Templat</th>
                        <th style="min-width:240px;">Path Berkas</th>
                        <th style="min-width:140px;">Tanggal Dibuat</th>
                        <th style="min-width:110px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:32px; height:32px; border-radius:var(--radius-base); background:var(--color-primary-soft); color:var(--color-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <strong style="color:var(--text-title); font-size:0.88rem; font-weight:600;">
                                    {{ $tpl->nama_templat }}
                                </strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info font-mono" style="font-size:0.75rem;">
                                {{ $tpl->path_file }}
                            </span>
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-muted);">
                            {{ $tpl->created_at->format('d M Y, H:i') }}
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <a href="{{ route('template-surat.show', $tpl) }}" class="btn-icon" title="Pratinjau HTML">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <a href="{{ route('template-surat.edit', $tpl) }}" class="btn-icon" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('template-surat.destroy', $tpl) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus templat \'{{ $tpl->nama_templat }}\'?')">
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
                        <td colspan="4" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada templat surat. <a href="{{ route('template-surat.create') }}" style="color:var(--color-primary);">Tambah templat sekarang</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-color);">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
