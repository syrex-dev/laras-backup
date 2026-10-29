import React from 'react';
import { AlertCircle, DownloadCloud } from 'lucide-react';
import { Link } from '@inertiajs/react';

export const ReadOnlyBanner: React.FC = () => {
    return (
        <div className="bg-amber-500 text-slate-900 px-4 py-2.5 shadow-md flex flex-wrap items-center justify-between gap-3 text-xs md:text-sm font-medium z-40">
            <div className="flex items-center gap-2">
                <AlertCircle className="w-5 h-5 text-slate-950 flex-shrink-0" />
                <span>
                    <strong>Mode Baca-Saja (Read-Only):</strong> Masa aktif lisensi E-Office telah kedaluwarsa. Seluruh data Anda aman dan tetap dapat dibaca, dicari, serta diekspor.
                </span>
            </div>
            <div className="flex items-center gap-2">
                <Link
                    href="/pengaturan/lisensi"
                    className="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-950 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition-colors"
                >
                    <DownloadCloud className="w-3.5 h-3.5" />
                    <span>Perbarui Lisensi / Ekspor Data</span>
                </Link>
            </div>
        </div>
    );
};
