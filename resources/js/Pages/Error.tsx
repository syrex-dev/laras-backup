import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ShieldAlert, FileQuestion, Clock, ServerCrash, ArrowLeft, Home, LogIn } from 'lucide-react';

interface ErrorProps {
    status: number;
    message?: string;
}

export default function ErrorPage({ status, message }: ErrorProps) {
    const errorDetails: Record<
        number,
        {
            title: string;
            description: string;
            icon: React.ElementType;
            color: string;
            bg: string;
        }
    > = {
        403: {
            title: 'Akses Ditolak (403)',
            description:
                'Anda tidak memiliki izin hak akses untuk membuka halaman atau menjalankan tindakan ini.',
            icon: ShieldAlert,
            color: 'text-rose-600',
            bg: 'bg-rose-50',
        },
        404: {
            title: 'Halaman Tidak Ditemukan (404)',
            description:
                'Alamat naskah atau tautan yang Anda tuju tidak ditemukan atau mungkin telah dipindahkan.',
            icon: FileQuestion,
            color: 'text-amber-600',
            bg: 'bg-amber-50',
        },
        419: {
            title: 'Sesi Kedaluwarsa (419)',
            description:
                'Sesi keamanan Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali untuk melanjutkan.',
            icon: Clock,
            color: 'text-sky-600',
            bg: 'bg-sky-50',
        },
        500: {
            title: 'Kendala Server Internal (500)',
            description:
                'Terjadi kesalahan pemrosesan pada peladen aplikasi. Tim administrator telah diberitahu mengenai hal ini.',
            icon: ServerCrash,
            color: 'text-rose-600',
            bg: 'bg-rose-50',
        },
        503: {
            title: 'Pemeliharaan Sistem (503)',
            description:
                'Sistem persuratan sedang dalam pemeliharaan rutin berkala. Mohon tunggu beberapa saat.',
            icon: ServerCrash,
            color: 'text-amber-600',
            bg: 'bg-amber-50',
        },
    };

    const currentError = errorDetails[status] || {
        title: `Terjadi Kesalahan (${status})`,
        description: message || 'Terjadi kendala saat memproses permintaan Anda.',
        icon: ShieldAlert,
        color: 'text-slate-600',
        bg: 'bg-slate-100',
    };

    const Icon = currentError.icon;

    return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans">
            <Head title={currentError.title} />

            <div className="max-w-md w-full bg-white rounded-3xl border border-slate-200/80 shadow-xl p-8 md:p-10 text-center space-y-6">
                <div
                    className={`w-20 h-20 rounded-3xl ${currentError.bg} ${currentError.color} flex items-center justify-center mx-auto shadow-inner`}
                >
                    <Icon className="w-10 h-10" />
                </div>

                <div>
                    <span className="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">
                        Status HTTP {status}
                    </span>
                    <h1 className="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
                        {currentError.title}
                    </h1>
                    <p className="text-sm text-slate-500 mt-2 leading-relaxed">
                        {message || currentError.description}
                    </p>
                </div>

                <div className="pt-2 flex items-center justify-center gap-3 flex-wrap">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-xs"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Kembali</span>
                    </button>

                    {status === 419 ? (
                        <Link
                            href="/login"
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-xs"
                        >
                            <LogIn className="w-4 h-4" />
                            <span>Masuk Kembali</span>
                        </Link>
                    ) : (
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-xs"
                        >
                            <Home className="w-4 h-4" />
                            <span>Ke Dashboard</span>
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}
