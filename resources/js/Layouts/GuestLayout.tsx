import React from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export const GuestLayout: React.FC<{ children: React.ReactNode; title?: string }> = ({
    children,
    title,
}) => {
    const { props } = usePage<PageProps>();
    const { institution } = props;

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-100 flex flex-col justify-center items-center p-4">
            <div className="w-full max-w-md">
                <div className="text-center mb-6">
                    <div className="inline-flex w-12 h-12 rounded-xl bg-emerald-600 text-white font-bold items-center justify-center text-xl shadow-lg shadow-emerald-500/25 mb-3">
                        {institution?.alias?.substring(0, 2) || 'EO'}
                    </div>
                    <h1 className="text-xl font-bold text-slate-900 tracking-tight">
                        {institution?.name || 'Sistem E-Office'}
                    </h1>
                    {title && <p className="text-xs text-slate-500 mt-1">{title}</p>}
                </div>

                <div className="bg-white border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/50 p-6 md:p-8">
                    {children}
                </div>

                <div className="text-center mt-6 text-xs text-slate-400">
                    &copy; {new Date().getFullYear()} {institution?.name || 'E-Office'}. Hak cipta dilindungi.
                </div>
            </div>
        </div>
    );
};
