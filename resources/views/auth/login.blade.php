<!DOCTYPE html>
<html lang="id" class="{{ $defaultTheme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Simpan.in | E-Office &amp; Arsip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
        window.__IS_LOGIN_PAGE__ = true;
        window.__CMS_DEFAULT_THEME__ = '{{ $defaultTheme ?? "light" }}';
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <script src="{{ asset('js/theme.js') }}"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="relative w-full max-w-md">
        <!-- Brand Header: Simpan.in Mantis Style -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#1677ff] text-white shadow-md shadow-blue-500/20 mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white" style="letter-spacing:-0.02em;">
                SIMPAN<span style="color:#1677ff;">.IN</span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                {{ $profil->nama_instansi ?? 'Politeknik Mardira Indonesia' }}
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Sistem Informasi Persuratan &amp; Tata Naskah Dinas</p>
        </div>

        <!-- Login Card -->
        <div class="login-card p-7 sm:p-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white" style="line-height:1.2;">Masuk ke Akun</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Masukkan kredensial akun SIMPAN.IN Anda.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="flex items-center gap-2.5 bg-red-500/10 border border-red-500/30 text-red-500 px-3.5 py-2.5 rounded-lg text-xs mb-5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="nama@poltek-mardira.ac.id"
                        class="login-input w-full px-3.5 py-2.5 text-sm focus:outline-none transition-all"
                    >
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Kata Sandi</label>
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="login-input w-full px-3.5 py-2.5 text-sm focus:outline-none transition-all"
                    >
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" style="accent-color:#1677ff;">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Ingat sesi login saya</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full btn-login-submit py-2.5 px-5 text-sm flex items-center justify-center gap-2">
                    <span>Masuk ke Dashboard</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Demo Accounts Box -->
        <div class="mt-5 demo-box p-4 text-xs">
            <p class="font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5 text-[12px]">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Akun Demo Role SIMPAN.IN:
            </p>
            <div class="grid grid-cols-2 gap-2 text-[11px]">
                <div class="p-2 bg-slate-100 dark:bg-zinc-800 rounded">
                    <strong class="text-blue-600 dark:text-blue-400">Admin</strong>: admin@himase.ac.id
                </div>
                <div class="p-2 bg-slate-100 dark:bg-zinc-800 rounded">
                    <strong class="text-amber-600 dark:text-amber-400">Pimpinan</strong>: ketua@himase.ac.id
                </div>
                <div class="p-2 bg-slate-100 dark:bg-zinc-800 rounded">
                    <strong class="text-teal-600 dark:text-teal-400">Sekretaris</strong>: sekretaris@himase.ac.id
                </div>
                <div class="p-2 bg-slate-100 dark:bg-zinc-800 rounded">
                    <strong class="text-slate-600 dark:text-slate-400">Staf</strong>: staf@himase.ac.id
                </div>
            </div>
            <p class="mt-2 text-center text-[10px] text-slate-400">Kata sandi default: <code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-zinc-700 font-mono text-slate-700 dark:text-slate-300">password</code></p>
        </div>

        <p class="text-center text-[11px] text-slate-400 dark:text-slate-600 mt-5">
            &copy; {{ date('Y') }} Politeknik Mardira Indonesia · Simpan.in
        </p>
    </div>

</body>
</html>
