@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header & Actions ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Organisasi</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Pengguna</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Manajemen Pengguna
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Kelola akun pengguna, penetapan peran (role), dan hak akses internal sistem persuratan.
            </p>
        </div>

        <div>
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Pengguna</span>
            </a>
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

    {{-- ── 2. Table Wrapper with Search Toolbar ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar" style="flex-wrap:wrap; gap:12px;">
            <h3 class="table-title">Daftar Akun Pengguna</h3>

            <form action="{{ route('users.index') }}" method="GET" style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative; width:220px;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                        class="form-control" style="padding:6px 10px; font-size:0.82rem;">
                </div>
                <button type="submit" class="btn btn-outline btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Cari</span>
                </button>
                @if(request('search'))
                <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm" title="Reset filter">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:240px;">Pengguna</th>
                        <th style="min-width:220px;">Email</th>
                        <th style="min-width:140px;">Peran (Role)</th>
                        <th style="min-width:100px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px; height:34px; border-radius:var(--radius-base); background:var(--color-primary-soft); color:var(--color-primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">
                                    {{ strtoupper(substr($user->nama, 0, 1)) }}
                                </div>
                                <div>
                                    <strong style="color:var(--text-title); font-size:0.88rem; font-weight:600; display:block;">
                                        {{ $user->nama }}
                                    </strong>
                                    @if(auth()->id() === $user->id)
                                    <span style="font-size:0.7rem; color:var(--color-primary); font-weight:600;">(Akun Anda)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="font-size:0.84rem; color:var(--text-primary);">
                            {{ $user->email }}
                        </td>
                        <td>
                            @php
                                $roleName = $user->userRole->name ?? ($user->roles->first()?->name ?? '-');
                                $badgeClass = match(strtolower($roleName)) {
                                    'admin' => 'badge-primary',
                                    'pimpinan' => 'badge-warning',
                                    'sekretaris' => 'badge-info',
                                    default => 'badge-muted',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ strtoupper($roleName) }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <a href="{{ route('users.edit', $user->id) }}" class="btn-icon" title="Edit Pengguna">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>

                                @if(auth()->id() !== $user->id)
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun \'{{ $user->nama }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus Pengguna" style="color:var(--color-danger);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding:36px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Tidak ditemukan data pengguna. <a href="{{ route('users.create') }}" style="color:var(--color-primary);">Tambah pengguna baru</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-color);">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
