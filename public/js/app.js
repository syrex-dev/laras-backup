/**
 * app.js - Logika Interaktif Aplikasi Simpan.in
 * Politeknik Mardira Indonesia
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // Sidebar Mobile Toggle
    // -------------------------------------------------------------
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    window.toggleSidebar = function () {
        if (!sidebar) return;
        const isOpen = sidebar.classList.contains('translate-x-0');
        if (isOpen) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('hidden');
            document.body.style.overflow = 'auto';
        } else {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    };

    // -------------------------------------------------------------
    // Global Search Modal & Engine
    // -------------------------------------------------------------
    const searchModal = document.getElementById('search-modal');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    const searchTrigger = document.getElementById('search-trigger');

    window.openSearch = function () {
        if (!searchModal) return;
        searchModal.classList.remove('hidden');
        if (searchInput) searchInput.focus();
        document.body.style.overflow = 'hidden';
    };

    window.closeSearch = function () {
        if (!searchModal) return;
        searchModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    };

    if (searchTrigger) {
        searchTrigger.addEventListener('click', window.openSearch);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
            e.preventDefault();
            window.openSearch();
        }
        if (e.key === 'Escape') {
            window.closeSearch();
        }
    });

    let searchTimeout;
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = `
                    <div class="py-12 text-center text-slate-500">
                        <p>Ketik minimal 2 karakter...</p>
                    </div>`;
                return;
            }

            searchTimeout = setTimeout(async () => {
                searchResults.innerHTML = `
                    <div class="py-12 text-center">
                        <div class="inline-block animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mb-3"></div>
                        <p class="text-slate-400">Mencari hasil terbaik...</p>
                    </div>`;

                try {
                    const searchUrl = window.__SEARCH_URL__ || '/search';
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (!data || data.length === 0) {
                        searchResults.innerHTML = `
                            <div class="py-12 text-center text-slate-500">
                                <p>Tidak ada hasil ditemukan untuk "${query}"</p>
                            </div>`;
                        return;
                    }

                    searchResults.innerHTML = data.map(item => `
                        <a href="${item.url}" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-100 dark:hover:bg-white/5 border border-transparent hover:border-slate-200 dark:hover:border-white/10 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${item.icon}"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h4 class="text-slate-900 dark:text-white font-medium truncate">${item.title}</h4>
                                    <span class="text-[10px] font-black uppercase text-amber-500 dark:text-amber-400 tracking-tighter">${item.type}</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">${item.subtitle}</p>
                            </div>
                        </a>
                    `).join('');
                } catch (error) {
                    searchResults.innerHTML = `<p class="p-4 text-red-500 text-center">Terjadi kesalahan pada server pencarian.</p>`;
                }
            }, 300);
        });
    }
});
