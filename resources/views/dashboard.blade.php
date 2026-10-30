@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $user = Auth::user();
    $org = \App\Models\ProfilOrganisasi::first();
    $suratMasukTerbaru = \App\Models\SuratMasuk::with('klasifikasi')->latest()->take(5)->get();
    $pendingTasks = \App\Domain\Disposisi\Models\Disposisi::with('suratMasuk')
        ->where('penerima_id', Auth::id())
        ->where('status', 'belum_dibaca')
        ->latest()
        ->take(5)
        ->get();
@endphp

<div class="space-y-6">

    {{-- ── 1. Mantis Breadcrumbs & Header ── --}}
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <span>Dashboard</span>
                <span>/</span>
                <span style="color:var(--text-title); font-weight:600;">Default</span>
            </div>
            <h1 style="font-size:1.35rem; font-weight:700; color:var(--text-title); margin:0;">
                Dashboard
            </h1>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            @role('Admin|Sekretaris')
            <a href="{{ route('surat-keluar.create') }}" class="btn btn-primary btn-sm" id="dash-btn-create-sk">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Buat Surat Keluar</span>
            </a>
            @endrole
            <a href="{{ route('surat-masuk.create') }}" class="btn btn-outline btn-sm" id="dash-btn-create-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Catat Surat Masuk</span>
            </a>
        </div>
    </div>

    {{-- ── 2. Mantis Metric Stat Cards ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:18px; margin-bottom:24px;">
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
            <div class="stat-card-value">{{ $stats['surat_masuk'] }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>+59.3%</span>
                </span>
                <span class="trend-desc">Arsip digital</span>
            </div>
        </div>

        {{-- Surat Keluar --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Total Surat Keluar</span>
                <div class="stat-card-icon success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['surat_keluar'] }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>+70.5%</span>
                </span>
                <span class="trend-desc">Tervalidasi TTE &amp; QR</span>
            </div>
        </div>

        {{-- Disposisi --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Total Disposisi</span>
                <div class="stat-card-icon warning">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['disposisi'] }}</div>
            <div class="stat-card-trend">
                <span class="trend-pill up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    <span>27.4%</span>
                </span>
                <span class="trend-desc">Instruksi vertikal</span>
            </div>
        </div>

        {{-- Klasifikasi --}}
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-card-label">Klasifikasi Surat</span>
                <div class="stat-card-icon info">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card-value">{{ $stats['klasifikasi'] }}</div>
            <div class="stat-card-trend">
                <span class="badge badge-info" style="font-size:0.7rem; padding:1px 6px;">
                    Terkatalog
                </span>
                <span class="trend-desc">Kategori aktif</span>
            </div>
        </div>
    </div>

    {{-- ── 3. Main Chart & Task Section (Mantis Layout) ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px; align-items:start; margin-bottom:24px;">
        
        {{-- Chart Card (Spans 2 columns on wide screen) --}}
        <div class="card" style="grid-column: 1 / -1; @media(min-width:1024px){ grid-column: span 2; }">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Volume Persuratan</h2>
                    <p class="card-subtitle">Statistik aliran surat masuk &amp; surat keluar 6 bulan terakhir</p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="display:flex; align-items:center; gap:4px; background:var(--bg-surface-2); padding:3px; border-radius:var(--radius-base); border:1px solid var(--border-color);">
                        <button id="btnLine" class="btn btn-sm btn-primary" style="padding:3px 10px; font-size:0.75rem;">Garis</button>
                        <button id="btnBar" class="btn btn-sm btn-ghost" style="padding:3px 10px; font-size:0.75rem;">Batang</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="position:relative; height:290px; width:100%;">
                    <canvas id="statsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Pending Disposisi Tasks (Mantis Activity Card) --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Tugas Disposisi</h2>
                    <p class="card-subtitle">Instruksi yang memerlukan tindak lanjut</p>
                </div>
                <span class="badge badge-warning">{{ $pendingTasks->count() }} Pending</span>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="display:flex; flex-direction:column;">
                    @forelse($pendingTasks as $task)
                    <a href="{{ route('disposisi.show', $task) }}"
                       style="display:flex; align-items:flex-start; gap:12px; padding:14px 20px; border-bottom:1px solid var(--border-color); text-decoration:none; transition:background var(--transition);"
                       onmouseover="this.style.background='var(--sidebar-item-hover)'"
                       onmouseout="this.style.background='transparent'">
                        <div style="width:34px; height:34px; border-radius:var(--radius-md); background:var(--color-warning-soft); border:1px solid var(--color-warning-border); color:var(--color-warning-dark); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                <span style="font-size:0.72rem; font-weight:700; color:var(--color-primary); font-family:ui-monospace, monospace;">
                                    {{ $task->suratMasuk?->nomor_surat ?? 'Surat Masuk' }}
                                </span>
                                <span style="font-size:0.7rem; color:var(--text-muted);">
                                    {{ $task->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <div style="font-size:0.85rem; font-weight:500; color:var(--text-title); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;">
                                {{ $task->instruksi }}
                            </div>
                        </div>
                    </a>
                    @empty
                    <div style="padding:40px 20px; text-align:center;">
                        <div style="width:42px; height:42px; border-radius:50%; background:var(--color-success-soft); border:1px solid var(--color-success-border); color:var(--color-success-dark); display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <p style="font-size:0.875rem; font-weight:600; color:var(--text-title); margin:0;">Semua Tugas Selesai</p>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin:4px 0 0;">Tidak ada instruksi disposisi pending.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @if($pendingTasks->count() > 0)
            <div class="card-footer" style="text-align:center;">
                <a href="{{ route('disposisi.index') }}" style="font-size:0.8rem; font-weight:600; color:var(--color-primary); text-decoration:none;">
                    Buka Seluruh Disposisi &rarr;
                </a>
            </div>
            @endif
        </div>

    </div>

    {{-- ── 4. Recent Letters Table (Mantis Style) ── --}}
    <div class="table-wrapper">
        <div class="table-toolbar">
            <div>
                <h3 class="table-title">Surat Masuk Terbaru</h3>
                <p style="font-size:0.78rem; color:var(--text-muted); margin:2px 0 0;">Dokumen terbaru yang dicatat ke sistem</p>
            </div>
            <a href="{{ route('surat-masuk.index') }}" class="btn btn-outline btn-sm">
                <span>Lihat Semua Surat</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width:170px;">Tracking / Nomor</th>
                        <th style="min-width:160px;">Pengirim</th>
                        <th style="min-width:260px;">Perihal</th>
                        <th style="min-width:120px;">Tanggal</th>
                        <th style="min-width:90px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratMasukTerbaru as $sm)
                    <tr>
                        <td>
                            <span class="badge badge-primary" style="font-family:ui-monospace, monospace; font-size:0.74rem;">
                                {{ $sm->nomor_surat }}
                            </span>
                        </td>
                        <td>
                            <strong style="color:var(--text-title); font-size:0.85rem; font-weight:600;">{{ $sm->pengirim }}</strong>
                        </td>
                        <td>
                            <a href="{{ route('surat-masuk.show', $sm) }}" style="color:var(--text-title); text-decoration:none; font-weight:500;"
                               onmouseover="this.style.color='var(--color-primary)'"
                               onmouseout="this.style.color='var(--text-title)'">
                                {{ $sm->perihal }}
                            </a>
                        </td>
                        <td style="font-size:0.8rem; color:var(--text-muted);">
                            {{ $sm->tanggal_surat?->format('d M Y') ?? '-' }}
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('surat-masuk.show', $sm) }}" class="btn-icon" title="Lihat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:28px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
                            Belum ada surat masuk tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function(){
        var ctx = document.getElementById('statsChart');
        if (!ctx) return;
        var c = ctx.getContext('2d');

        var isDark = document.documentElement.classList.contains('dark');
        var gridColor = isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0';
        var labelColor = isDark ? '#8c8c8c' : '#8c8c8c';

        // Gradients with Mantis Blue (#1677ff) and Mantis Cyan (#13c2c2)
        var blueGrad = c.createLinearGradient(0, 0, 0, 270);
        blueGrad.addColorStop(0, 'rgba(22, 119, 255, 0.28)');
        blueGrad.addColorStop(1, 'rgba(22, 119, 255, 0.00)');

        var cyanGrad = c.createLinearGradient(0, 0, 0, 270);
        cyanGrad.addColorStop(0, 'rgba(19, 194, 194, 0.28)');
        cyanGrad.addColorStop(1, 'rgba(19, 194, 194, 0.00)');

        var lineDatasets = [{
            label: 'Surat Masuk',
            data: [12, 19, 15, 25, 22, 30],
            borderColor: '#1677ff',
            borderWidth: 2,
            backgroundColor: blueGrad,
            tension: 0.35,
            fill: true,
            pointBackgroundColor: '#1677ff',
            pointBorderColor: '#ffffff',
            pointRadius: 3.5,
            pointHoverRadius: 6
        }, {
            label: 'Surat Keluar',
            data: [5, 12, 8, 18, 15, 25],
            borderColor: '#13c2c2',
            borderWidth: 2,
            backgroundColor: cyanGrad,
            tension: 0.35,
            fill: true,
            pointBackgroundColor: '#13c2c2',
            pointBorderColor: '#ffffff',
            pointRadius: 3.5,
            pointHoverRadius: 6
        }];

        var barDatasets = [{
            label: 'Surat Masuk',
            data: [12, 19, 15, 25, 22, 30],
            backgroundColor: 'rgba(22, 119, 255, 0.85)',
            borderColor: '#1677ff',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Surat Keluar',
            data: [5, 12, 8, 18, 15, 25],
            backgroundColor: 'rgba(19, 194, 194, 0.85)',
            borderColor: '#13c2c2',
            borderWidth: 1,
            borderRadius: 4
        }];

        var myChart = null;

        function render(type) {
            if (myChart) myChart.destroy();
            myChart = new Chart(c, {
                type: type,
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: type === 'line' ? lineDatasets : barDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: labelColor,
                                font: { family: "'Public Sans', sans-serif", size: 12, weight: '500' },
                                usePointStyle: true,
                                boxWidth: 6,
                                padding: 16
                            }
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(31,31,31,0.95)' : 'rgba(38,38,38,0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#e8e8e8',
                            titleFont: { family: "'Public Sans', sans-serif", weight: '600' },
                            bodyFont: { family: "'Public Sans', sans-serif" },
                            padding: 10,
                            borderRadius: 6,
                            borderColor: isDark ? '#434343' : '#303030',
                            borderWidth: 1
                        }
                    },
                    interaction: { intersect: false, mode: 'index' },
                    scales: {
                        y: {
                            grid: { color: gridColor },
                            ticks: { color: labelColor, font: { family: "'Public Sans', sans-serif", size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: labelColor, font: { family: "'Public Sans', sans-serif", size: 11 } }
                        }
                    }
                }
            });
        }

        render('line');

        var btnLine = document.getElementById('btnLine');
        var btnBar  = document.getElementById('btnBar');

        if (btnLine && btnBar) {
            btnLine.addEventListener('click', function(){
                render('line');
                btnLine.className = 'btn btn-sm btn-primary';
                btnBar.className = 'btn btn-sm btn-ghost';
            });
            btnBar.addEventListener('click', function(){
                render('bar');
                btnBar.className = 'btn btn-sm btn-primary';
                btnLine.className = 'btn btn-sm btn-ghost';
            });
        }
    })();
</script>
@endsection
