import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { Button } from '@/Components/Button';
import {
    Mail,
    Send,
    FileCheck2,
    Archive,
    Plus,
    ArrowRight,
    Clock,
    FileText,
    ChevronRight,
    Sparkles,
    BarChart3,
    TrendingUp,
} from 'lucide-react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line, Bar } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

interface DashboardStats {
    surat_masuk: number;
    surat_keluar: number;
    disposisi: number;
    klasifikasi: number;
}

interface MonthlyVolumeData {
    labels: string[];
    surat_masuk: number[];
    surat_keluar: number[];
}

interface LetterItem {
    id: number;
    nomor_agenda?: string | null;
    nomor_surat?: string | null;
    perihal: string;
    asal_surat?: string | null;
    tujuan?: string | null;
    status: string;
    tanggal_surat?: string | null;
    tanggal_diterima?: string | null;
    created_at?: string;
    klasifikasi?: {
        id: number;
        kode_klasifikasi: string;
        nama: string;
    } | null;
}

interface DisposisiItem {
    id: number;
    status: string;
    catatan?: string | null;
    instruksi?: string | null;
    created_at?: string;
    surat_masuk?: {
        id: number;
        nomor_agenda?: string | null;
        nomor_surat?: string | null;
        perihal: string;
        asal_surat?: string | null;
    } | null;
}

interface DashboardProps {
    stats: DashboardStats;
    recentSuratMasuk: LetterItem[];
    recentSuratKeluar: LetterItem[];
    pendingDisposisi: DisposisiItem[];
    monthlyVolume?: MonthlyVolumeData;
}

export default function Dashboard({
    stats,
    recentSuratMasuk = [],
    recentSuratKeluar = [],
    pendingDisposisi = [],
    monthlyVolume = { labels: [], surat_masuk: [], surat_keluar: [] },
}: DashboardProps) {
    const [chartType, setChartType] = useState<'line' | 'bar'>('line');

    const statCards = [
        {
            title: 'Surat Masuk',
            value: stats.surat_masuk,
            desc: 'Total naskah masuk terdaftar',
            icon: Mail,
            color: 'emerald',
            href: '/surat-masuk',
            badge: 'Tercatat',
        },
        {
            title: 'Surat Keluar',
            value: stats.surat_keluar,
            desc: 'Total naskah keluar terproses',
            icon: Send,
            color: 'teal',
            href: '/surat-keluar',
            badge: 'Resmi',
        },
        {
            title: 'Disposisi Aktif',
            value: stats.disposisi,
            desc: 'Instruksi vertikal berjenjang',
            icon: FileCheck2,
            color: 'amber',
            href: '/disposisi',
            badge: 'Berjalan',
        },
        {
            title: 'Klasifikasi Naskah',
            value: stats.klasifikasi,
            desc: 'Kategori pola kearsipan',
            icon: Archive,
            color: 'sky',
            href: '/klasifikasi-surat',
            badge: 'Katalog',
        },
    ];

    const totalVolume =
        (monthlyVolume?.surat_masuk?.reduce((a, b) => a + b, 0) || 0) +
        (monthlyVolume?.surat_keluar?.reduce((a, b) => a + b, 0) || 0);

    const chartData = {
        labels: monthlyVolume.labels,
        datasets: [
            {
                label: 'Surat Masuk',
                data: monthlyVolume.surat_masuk,
                borderColor: '#10b981',
                backgroundColor: chartType === 'line' ? 'rgba(16, 185, 129, 0.12)' : '#10b981',
                fill: chartType === 'line',
                tension: 0.35,
                borderRadius: chartType === 'bar' ? 6 : 0,
            },
            {
                label: 'Surat Keluar',
                data: monthlyVolume.surat_keluar,
                borderColor: '#0284c7',
                backgroundColor: chartType === 'line' ? 'rgba(2, 132, 199, 0.12)' : '#0284c7',
                fill: chartType === 'line',
                tension: 0.35,
                borderRadius: chartType === 'bar' ? 6 : 0,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: {
                    boxWidth: 12,
                    font: { size: 12, family: 'sans-serif' },
                },
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 12 },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: { size: 11 },
                },
                grid: {
                    color: '#f1f5f9',
                },
            },
            x: {
                ticks: {
                    font: { size: 11 },
                },
                grid: {
                    display: false,
                },
            },
        },
    };

    const formatStatusBadge = (status: string) => {
        switch (status) {
            case 'disetujui':
            case 'selesai':
                return <Badge variant="success">Disetujui</Badge>;
            case 'diperiksa':
            case 'diproses':
                return <Badge variant="warning">Diperiksa</Badge>;
            case 'revisi':
                return <Badge variant="danger">Revisi</Badge>;
            case 'ditolak':
                return <Badge variant="danger">Ditolak</Badge>;
            case 'draf':
            case 'draft':
                return <Badge variant="muted">Draf</Badge>;
            default:
                return <Badge variant="info">{status}</Badge>;
        }
    };

    return (
        <AppLayout title="Dashboard Utama">
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Header & Quick Action Row */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-600 mb-1 tracking-wide uppercase">
                            <Sparkles className="w-4 h-4" />
                            <span>Ikhtisar Sistem Tata Naskah</span>
                        </div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                            Selamat Datang di Portal Persuratan
                        </h1>
                        <p className="text-xs md:text-sm text-slate-500 mt-1">
                            Kelola naskah dinas, alur disposisi, dan persetujuan bertingkat dengan aman dan terverifikasi.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5 flex-wrap">
                        <Link href="/surat-masuk/create">
                            <Button variant="outline" size="sm" className="gap-1.5 shadow-xs">
                                <Plus className="w-4 h-4" />
                                <span>Catat Surat Masuk</span>
                            </Button>
                        </Link>
                        <Link href="/surat-keluar/create">
                            <Button variant="primary" size="sm" className="gap-1.5 shadow-xs">
                                <Plus className="w-4 h-4" />
                                <span>Buat Surat Keluar</span>
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* 4 Metric Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {statCards.map((c) => {
                        const Icon = c.icon;
                        return (
                            <Link key={c.title} href={c.href} className="group block">
                                <Card className="p-5 hover:border-slate-300 hover:shadow-md transition-all duration-200">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            {c.title}
                                        </span>
                                        <div className="p-2 rounded-xl bg-slate-100 text-slate-700 group-hover:bg-emerald-50 group-hover:text-emerald-600 transition-colors">
                                            <Icon className="w-5 h-5" />
                                        </div>
                                    </div>
                                    <div className="mt-4 flex items-baseline justify-between">
                                        <div className="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">
                                            {c.value}
                                        </div>
                                        <Badge variant="muted">{c.badge}</Badge>
                                    </div>
                                    <p className="text-xs text-slate-400 mt-2 truncate">{c.desc}</p>
                                </Card>
                            </Link>
                        );
                    })}
                </div>

                {/* Volume Persuratan 6 Bulan Terakhir Chart Card */}
                <Card
                    title={
                        <div className="flex items-center gap-2">
                            <BarChart3 className="w-5 h-5 text-emerald-600" />
                            <span>Volume Persuratan (6 Bulan Terakhir)</span>
                        </div>
                    }
                    subtitle="Statistik aliran naskah masuk dan naskah dinas keluar yang diproses sistem"
                    action={
                        <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl">
                            <button
                                type="button"
                                onClick={() => setChartType('line')}
                                className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all ${
                                    chartType === 'line'
                                        ? 'bg-white text-emerald-700 shadow-xs'
                                        : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                Garis
                            </button>
                            <button
                                type="button"
                                onClick={() => setChartType('bar')}
                                className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all ${
                                    chartType === 'bar'
                                        ? 'bg-white text-emerald-700 shadow-xs'
                                        : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                Batang
                            </button>
                        </div>
                    }
                >
                    {totalVolume === 0 && (
                        <div className="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
                            <div className="flex items-center gap-2">
                                <TrendingUp className="w-4 h-4 text-emerald-600" />
                                <span>Belum ada riwayat lalu lintas surat 6 bulan terakhir. Grafik akan otomatis terisi saat naskah dinas didaftarkan.</span>
                            </div>
                            <Badge variant="muted">Empty State</Badge>
                        </div>
                    )}

                    <div className="h-72 w-full">
                        {chartType === 'line' ? (
                            <Line data={chartData} options={chartOptions} />
                        ) : (
                            <Bar data={chartData} options={chartOptions} />
                        )}
                    </div>
                </Card>

                {/* Main Content Grid: Recent Letters & Pending Dispositions */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left 2 Cols: Recent Incoming & Outgoing Letters */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Surat Masuk Terbaru */}
                        <Card
                            title="Surat Masuk Terbaru"
                            subtitle="Daftar naskah masuk yang baru diregistrasi"
                            action={
                                <Link
                                    href="/surat-masuk"
                                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                                >
                                    <span>Lihat Semua</span>
                                    <ChevronRight className="w-3.5 h-3.5" />
                                </Link>
                            }
                        >
                            {recentSuratMasuk.length === 0 ? (
                                <div className="py-8 text-center text-slate-400 text-sm">
                                    <Mail className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                    Belum ada catatan surat masuk terbaru.
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {recentSuratMasuk.map((letter) => (
                                        <div
                                            key={letter.id}
                                            className="py-3.5 flex items-start justify-between gap-4 first:pt-0 last:pb-0"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span className="font-mono text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                                        {letter.nomor_agenda || 'No Agenda'}
                                                    </span>
                                                    {letter.klasifikasi && (
                                                        <span className="text-[11px] text-slate-400">
                                                            {letter.klasifikasi.kode_klasifikasi} - {letter.klasifikasi.nama}
                                                        </span>
                                                    )}
                                                </div>
                                                <h4 className="text-sm font-semibold text-slate-800 line-clamp-1">
                                                    {letter.perihal}
                                                </h4>
                                                <p className="text-xs text-slate-500 mt-0.5 truncate">
                                                    Pengirim: {letter.asal_surat || '-'}
                                                </p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <div className="text-xs text-slate-400 mb-1">
                                                    {letter.tanggal_surat || letter.tanggal_diterima || '-'}
                                                </div>
                                                {formatStatusBadge(letter.status)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card>

                        {/* Surat Keluar Terbaru */}
                        <Card
                            title="Surat Keluar Terbaru"
                            subtitle="Daftar naskah dinas keluar yang sedang atau telah diproses"
                            action={
                                <Link
                                    href="/surat-keluar"
                                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                                >
                                    <span>Lihat Semua</span>
                                    <ChevronRight className="w-3.5 h-3.5" />
                                </Link>
                            }
                        >
                            {recentSuratKeluar.length === 0 ? (
                                <div className="py-8 text-center text-slate-400 text-sm">
                                    <Send className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                    Belum ada catatan surat keluar terbaru.
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {recentSuratKeluar.map((letter) => (
                                        <div
                                            key={letter.id}
                                            className="py-3.5 flex items-start justify-between gap-4 first:pt-0 last:pb-0"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span className="font-mono text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                                        {letter.nomor_surat || 'DRAF'}
                                                    </span>
                                                    {letter.klasifikasi && (
                                                        <span className="text-[11px] text-slate-400">
                                                            {letter.klasifikasi.kode_klasifikasi}
                                                        </span>
                                                    )}
                                                </div>
                                                <h4 className="text-sm font-semibold text-slate-800 line-clamp-1">
                                                    {letter.perihal}
                                                </h4>
                                                <p className="text-xs text-slate-500 mt-0.5 truncate">
                                                    Tujuan: {letter.tujuan || '-'}
                                                </p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <div className="text-xs text-slate-400 mb-1">
                                                    {letter.tanggal_surat || '-'}
                                                </div>
                                                {formatStatusBadge(letter.status)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card>
                    </div>

                    {/* Right Col: Pending Tasks / Disposisi */}
                    <div className="space-y-6">
                        <Card
                            title="Tugas & Disposisi"
                            subtitle="Instruksi yang memerlukan tindak lanjut Anda"
                            action={
                                <Link
                                    href="/disposisi"
                                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                                >
                                    <span>Kotak Masuk</span>
                                    <ChevronRight className="w-3.5 h-3.5" />
                                </Link>
                            }
                        >
                            {pendingDisposisi.length === 0 ? (
                                <div className="py-10 text-center text-slate-400 text-sm">
                                    <FileCheck2 className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                    Tidak ada instruksi disposisi yang tertunda.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {pendingDisposisi.map((task) => (
                                        <div
                                            key={task.id}
                                            className="p-3.5 rounded-xl border border-amber-200/60 bg-amber-50/40 hover:bg-amber-50/70 transition-colors"
                                        >
                                            <div className="flex items-center justify-between text-xs font-semibold text-amber-800 mb-1">
                                                <span>Instruksi #{task.id}</span>
                                                <Badge variant="warning">Perlu Tindak Lanjut</Badge>
                                            </div>
                                            <p className="text-xs font-medium text-slate-800 line-clamp-2">
                                                {task.instruksi || task.catatan || 'Segera tindak lanjuti disposisi ini'}
                                            </p>
                                            {task.surat_masuk && (
                                                <p className="text-[11px] text-slate-500 mt-2 truncate">
                                                    Ref: {task.surat_masuk.nomor_surat || task.surat_masuk.nomor_agenda} — {task.surat_masuk.perihal}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card>

                        {/* Quick Help / Info Card */}
                        <div className="p-5 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-md">
                            <h4 className="text-sm font-bold flex items-center gap-2">
                                <FileText className="w-4 h-4 text-emerald-400" />
                                <span>Alur Persuratan Resmi</span>
                            </h4>
                            <p className="text-xs text-slate-300 mt-2 leading-relaxed">
                                Pastikan naskah keluar diverifikasi berjenjang oleh pemeriksa sebelum ditandatangani dan diterbitkan nomor resminya secara otomatis.
                            </p>
                            <Link
                                href="/surat-keluar"
                                className="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors"
                            >
                                <span>Buka Manajemen Surat Keluar</span>
                                <ArrowRight className="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
