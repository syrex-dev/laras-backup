import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useTerminology } from '@/Hooks/useTerminology';
import { ReadOnlyBanner } from '@/Components/ReadOnlyBanner';
import { Badge } from '@/Components/Badge';
import {
    LayoutDashboard,
    Mail,
    Send,
    FileCheck2,
    Archive,
    Building2,
    Users,
    Settings,
    ShieldAlert,
    LogOut,
    Menu,
    X,
    Bell,
    CheckCircle2,
    AlertTriangle,
} from 'lucide-react';

export const AppLayout: React.FC<{ children: React.ReactNode; title?: string }> = ({
    children,
    title,
}) => {
    const { props } = usePage<PageProps>();
    const { auth, institution, license, flash } = props;
    const { term } = useTerminology();
    const [mobileOpen, setMobileOpen] = useState(false);

    const handleLogout = () => {
        router.post('/logout');
    };

    const userRoles = auth.user?.roles?.map((r) => r.name) || [];
    const isPimpinan = userRoles.includes('Pimpinan');
    const isAdmin = userRoles.includes('Admin') || userRoles.includes('Admin Instansi');

    const navigation = [
        {
            name: 'Dashboard',
            href: '/dashboard',
            icon: LayoutDashboard,
            current: window.location.pathname === '/dashboard',
        },
        {
            name: 'Surat Masuk',
            href: '/surat-masuk',
            icon: Mail,
            current: window.location.pathname.startsWith('/surat-masuk'),
        },
        {
            name: 'Disposisi',
            href: '/disposisi',
            icon: FileCheck2,
            current: window.location.pathname.startsWith('/disposisi'),
        },
        {
            name: 'Surat Keluar',
            href: '/surat-keluar',
            icon: Send,
            current: window.location.pathname.startsWith('/surat-keluar'),
        },
        {
            name: 'Kearsipan Dinamis',
            href: '/klasifikasi-surat',
            icon: Archive,
            current: window.location.pathname.startsWith('/klasifikasi-surat') || window.location.pathname.startsWith('/arsip'),
        },
        {
            name: `Unit Kerja (${term('unit_level_1', 'Jurusan')})`,
            href: '/organisasi/unit',
            icon: Building2,
            current: window.location.pathname.startsWith('/organisasi'),
            adminOnly: true,
        },
        {
            name: 'Manajemen Pengguna',
            href: '/users',
            icon: Users,
            current: window.location.pathname.startsWith('/users'),
            adminOnly: true,
        },
        {
            name: 'Profil & Kamus Istilah',
            href: '/pengaturan/profil',
            icon: Settings,
            current: window.location.pathname.startsWith('/pengaturan/profil'),
            adminOnly: true,
        },
        {
            name: 'Lisensi & Sistem',
            href: '/pengaturan/lisensi',
            icon: ShieldAlert,
            current: window.location.pathname.startsWith('/pengaturan/lisensi'),
            adminOnly: true,
        },
    ];

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans">
            {/* Read-Only License Banner */}
            {license?.is_read_only && <ReadOnlyBanner />}

            {/* Flash Messages Floating Toast */}
            {flash.success && (
                <div className="fixed top-4 right-4 z-50 bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2.5 text-sm animate-in fade-in slide-in-from-top-4">
                    <CheckCircle2 className="w-5 h-5 flex-shrink-0" />
                    <span>{flash.success}</span>
                </div>
            )}
            {flash.error && (
                <div className="fixed top-4 right-4 z-50 bg-rose-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2.5 text-sm animate-in fade-in slide-in-from-top-4">
                    <AlertTriangle className="w-5 h-5 flex-shrink-0" />
                    <span>{flash.error}</span>
                </div>
            )}

            <div className="flex flex-1">
                {/* Desktop Sidebar (Fixed 260px) */}
                <aside className="hidden md:flex w-64 bg-white border-r border-slate-200 flex-col flex-shrink-0 justify-between">
                    <div>
                        {/* Header Branding */}
                        <div className="h-16 px-6 border-b border-slate-100 flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-base shadow-sm shadow-emerald-500/20">
                                {institution?.alias?.substring(0, 2) || 'EO'}
                            </div>
                            <div className="min-w-0 flex-1">
                                <h1 className="text-sm font-bold text-slate-900 truncate">
                                    {institution?.name || 'E-Office System'}
                                </h1>
                                <p className="text-[11px] text-slate-400 font-medium truncate">
                                    Tata Usaha & Persuratan
                                </p>
                            </div>
                        </div>

                        {/* Navigation Links */}
                        <nav className="p-3 space-y-1">
                            {navigation.map((item) => {
                                if (item.adminOnly && !isAdmin) return null;
                                const Icon = item.icon;
                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={`flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                                            item.current
                                                ? 'bg-emerald-50 text-emerald-700 font-semibold shadow-xs'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }`}
                                    >
                                        <Icon className={`w-4 h-4 ${item.current ? 'text-emerald-600' : 'text-slate-400'}`} />
                                        <span className="truncate">{item.name}</span>
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>

                    {/* Sidebar Footer User Card */}
                    <div className="p-4 border-t border-slate-100 bg-slate-50/50">
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-slate-800 truncate">
                                    {auth.user?.name || 'Pengguna'}
                                </p>
                                <p className="text-xs text-slate-500 truncate">
                                    {auth.user?.position?.name || userRoles[0] || 'Pegawai'}
                                </p>
                            </div>
                            <button
                                onClick={handleLogout}
                                title="Keluar dari sistem"
                                className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                            >
                                <LogOut className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </aside>

                {/* Mobile Drawer (Responsive Overlay) */}
                {mobileOpen && (
                    <div className="fixed inset-0 z-50 md:hidden flex">
                        <div
                            className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs"
                            onClick={() => setMobileOpen(false)}
                        />
                        <div className="relative w-72 max-w-[80vw] bg-white h-full flex flex-col justify-between shadow-2xl z-10 animate-in slide-in-from-left duration-200">
                            <div>
                                <div className="h-16 px-5 border-b border-slate-100 flex items-center justify-between">
                                    <div className="flex items-center gap-2.5 min-w-0">
                                        <div className="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-sm">
                                            {institution?.alias?.substring(0, 2) || 'EO'}
                                        </div>
                                        <span className="font-bold text-slate-900 text-sm truncate">
                                            {institution?.alias || 'E-Office'}
                                        </span>
                                    </div>
                                    <button
                                        onClick={() => setMobileOpen(false)}
                                        className="p-1 text-slate-400 hover:text-slate-600"
                                    >
                                        <X className="w-5 h-5" />
                                    </button>
                                </div>
                                <nav className="p-3 space-y-1 overflow-y-auto max-h-[70vh]">
                                    {navigation.map((item) => {
                                        if (item.adminOnly && !isAdmin) return null;
                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                onClick={() => setMobileOpen(false)}
                                                className={`flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium ${
                                                    item.current
                                                        ? 'bg-emerald-50 text-emerald-700 font-semibold'
                                                        : 'text-slate-600 hover:bg-slate-50'
                                                }`}
                                            >
                                                <Icon className="w-4 h-4 text-slate-400" />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </nav>
                            </div>
                            <div className="p-4 border-t border-slate-100 flex items-center justify-between">
                                <span className="text-xs text-slate-600 font-medium truncate">
                                    {auth.user?.name}
                                </span>
                                <button
                                    onClick={handleLogout}
                                    className="p-1 text-rose-600"
                                >
                                    <LogOut className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Main View Area */}
                <div className="flex-1 flex flex-col min-w-0">
                    {/* Topbar */}
                    <header className="h-16 bg-white border-b border-slate-200 px-4 md:px-8 flex items-center justify-between gap-4 sticky top-0 z-30 shadow-xs">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setMobileOpen(true)}
                                className="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100"
                            >
                                <Menu className="w-5 h-5" />
                            </button>
                            <div>
                                <h2 className="text-base md:text-lg font-bold text-slate-900 tracking-tight">
                                    {title || 'Dashboard'}
                                </h2>
                            </div>
                        </div>

                        {/* Topbar Right Profile & Quick Actions */}
                        <div className="flex items-center gap-3">
                            {/* Mobile-First Pimpinan Indicator */}
                            {isPimpinan && (
                                <Badge variant="primary" dot className="hidden sm:inline-flex">
                                    {term('lead_level_1', 'Pimpinan')}
                                </Badge>
                            )}
                            <div className="hidden sm:block text-right">
                                <p className="text-xs font-semibold text-slate-800 leading-tight">
                                    {auth.user?.name}
                                </p>
                                <p className="text-[11px] text-slate-400 leading-tight">
                                    {auth.user?.position?.name || userRoles[0]}
                                </p>
                            </div>
                            <div className="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-600 text-xs">
                                {auth.user?.name?.substring(0, 1) || 'U'}
                            </div>
                        </div>
                    </header>

                    {/* Page Content Body */}
                    <main className="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
};
