@extends('layouts.app')

@section('title', 'Audit Logs')

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
                <span style="color:var(--color-primary); font-weight:600;">Audit Logs</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Audit Logs Aktivitas
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Rekam jejak riwayat audit trail interaksi dan perubahan data seluruh pengguna sistem.
            </p>
        </div>
    </div>

    {{-- ── 2. Table Wrapper ── --}}
    @php
        $logs = \App\Models\LogAktivitas::with('user')->latest()->paginate(20);
    @endphp

    <div class="table-wrapper">
        <div class="table-toolbar">
            <h3 class="table-title">Riwayat Log Sistem</h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:160px;">Waktu &amp; Tanggal</th>
                        <th style="min-width:200px;">Pengguna</th>
                        <th style="min-width:110px;">Tindakan</th>
                        <th style="min-width:180px;">Modul / ID Entitas</th>
                        <th style="min-width:130px;">Alamat IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="font-size:0.82rem; color:var(--text-title);">
                            <span class="font-mono">{{ $log->created_at->format('d M Y, H:i:s') }}</span>
                        </td>
                        <td>
                            <strong style="color:var(--text-title); font-size:0.86rem; font-weight:600; display:block;">
                                {{ $log->user->nama ?? 'Sistem / Tamu' }}
                            </strong>
                            <span style="font-size:0.75rem; color:var(--text-muted);">{{ $log->user->email ?? '—' }}</span>
                        </td>
                        <td>
                            @php
                                $actionBadge = match(strtolower($log->aksi)) {
                                    'created', 'create', 'tambah' => 'badge-success',
                                    'updated', 'update', 'ubah', 'edit' => 'badge-warning',
                                    'deleted', 'delete', 'hapus' => 'badge-danger',
                                    'login', 'auth' => 'badge-info',
                                    default => 'badge-primary',
                                };
                            @endphp
                            <span class="badge {{ $actionBadge }}">
                                {{ strtoupper($log->aksi) }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:0.85rem; color:var(--text-title); font-weight:500;">
                                {{ $log->nama_tabel ?? '—' }}
                            </span>
                            @if($log->id_rekaman)
                            <span class="badge badge-muted font-mono" style="font-size:0.7rem; margin-left:6px;">
                                ID: {{ $log->id_rekaman }}
                            </span>
                            @endif
                        </td>
                        <td class="font-mono" style="font-size:0.8rem; color:var(--text-muted);">
                            {{ $log->alamat_ip ?: '127.0.0.1' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada riwayat aktivitas yang tercatat dalam log audit.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-color);">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
