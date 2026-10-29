/**
 * theme.js - Pengelolaan Mode Gelap & Terang Simpan.in
 * Politeknik Mardira Indonesia
 */

(function () {
    // 1. Inisialisasi tema sebelum DOM selesai dimuat untuk mencegah flicker
    try {
        const isLoginPage = window.__IS_LOGIN_PAGE__ || false;
        const cmsTheme = window.__CMS_DEFAULT_THEME__ || null;
        const savedTheme = localStorage.getItem('simpanin_theme');

        // Jika di halaman login, wajib mengikuti settingan tema dari CMS
        let activeTheme;
        if (isLoginPage && cmsTheme) {
            activeTheme = cmsTheme;
        } else {
            activeTheme = savedTheme || cmsTheme || 'light';
        }

        if (activeTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
    } catch (e) {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    }
})();

// 2. Fungsi Toggle Tema (untuk tombol di header)
window.toggleTheme = function () {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');

    if (isDark) {
        html.classList.remove('dark');
        html.classList.add('light');
        localStorage.setItem('simpanin_theme', 'light');
    } else {
        html.classList.remove('light');
        html.classList.add('dark');
        localStorage.setItem('simpanin_theme', 'dark');
    }

    window.updateThemeIcons();
};

// 3. Update Ikon Sun / Moon
window.updateThemeIcons = function () {
    const isDark = document.documentElement.classList.contains('dark');
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    const toggleBtn = document.getElementById('theme-toggle');

    if (sunIcon && moonIcon) {
        if (isDark) {
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
            if (toggleBtn) toggleBtn.setAttribute('title', 'Beralih ke Mode Terang');
        } else {
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
            if (toggleBtn) toggleBtn.setAttribute('title', 'Beralih ke Mode Gelap');
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    window.updateThemeIcons();
});
