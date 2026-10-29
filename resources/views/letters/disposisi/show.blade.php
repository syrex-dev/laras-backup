@extends('layouts.app')

@section('title', 'Detail Disposisi')

@section('content')
<div class="space-y-6">

    {{-- Header & Breadcrumb --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <a href="{{ route('disposisi.index') }}" style="color:var(--text-muted); text-decoration:none;">Disposisi</a>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Detail</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Detail Pelacakan Disposisi
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Surat Terkait: <span style="font-family:ui-monospace, monospace; color:var(--color-primary); font-weight:600;">{{ $disposisi->suratMasuk->nomor_surat }}</span>
            </p>
        </div>

        <div>
            <a href="{{ route('disposisi.index') }}" class="btn btn-outline btn-sm">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Content Grid --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; align-items:start;">
        
        {{-- Left Tracking Timeline --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alur Pelacakan Status</h3>
                @php
                    $statusBadge = match($disposisi->status) {
                        'selesai'      => 'badge-success',
                        'sudah_dibaca' => 'badge-info',
                        default        => 'badge-warning',
                    };
                    $statusText = match($disposisi->status) {
                        'selesai'      => 'Selesai Dilaksanakan',
                        'sudah_dibaca' => 'Sudah Dibaca',
                        default        => 'Belum Dibaca',
                    };
                @endphp
                <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
            </div>

            <div class="card-body" style="padding:24px 20px;">
                <div style="position:relative; display:flex; flex-direction:column; gap:24px;">
                    {{-- Vertical line --}}
                    <div style="position:absolute; left:16px; top:12px; bottom:12px; width:2px; background:var(--border-color); z-index:1;"></div>

                    {{-- Step 1: Disposisi Dikirim --}}
                    <div style="position:relative; display:flex; align-items:flex-start; gap:16px; z-index:2;">
                        <div style="width:34px; height:34px; border-radius:50%; background:var(--color-primary); color:white; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 0 0 4px var(--bg-surface);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.88rem; font-weight:700; color:var(--text-title);">Disposisi Diterbitkan</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Oleh: {{ $disposisi->pengirim->nama }} (Pimpinan)</div>
                            <div style="font-size:0.75rem; color:var(--color-primary); font-weight:600; margin-top:2px;">
                                {{ $disposisi->created_at->format('d M Y, H:i') }}
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Diterima & Dibaca --}}
                    @php $isRead = !empty($disposisi->dibaca_pada); @endphp
                    <div style="position:relative; display:flex; align-items:flex-start; gap:16px; z-index:2;">
                        <div style="width:34px; height:34px; border-radius:50%; background:{{ $isRead ? 'var(--color-info)' : 'var(--bg-surface-2)' }}; color:{{ $isRead ? 'white' : 'var(--text-muted)' }}; border:2px solid {{ $isRead ? 'var(--color-info)' : 'var(--border-color)' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 0 0 4px var(--bg-surface);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.88rem; font-weight:700; color:{{ $isRead ? 'var(--text-title)' : 'var(--text-muted)' }};">Diterima &amp; Dibaca</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Penerima: {{ $disposisi->penerima->nama }}</div>
                            <div style="font-size:0.75rem; color:{{ $isRead ? 'var(--color-info-dark)' : 'var(--text-muted)' }}; font-weight:600; margin-top:2px;">
                                {{ $isRead ? $disposisi->dibaca_pada->format('d M Y, H:i') : 'Menunggu dibuka...' }}
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Tindak Lanjut Selesai --}}
                    @php $isDone = !empty($disposisi->selesai_pada); @endphp
                    <div style="position:relative; display:flex; align-items:flex-start; gap:16px; z-index:2;">
                        <div style="width:34px; height:34px; border-radius:50%; background:{{ $isDone ? 'var(--color-success)' : 'var(--bg-surface-2)' }}; color:{{ $isDone ? 'white' : 'var(--text-muted)' }}; border:2px solid {{ $isDone ? 'var(--color-success)' : 'var(--border-color)' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 0 0 4px var(--bg-surface);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.88rem; font-weight:700; color:{{ $isDone ? 'var(--text-title)' : 'var(--text-muted)' }};">Tindak Lanjut Selesai</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Instruksi telah tuntas dilaksanakan</div>
                            <div style="font-size:0.75rem; color:{{ $isDone ? 'var(--color-success-dark)' : 'var(--text-muted)' }}; font-weight:600; margin-top:2px;">
                                {{ $isDone ? $disposisi->selesai_pada->format('d M Y, H:i') : 'Dalam pengerjaan...' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if(Auth::id() === $disposisi->penerima_id && $disposisi->status !== 'selesai')
                <div style="margin-top:28px; padding-top:20px; border-top:1px solid var(--border-color);">
                    <form action="{{ route('disposisi.update-status', $disposisi) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="selesai">
                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; background:var(--color-success); border-color:var(--color-success);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Tandai Disposisi Selesai</span>
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- Right Column: Instructions & Source --}}
        <div style="display:flex; flex-direction:column; gap:20px;">
            {{-- Instruksi Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Instruksi &amp; Arahan Pimpinan</h3>
                    @php
                        $prioBadge = match($disposisi->prioritas) {
                            'segera'  => 'badge-danger',
                            'rahasia' => 'badge-warning',
                            default   => 'badge-muted',
                        };
                    @endphp
                    <span class="badge {{ $prioBadge }}">
                        Prioritas: {{ ucfirst($disposisi->prioritas) }}
                    </span>
                </div>
                <div class="card-body">
                    <div style="padding:16px; background:var(--bg-surface-2); border-radius:var(--radius-base); border:1px solid var(--border-color); color:var(--text-title); line-height:1.6; font-size:0.9rem;">
                        {!! nl2br(e($disposisi->instruksi)) !!}
                    </div>
                </div>
            </div>

            {{-- Sumber Surat Masuk Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dokumen Surat Masuk Sumber</h3>
                    <a href="{{ route('surat-masuk.show', $disposisi->suratMasuk) }}" class="btn btn-outline btn-sm">
                        <span>Buka Surat Masuk</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Nomor Surat</div>
                        <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.8rem;">
                            {{ $disposisi->suratMasuk->nomor_surat }}
                        </span>
                    </div>
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Pengirim</div>
                        <div style="font-size:0.88rem; font-weight:600; color:var(--text-title);">
                            {{ $disposisi->suratMasuk->pengirim }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:2px;">Perihal</div>
                        <div style="font-size:0.88rem; color:var(--text-title);">
                            {{ $disposisi->suratMasuk->perihal }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
