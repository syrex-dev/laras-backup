@extends('layouts.app')

@section('title', 'Surat Masuk')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header & Actions ── --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Persuratan</span>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Surat Masuk</span>
            </div>
            <h1 style="font-size:1.6rem; font-weight:800; color:var(--text-title); margin:0; line-height:1.2;">
                Surat Masuk
            </h1>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:4px 0 0;">
                Kelola arsip dokumen masuk, periksa tindak lanjut, dan delegasikan disposisi.
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('surat-masuk.create') }}" class="btn btn-primary" id="btn-create-surat-masuk">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Catat Surat Masuk</span>
            </a>
        </div>
    </div>

    {{-- ── 2. Mantis Metric / Summary Cards ── --}}
    @php
        $totalMasuk = \App\Models\SuratMasuk::count();
        $totalDisposisi = \App\Domain\Disposisi\Models\Disposisi::distinct('surat_masuk_id')->count('surat_masuk_id');
        $belumDisposisi = max(0, $totalMasuk - $totalDisposisi);
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:24px;">
        {{-- Total Surat Masuk --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Total Surat Masuk</span>
                <div class="stat-card-icon primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $totalMasuk }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>+59.3%</span>
                </span>
                <span class="trend-desc">Dokumen terarsip</span>
            </div>
        </div>

        {{-- Sudah Didisposisi --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Sudah Disposisi</span>
                <div class="stat-card-icon success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" style="color:var(--color-success-dark);">{{ $totalDisposisi }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>Tuntas</span>
                </span>
                <span class="trend-desc">Diteruskan ke staf</span>
            </div>
        </div>

        {{-- Belum Disposisi --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Menunggu Disposisi</span>
                <div class="stat-card-icon warning">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value" style="color:var(--color-warning-dark);">{{ $belumDisposisi }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill down">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    <span>Perlu Tindakan</span>
                </span>
                <span class="trend-desc">Instruksi lanjutan</span>
            </div>
        </div>
    </div>

    {{-- ── 3. Table Container with Toolbar ── --}}
    <div class="table-wrapper">
        {{-- Toolbar Filter --}}
        <div class="table-toolbar" style="background:var(--bg-surface);">
            <form action="{{ route('surat-masuk.index') }}" method="GET" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; width:100%;">
                {{-- Search Input --}}
                <div style="position:relative; flex:1; min-width:240px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari nomor surat, pengirim, atau perihal..."
                           class="form-control"
                           style="padding-left:36px;">
                </div>

                {{-- Status Filter --}}
                <div style="min-width:180px;">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Status Tindak Lanjut</option>
                        <option value="disposisi" {{ request('status') === 'disposisi' ? 'selected' : '' }}>Sudah Disposisi</option>
                        <option value="belum_disposisi" {{ request('status') === 'belum_disposisi' ? 'selected' : '' }}>Belum Disposisi</option>
                    </select>
                </div>

                {{-- Klasifikasi Filter --}}
                @if(isset($klasifikasiList) && $klasifikasiList->count() > 0)
                <div style="min-width:180px;">
                    <select name="klasifikasi_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Klasifikasi</option>
                        @foreach($klasifikasiList as $klas)
                            <option value="{{ $klas->id }}" {{ request('klasifikasi_id') == $klas->id ? 'selected' : '' }}>
                                {{ $klas->kode }} — {{ $klas->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Filter Actions --}}
                <button type="submit" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span>Filter</span>
                </button>

                @if(request('search') || request('status') || request('klasifikasi_id'))
                <a href="{{ route('surat-masuk.index') }}" class="btn btn-ghost btn-sm" title="Reset filter" style="color:var(--color-danger);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Reset</span>
                </a>
                @endif
            </form>
        </div>

        {{-- Table Content --}}
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="min-width:200px;">Identitas Surat</th>
                        <th style="min-width:180px;">Pengirim &amp; Klasifikasi</th>
                        <th style="min-width:280px;">Perihal &amp; Ringkasan</th>
                        <th style="min-width:150px;">Status Tindak Lanjut</th>
                        <th style="min-width:130px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratMasuk as $index => $surat)
                    @php
                        $disposisiCount = $surat->disposisi ? $surat->disposisi->count() : 0;
                    @endphp
                    <tr>
                        {{-- No --}}
                        <td style="text-align:center; color:var(--text-muted); font-size:0.8rem; font-weight:600;">
                            {{ $suratMasuk->firstItem() + $index }}
                        </td>

                        {{-- Identitas Surat --}}
                        <td>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.75rem; font-weight:700; width:fit-content;">
                                    {{ $surat->nomor_surat }}
                                </span>
                                <div style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--text-muted);">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>Tgl: {{ $surat->tanggal_surat?->format('d/m/Y') ?? '-' }}</span>
                                    @if($surat->tanggal_diterima)
                                        <span>• Terima: {{ $surat->tanggal_diterima->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Pengirim & Klasifikasi --}}
                        <td>
                            <div style="display:flex; flex-direction:column; gap:3px;">
                                <strong style="color:var(--text-title); font-size:0.875rem;">
                                    {{ $surat->pengirim }}
                                </strong>
                                @if($surat->klasifikasi)
                                    <span class="badge badge-info" style="width:fit-content; font-size:0.68rem;">
                                        {{ $surat->klasifikasi->kode }} - {{ $surat->klasifikasi->nama }}
                                    </span>
                                @else
                                    <span style="font-size:0.75rem; color:var(--text-muted);">Umum</span>
                                @endif
                            </div>
                        </td>

                        {{-- Perihal & Ringkasan --}}
                        <td>
                            <div style="display:flex; flex-direction:column; gap:3px;">
                                <a href="{{ route('surat-masuk.show', $surat) }}"
                                   style="color:var(--text-title); font-weight:600; text-decoration:none; line-height:1.4; display:block;"
                                   onmouseover="this.style.color='var(--color-primary)'"
                                   onmouseout="this.style.color='var(--text-title)'">
                                    {{ $surat->perihal }}
                                </a>
                                @if(!empty($surat->ringkasan))
                                    <p style="font-size:0.76rem; color:var(--text-muted); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        {{ $surat->ringkasan }}
                                    </p>
                                @endif
                            </div>
                        </td>

                        {{-- Status Tindak Lanjut (Disposisi) --}}
                        <td>
                            @if($disposisiCount > 0)
                                <a href="{{ route('surat-masuk.show', $surat) }}#disposisi" style="text-decoration:none;">
                                    <span class="badge badge-success" title="{{ $disposisiCount }} instruksi disposisi tercatat">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span>Didisposisi ({{ $disposisiCount }})</span>
                                    </span>
                                </a>
                            @else
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="badge badge-warning" title="Belum ada instruksi disposisi">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        <span>Belum Disposisi</span>
                                    </span>
                                    @role('Admin|Pimpinan|Sekretaris')
                                    <a href="{{ route('disposisi.create', ['surat_masuk_id' => $surat->id]) }}"
                                       class="btn-icon"
                                       title="Buat Disposisi untuk surat ini"
                                       style="width:26px; height:26px; color:var(--color-primary); border-color:var(--color-primary-soft-border); background:var(--color-primary-soft);">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                    </a>
                                    @endrole
                                </div>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td style="text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                {{-- Lihat --}}
                                <a href="{{ route('surat-masuk.show', $surat) }}"
                                   class="btn-icon"
                                   title="Lihat Detail &amp; Lampiran">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                {{-- Edit --}}
                                @role('Admin|Sekretaris')
                                <a href="{{ route('surat-masuk.edit', $surat) }}"
                                   class="btn-icon"
                                   title="Ubah Surat">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>

                                {{-- Hapus --}}
                                <form action="{{ route('surat-masuk.destroy', $surat) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus surat masuk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Hapus Surat" style="color:var(--color-danger);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endrole
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:48px 24px; text-align:center;">
                            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px;">
                                <div style="width:56px; height:56px; border-radius:50%; background:var(--color-primary-soft); display:flex; align-items:center; justify-content:center; color:var(--color-primary);">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </div>
                                <h3 style="font-size:1rem; font-weight:700; color:var(--text-title); margin:0;">
                                    Belum Ada Surat Masuk
                                </h3>
                                <p style="font-size:0.85rem; color:var(--text-muted); max-width:360px; margin:0;">
                                    @if(request('search') || request('status') || request('klasifikasi_id'))
                                        Tidak ada surat yang cocok dengan filter pencarian saat ini.
                                    @else
                                        Mulai arsipkan dokumen surat masuk perguruan tinggi sekarang.
                                    @endif
                                </p>
                                @if(request('search') || request('status') || request('klasifikasi_id'))
                                    <a href="{{ route('surat-masuk.index') }}" class="btn btn-outline btn-sm">
                                        Reset Filter
                                    </a>
                                @else
                                    <a href="{{ route('surat-masuk.create') }}" class="btn btn-primary btn-sm">
                                        + Tambah Surat Masuk
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if($suratMasuk->hasPages() || $suratMasuk->total() > 0)
        <div class="table-footer">
            <div class="table-count">
                Menampilkan <strong>{{ $suratMasuk->firstItem() ?? 0 }}</strong> - <strong>{{ $suratMasuk->lastItem() ?? 0 }}</strong> dari <strong>{{ $suratMasuk->total() }}</strong> surat masuk
            </div>
            <div>
                {{ $suratMasuk->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
