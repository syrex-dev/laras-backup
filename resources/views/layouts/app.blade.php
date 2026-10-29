@php
    $org = \App\Models\ProfilOrganisasi::first();
    $cmsTheme = $org?->tema_default ?? 'light';

    // User avatar initials
    $nama = Auth::check() ? (Auth::user()->nama ?? '') : '';
    $words = explode(' ', trim($nama));
    $initials = count($words) >= 2
        ? strtoupper(substr($words[0],0,1) . substr($words[1],0,1))
        : strtoupper(substr($nama,0,2) ?: 'US');
@endphp
<!DOCTYPE html>
<html lang="id" class="{{ $cmsTheme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $org?->nama_organisasi ?? 'Simpan.in' }}</title>

    {{-- Anti-flash theme script — must be FIRST --}}
    <script>
        (function(){
            var cms = '{{ $cmsTheme }}';
            var saved = null;
            try { saved = localStorage.getItem('simpanin_theme'); } catch(e){}
            var theme = saved || cms || 'light';
            document.documentElement.className = theme;
            window.__CMS_DEFAULT_THEME__ = cms;
        })();
    </script>

    {{-- Google Fonts: Public Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    {{-- Vite: Tailwind CSS v4 build --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire styles --}}
    @livewireStyles

    @yield('styles')
</head>
<body>

{{-- ============================================================
     TOAST STACK
     ============================================================ --}}
<div id="toast-stack" aria-live="polite" aria-atomic="false"></div>

{{-- ============================================================
     LAYOUT WRAPPER
     ============================================================ --}}
<div class="layout-wrapper" id="layout-wrapper">

    {{-- ============================================================
         SIDEBAR
         ============================================================ --}}
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Sidebar navigasi utama">

        {{-- Logo + Collapse Toggle --}}
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-logo-wrap" title="{{ $org?->nama_organisasi ?? 'Simpan.in' }}">
                <div class="sidebar-logo-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <div class="sidebar-logo-text">
                    <div class="sidebar-logo-name">Simpan<span>.in</span></div>
                    <div class="sidebar-logo-sub">E-Office &amp; Arsip</div>
                </div>
            </a>
            <button class="sidebar-toggle" id="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" aria-label="Lipat/buka sidebar" title="Lipat sidebar">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav" id="sidebar-nav">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               id="nav-dashboard">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </span>
                <span class="sidebar-text">Dashboard</span>
            </a>

            {{-- ── PERSURATAN ── --}}
            <span class="sidebar-section-label">Persuratan</span>

            <a href="{{ route('surat-masuk.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('surat-masuk.*') ? 'active' : '' }}"
               id="nav-surat-masuk">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </span>
                <span class="sidebar-text">Surat Masuk</span>
            </a>

            <a href="{{ route('surat-keluar.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('surat-keluar.*') ? 'active' : '' }}"
               id="nav-surat-keluar">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </span>
                <span class="sidebar-text">Surat Keluar</span>
            </a>

            <a href="{{ route('disposisi.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('disposisi.*') ? 'active' : '' }}"
               id="nav-disposisi">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </span>
                <span class="sidebar-text">Disposisi</span>
            </a>

            {{-- ── KEARSIPAN ── --}}
            @role('Admin|Sekretaris')
            <span class="sidebar-section-label">Kearsipan</span>

            <a href="{{ route('klasifikasi-surat.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('klasifikasi-surat.*') ? 'active' : '' }}"
               id="nav-klasifikasi">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                    </svg>
                </span>
                <span class="sidebar-text">Klasifikasi Surat</span>
            </a>

            <a href="{{ route('template-surat.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('template-surat.*') ? 'active' : '' }}"
               id="nav-template">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </span>
                <span class="sidebar-text">Templat Surat</span>
            </a>
            @endrole

            {{-- ── PENGATURAN ── --}}
            @role('Admin')
            <span class="sidebar-section-label">Pengaturan</span>

            <a href="{{ route('admin.profile.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}"
               id="nav-profile-org">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </span>
                <span class="sidebar-text">Profil Organisasi</span>
            </a>

            <a href="{{ route('users.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
               id="nav-users">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                        <path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </span>
                <span class="sidebar-text">Manajemen User</span>
            </a>

            <a href="{{ route('admin.logs.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
               id="nav-logs">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
                <span class="sidebar-text">Audit Log</span>
            </a>

            <a href="{{ route('admin.recycle-bin.index') }}"
               class="sidebar-nav-item {{ request()->routeIs('admin.recycle-bin.*') ? 'active' : '' }}"
               id="nav-recycle">
                <span class="nav-icon-wrap" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                        <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                    </svg>
                </span>
                <span class="sidebar-text">Tong Sampah</span>
            </a>
            @endrole

        </nav>

        {{-- Sidebar Footer — Logout --}}
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="sidebar-nav-item danger" id="btn-sidebar-logout"
                    style="width:100%; color: var(--color-danger);"
                    onmouseover="this.style.background='var(--color-danger-soft)'"
                    onmouseout="this.style.background='transparent'">
                    <span class="nav-icon-wrap" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </span>
                    <span class="sidebar-text">Keluar Sesi</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeMobileSidebar()" aria-hidden="true"></div>

    {{-- ============================================================
         LAYOUT CONTENT
         ============================================================ --}}
    <div class="layout-content" id="layout-content">

        {{-- ============================================================
             NAVBAR
             ============================================================ --}}
        <header class="navbar" role="banner">
            {{-- Left: Mobile hamburger + search --}}
            <div style="display:flex; align-items:center; gap:12px;">
                {{-- Sidebar toggle button (Mantis style: works on both desktop and mobile) --}}
                <button class="navbar-btn" id="navbar-sidebar-toggle" onclick="handleNavbarSidebarToggle()"
                    aria-label="Buka/tutup sidebar" title="Lipat atau buka sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                {{-- Search trigger --}}
                <button class="navbar-search" id="search-trigger-btn" onclick="openSearchPalette()"
                    aria-label="Buka pencarian global" title="Cari [Ctrl+K]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Cari surat, nomor, perihal...</span>
                    <kbd class="navbar-search-kbd">Ctrl K</kbd>
                </button>
            </div>

            {{-- Right controls --}}
            <div class="navbar-controls">
                {{-- Dark/Light toggle --}}
                <button class="navbar-btn" id="theme-toggle-btn" onclick="toggleTheme()"
                    aria-label="Beralih mode gelap/terang" title="Beralih tema">
                    {{-- Sun (shown in dark mode) --}}
                    <svg id="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    {{-- Moon (shown in light mode) --}}
                    <svg id="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </button>

                <div class="navbar-divider" aria-hidden="true"></div>

                {{-- Avatar + dropdown --}}
                @auth
                <div class="dropdown" id="avatar-dropdown">
                    <div class="navbar-avatar-wrap" onclick="toggleAvatarDropdown()"
                        role="button" tabindex="0" aria-expanded="false" aria-haspopup="true"
                        aria-label="Menu profil pengguna"
                        onkeydown="if(event.key==='Enter'||event.key===' ')toggleAvatarDropdown()">
                        <div class="navbar-avatar" aria-hidden="true">
                            @if(!empty(Auth::user()->foto))
                                <img src="{{ asset('storage/' . Auth::user()->foto) }}"
                                     alt="{{ Auth::user()->nama }}">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <div class="navbar-avatar-info" style="display:none;" id="avatar-info">
                            <div class="navbar-avatar-name">{{ Auth::user()->nama }}</div>
                            <div class="navbar-avatar-role">{{ Auth::user()->roles->first()?->name ?? 'Pengguna' }}</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>

                    <div class="dropdown-menu" id="avatar-dropdown-menu" role="menu">
                        <div class="dropdown-header">
                            <div class="dropdown-header-name">{{ Auth::user()->nama }}</div>
                            <div class="dropdown-header-email">{{ Auth::user()->email }}</div>
                            <div style="margin-top:4px;">
                                <span class="badge badge-primary" style="font-size:0.68rem;">
                                    {{ Auth::user()->roles->first()?->name ?? 'Pengguna' }}
                                </span>
                            </div>
                        </div>
                        <hr class="dropdown-divider">
                        @role('Admin')
                        <a href="{{ route('admin.profile.index') }}" class="dropdown-item" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Profil Organisasi
                        </a>
                        @endrole
                        <a href="{{ route('profile.signature.index') }}" class="dropdown-item" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Tanda Tangan
                        </a>
                        <hr class="dropdown-divider">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item danger" role="menuitem" id="btn-logout">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Keluar Sesi
                            </button>
                        </form>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        {{-- ============================================================
             MAIN CONTENT
             ============================================================ --}}
        <main class="main-content" id="main-content" role="main">

            {{-- Flash alerts --}}
            @if(session('success'))
            <div class="alert alert-success" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                {{ session('warning') }}
            </div>
            @endif

            @yield('content')
        </main>

    </div>{{-- end layout-content --}}
</div>{{-- end layout-wrapper --}}

{{-- ============================================================
     SEARCH PALETTE MODAL
     ============================================================ --}}
<div id="search-palette-modal" role="dialog" aria-modal="true" aria-label="Pencarian global"
    style="display:none; position:fixed; inset:0; z-index:9990; background:rgba(0,0,0,0.4); backdrop-filter:blur(3px); align-items:flex-start; justify-content:center; padding:80px 16px 16px;">
    <div class="search-palette" id="search-palette-box">
        <div class="search-palette-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary); flex-shrink:0;" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="search-palette-input" class="search-palette-input"
                placeholder="Cari surat, nomor, perihal..."
                autocomplete="off" spellcheck="false">
        </div>
        <div class="search-palette-results" id="search-palette-results">
            <div style="padding:32px; text-align:center; color:var(--text-muted); font-size:0.875rem;">
                Ketik untuk mulai mencari...
            </div>
        </div>
        <div class="search-palette-footer">
            <span><kbd>↵</kbd> buka</span>
            <span><kbd>↑</kbd><kbd>↓</kbd> navigasi</span>
            <span><kbd>ESC</kbd> tutup</span>
        </div>
    </div>
</div>

{{-- ============================================================
     SCRIPTS
     ============================================================ --}}
<script>
    window.__SEARCH_URL__ = "{{ route('global.search') }}";
    window.__CMS_DEFAULT_THEME__ = '{{ $cmsTheme }}';
</script>
<script src="{{ asset('js/app.js') }}" defer></script>
<script src="{{ asset('js/theme.js') }}"></script>

<script>
/* ============================================================
   SIDEBAR — Collapse (desktop) & Mobile off-canvas
   ============================================================ */
(function(){
    var STORAGE_KEY = 'simpanin_sidebar_collapsed';
    var sidebar = document.getElementById('sidebar');
    var content = document.getElementById('layout-content');
    var toggleBtn = document.getElementById('sidebar-toggle-btn');
    var mobileBtn = document.getElementById('mobile-menu-btn');
    var overlay   = document.getElementById('sidebar-overlay');
    var isMobile  = window.innerWidth <= 1024;

    function applyCollapsed(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            if (content) content.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            if (content) content.classList.remove('sidebar-collapsed');
        }
    }

    // Restore state
    var saved = false;
    try { saved = localStorage.getItem(STORAGE_KEY) === '1'; } catch(e){}
    if (!isMobile) applyCollapsed(saved);

    window.toggleSidebarCollapse = function() {
        var isNowCollapsed = !sidebar.classList.contains('collapsed');
        applyCollapsed(isNowCollapsed);
        try { localStorage.setItem(STORAGE_KEY, isNowCollapsed ? '1' : '0'); } catch(e){}
    };

    window.handleNavbarSidebarToggle = function() {
        if (window.innerWidth <= 1024) {
            if (sidebar.classList.contains('mobile-open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        } else {
            toggleSidebarCollapse();
        }
    };

    window.openMobileSidebar = function() {
        sidebar.classList.add('mobile-open');
        if (overlay) { overlay.classList.add('show'); }
        document.body.style.overflow = 'hidden';
    };
    window.closeMobileSidebar = function() {
        sidebar.classList.remove('mobile-open');
        if (overlay) { overlay.classList.remove('show'); }
        document.body.style.overflow = '';
    };

    // Show/hide mobile button
    function updateMobileUI() {
        isMobile = window.innerWidth <= 1024;
        if (mobileBtn) mobileBtn.style.display = isMobile ? 'flex' : 'none';
    }
    updateMobileUI();
    window.addEventListener('resize', updateMobileUI);

    // Close on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMobileSidebar();
    });
})();

/* ============================================================
   THEME TOGGLE
   ============================================================ */
window.toggleTheme = function() {
    var html = document.documentElement;
    var isDark = html.classList.contains('dark');
    html.classList.toggle('dark', !isDark);
    html.classList.toggle('light', isDark);
    try { localStorage.setItem('simpanin_theme', isDark ? 'light' : 'dark'); } catch(e){}
    updateThemeIcons();
};
function updateThemeIcons() {
    var isDark = document.documentElement.classList.contains('dark');
    var sun  = document.getElementById('icon-sun');
    var moon = document.getElementById('icon-moon');
    if (sun)  sun.style.display  = isDark ? 'block' : 'none';
    if (moon) moon.style.display = isDark ? 'none'  : 'block';
}
document.addEventListener('DOMContentLoaded', updateThemeIcons);

/* ============================================================
   AVATAR DROPDOWN
   ============================================================ */
window.toggleAvatarDropdown = function() {
    var menu = document.getElementById('avatar-dropdown-menu');
    if (!menu) return;
    var isOpen = menu.classList.toggle('open');
    var wrap = menu.previousElementSibling;
    if (wrap) wrap.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
};
document.addEventListener('click', function(e) {
    var dd = document.getElementById('avatar-dropdown');
    if (dd && !dd.contains(e.target)) {
        var menu = document.getElementById('avatar-dropdown-menu');
        if (menu) {
            menu.classList.remove('open');
            var wrap = menu.previousElementSibling;
            if (wrap) wrap.setAttribute('aria-expanded', 'false');
        }
    }
});

/* ============================================================
   SEARCH PALETTE
   ============================================================ */
var _searchTimeout = null;
window.openSearchPalette = function() {
    var modal = document.getElementById('search-palette-modal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(function(){ document.getElementById('search-palette-input')?.focus(); }, 50);
    }
};
window.closeSearchPalette = function() {
    var modal = document.getElementById('search-palette-modal');
    if (modal) modal.style.display = 'none';
};
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openSearchPalette();
    }
    if (e.key === 'Escape') closeSearchPalette();
});
document.getElementById('search-palette-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSearchPalette();
});
document.getElementById('search-palette-input')?.addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(_searchTimeout);
    if (!q) {
        document.getElementById('search-palette-results').innerHTML =
            '<div style="padding:32px; text-align:center; color:var(--text-muted); font-size:0.875rem;">Ketik untuk mulai mencari...</div>';
        return;
    }
    _searchTimeout = setTimeout(function() {
        fetch(window.__SEARCH_URL__ + '?q=' + encodeURIComponent(q))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                var results = document.getElementById('search-palette-results');
                if (!data.length) {
                    results.innerHTML = '<div style="padding:32px; text-align:center; color:var(--text-muted); font-size:0.875rem;">Tidak ada hasil ditemukan.</div>';
                    return;
                }
                results.innerHTML = data.map(function(item) {
                    return '<a href="' + item.url + '" class="search-result-item" onclick="closeSearchPalette()">' +
                        '<span style="width:32px;height:32px;border-radius:var(--radius-base);background:var(--color-primary-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.68rem;font-weight:700;color:var(--color-primary);">' + (item.type === 'masuk' ? 'SM' : item.type === 'keluar' ? 'SK' : 'DS') + '</span>' +
                        '<div><div style="font-weight:600;color:var(--text-title);font-size:0.875rem;">' + item.title + '</div>' +
                        '<div style="font-size:0.75rem;color:var(--text-muted);">' + (item.subtitle || '') + '</div></div>' +
                        '</a>';
                }).join('');
            })
            .catch(function(){});
    }, 250);
});

/* ============================================================
   TOAST (global helper — used by flash & Livewire events)
   ============================================================ */
window.showToast = function(type, title, message, duration) {
    duration = duration || 4000;
    var icons = {
        success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-success-dark)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        danger:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning-dark)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>',
        info:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-info-dark)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };
    var stack = document.getElementById('toast-stack');
    if (!stack) return;
    var id = 'toast-' + Date.now();
    var el = document.createElement('div');
    el.className = 'toast'; el.id = id;
    el.innerHTML =
        '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
        '<div class="toast-body"><p class="toast-title">' + title + '</p>' + (message ? '<p class="toast-msg">' + message + '</p>' : '') + '</div>' +
        '<button class="toast-close" onclick="document.getElementById(\'' + id + '\').remove()" aria-label="Tutup notifikasi">✕</button>';
    stack.appendChild(el);
    setTimeout(function(){ if (document.getElementById(id)) document.getElementById(id).remove(); }, duration);
};
</script>

{{-- Livewire scripts (includes Alpine.js) --}}
@livewireScripts

@yield('scripts')
</body>
</html>
