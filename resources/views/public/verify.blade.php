<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian Dokumen — Simpan.in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
        (function() {
            try {
                const savedTheme = localStorage.getItem('simpanin_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                }
            } catch (e) {
                document.documentElement.classList.add('light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #1677ff;
            --color-success: #52c41a;
            --color-danger: #ff4d4f;
            --font-sans: 'Public Sans', sans-serif;
        }
        body {
            font-family: var(--font-sans);
            background-color: #fafafb;
            color: #262626;
            margin: 0;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        html.dark body {
            background-color: #141414 !important;
            color: #e6e6e6 !important;
        }
        .mantis-card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.06);
        }
        html.dark .mantis-card {
            background: #1f1f1f;
            border-color: #303030;
            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.4);
        }
        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(82, 196, 26, 0.1);
            color: #389e0d;
            border: 1px solid rgba(82, 196, 26, 0.35);
        }
        .badge-invalid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(255, 77, 79, 0.1);
            color: #cf1322;
            border: 1px solid rgba(255, 77, 79, 0.35);
        }
    </style>
</head>
<body class="min-h-screen py-12 px-4 flex flex-col items-center justify-center">

    <div class="mb-8 text-center">
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
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sistem Validasi &amp; Verifikasi Keabsahan Dokumen Digital</p>
    </div>

    <div class="max-w-lg w-full mantis-card p-6 sm:p-8">
        @if(isset($surat))
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-3 border border-emerald-500/20">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Dokumen Resmi Terverifikasi</h2>
                <div class="mt-2">
                    <span class="badge-verified">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Tanda Tangan Elektronik Sah</span>
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-zinc-800/60 border border-slate-200 dark:border-zinc-700/60 space-y-3 text-left">
                    <div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Nomor Surat</div>
                        <div class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400">{{ $surat->nomor_surat }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Perihal</div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $surat->perihal }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Tanggal Terbit</div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $surat->tanggal_surat->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Status Dokumen</div>
                        <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Aktif &amp; Terarsip Resmi</div>
                    </div>
                </div>

                <p class="text-center text-xs text-slate-400 dark:text-slate-500 pt-2">
                    Integritas dan keaslian naskah dokumen ini dijamin oleh SIMPAN.IN melalui enkripsi QR Token.
                </p>
            </div>
        @else
            <div class="text-center py-4">
                <div class="w-14 h-14 bg-red-500/10 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-3 border border-red-500/20">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Dokumen Tidak Ditemukan</h2>
                <div class="mt-2 mb-4">
                    <span class="badge-invalid">Token Tidak Valid</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    QR Code tidak terdaftar dalam pangkalan data resmi atau dokumen telah dihapus secara permanen.
                </p>
                <div class="mt-6">
                    <a href="/" class="inline-flex items-center px-4 py-2 bg-[#1677ff] hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-sm">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-8 text-center text-xs text-slate-400 dark:text-slate-600">
        &copy; {{ date('Y') }} Simpan.in — Official Digital Archive System
    </div>
</body>
</html>
