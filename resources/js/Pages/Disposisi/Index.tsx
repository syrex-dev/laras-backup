import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { Badge } from '@/Components/Badge';
import { Modal } from '@/Components/Modal';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Inbox, Send, Search, Filter, Clock, AlertTriangle, CheckCircle2,
    ArrowRight, ChevronRight, UserCheck, ShieldAlert, Sparkles, FileText,
    BookOpen, ChevronDown, ChevronUp, Paperclip, Download
} from 'lucide-react';

export interface QuickPreset {
    id: string;
    position_id: number;
    position_name: string;
    unit_name: string;
    user_id?: number | null;
    user_name: string;
    template_id: number;
    template_nama: string;
    template_kode: string;
    prioritas: string;
}

interface RecipientItem {
    id: number;
    disposisi_id: number;
    status: string;
    dibaca_pada?: string | null;
    selesai_pada?: string | null;
    catatan_tindak_lanjut?: string | null;
    is_delegated: boolean;
    delegated_user_id?: number | null;
    is_overdue?: boolean;
    is_due_soon?: boolean;
    display_name?: string;
    delegation_label?: string;
    recipient_position?: { id: number; name: string; org_unit?: { name: string } } | null;
    recipient_user?: { id: number; name: string; email: string } | null;
    delegated_user?: { id: number; name: string; email: string } | null;
    disposisi: {
        id: number;
        instruksi: string;
        catatan?: string | null;
        prioritas: string;
        tenggat_waktu?: string | null;
        pemberi?: { id: number; name: string } | null;
        pemberi_position?: { id: number; name: string } | null;
        surat_masuk?: {
            id: number;
            nomor_agenda: string;
            nomor_surat: string;
            perihal: string;
            ringkasan?: string | null;
            pengirim: string;
            tanggal_surat: string;
            sifat?: string | null;
            attachments?: Array<{
                id: number;
                original_filename: string;
                file_size: number;
                mime_type: string;
            }>;
        } | null;
    };
}

interface IndexProps {
    items: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        tab: string;
        status: string;
        prioritas: string;
        search: string;
    };
    stats: {
        unread_count: number;
        overdue_count: number;
    };
    instructionTemplates: Array<{ id: number; kode: string; nama: string }>;
    subordinatePositions: Array<{ id: number; name: string; org_unit?: { name: string } }>;
    quickPresets?: QuickPreset[];
}

export default function Index({
    items,
    filters,
    stats,
    instructionTemplates,
    subordinatePositions,
    quickPresets = [],
}: IndexProps) {
    const { term } = useTerminology();
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [quickModalOpen, setQuickModalOpen] = useState(false);
    const [selectedSuratForQuick, setSelectedSuratForQuick] = useState<any>(null);
    const [showFullLetter, setShowFullLetter] = useState(false);

    // Form Quick Disposisi Mobile (<= 3 Ketukan)
    const { data: quickData, setData: setQuickData, post: postQuick, processing: quickProcessing, reset: resetQuick } = useForm({
        surat_masuk_id: null as number | null,
        instruction_template_id: null as number | null,
        instruksi: '',
        prioritas: 'biasa',
        recipients: [] as Array<{ position_id?: number; user_id?: number }>,
    });

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/disposisi', { ...filters, search: searchQuery }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/disposisi', { ...filters, [key]: value }, { preserveState: true });
    };

    // Alur Disposisi Cepat Mobile:
    // Ketukan 1: Buka Modal Cepat
    const openQuickDisposisiModal = (surat: any) => {
        setSelectedSuratForQuick(surat);
        setShowFullLetter(false); // default tertutup agar ringkas

        // Pilih preset default dari quickPresets pertama jika ada, atau fallback
        const defaultPreset = quickPresets[0];
        if (defaultPreset) {
            setQuickData({
                surat_masuk_id: surat.id,
                instruction_template_id: defaultPreset.template_id,
                instruksi: defaultPreset.template_nama,
                prioritas: defaultPreset.prioritas || 'biasa',
                recipients: [{ position_id: defaultPreset.position_id, user_id: defaultPreset.user_id || undefined }],
            });
        } else {
            setQuickData({
                surat_masuk_id: surat.id,
                instruction_template_id: instructionTemplates[0]?.id || null,
                instruksi: instructionTemplates[0]?.nama || 'Tindak lanjuti segera',
                prioritas: 'biasa',
                recipients: subordinatePositions[0] ? [{ position_id: subordinatePositions[0].id }] : [],
            });
        }
        setQuickModalOpen(true);
    };

    // Ketukan 2: Pilih preset kombinasi cepat (Penerima + Instruksi dari Database) dalam 1 tap
    const selectQuickPresetItem = (preset: QuickPreset) => {
        setQuickData({
            ...quickData,
            instruction_template_id: preset.template_id,
            instruksi: preset.template_nama,
            prioritas: preset.prioritas || 'biasa',
            recipients: [{ position_id: preset.position_id, user_id: preset.user_id || undefined }],
        });
    };

    // Ketukan 3: Submit / Kirim
    const handleQuickSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postQuick('/disposisi', {
            onSuccess: () => {
                setQuickModalOpen(false);
                resetQuick();
            },
        });
    };

    const getPriorityBadge = (prioritas: string) => {
        switch (prioritas) {
            case 'sangat_segera':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Sangat Segera</span>;
            case 'segera':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Segera</span>;
            default:
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Biasa</span>;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'belum_dibaca':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 animate-pulse">Belum Dibaca</span>;
            case 'dibaca':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300">Dibaca</span>;
            case 'diproses':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">Diproses</span>;
            case 'diteruskan':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Diteruskan</span>;
            case 'selesai':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>;
            case 'dikembalikan':
            case 'ditolak':
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Dikembalikan</span>;
            default:
                return <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">{status}</span>;
        }
    };

    return (
        <AppLayout title="Kotak Masuk Disposisi">
            <Head title="Kotak Masuk Disposisi" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Kotak Masuk Disposisi
                        </h1>
                        <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            Kelola instruksi tindak lanjut surat dinas dan alur persuratan berjenjang.
                        </p>
                    </div>

                    {/* Tab Switcher: Kotak Masuk vs Terkirim */}
                    <div className="inline-flex p-1 bg-slate-200 dark:bg-slate-800 rounded-lg self-start">
                        <button
                            onClick={() => handleFilterChange('tab', 'inbox')}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                filters.tab === 'inbox'
                                    ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            }`}
                        >
                            <Inbox className="w-4 h-4" />
                            <span>Kotak Masuk</span>
                            {stats.unread_count > 0 && (
                                <span className="px-1.5 py-0.5 text-xs bg-emerald-600 text-white rounded-full">
                                    {stats.unread_count}
                                </span>
                            )}
                        </button>
                        <button
                            onClick={() => handleFilterChange('tab', 'sent')}
                            className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                filters.tab === 'sent'
                                    ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            }`}
                        >
                            <Send className="w-4 h-4" />
                            <span>Terkirim</span>
                        </button>
                    </div>
                </div>

                {/* Filter & Search Bar */}
                <Card className="p-4">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                        <div className="md:col-span-5 relative">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari perihal, pengirim, atau agenda..."
                                className="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                        </div>

                        <div className="md:col-span-3">
                            <select
                                value={filters.status}
                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                <option value="semua">Semua Status</option>
                                <option value="belum_dibaca">Belum Dibaca</option>
                                <option value="dibaca">Dibaca</option>
                                <option value="diproses">Sedang Diproses</option>
                                <option value="diteruskan">Diteruskan</option>
                                <option value="selesai">Selesai</option>
                                <option value="terlambat">⚠️ Terlambat (Overdue)</option>
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <select
                                value={filters.prioritas}
                                onChange={(e) => handleFilterChange('prioritas', e.target.value)}
                                className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                <option value="semua">Semua Prioritas</option>
                                <option value="biasa">Biasa</option>
                                <option value="segera">Segera</option>
                                <option value="sangat_segera">Sangat Segera</option>
                            </select>
                        </div>

                        <div className="md:col-span-2 flex items-center gap-2">
                            <Button type="submit" variant="primary" className="w-full justify-center text-sm py-2">
                                Filter
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* List Items / Cards */}
                <div className="space-y-3">
                    {items.data.length === 0 ? (
                        <Card className="p-12 text-center">
                            <Inbox className="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
                            <h3 className="text-base font-semibold text-slate-800 dark:text-slate-200">
                                Tidak ada disposisi
                            </h3>
                            <p className="text-sm text-slate-500 mt-1">
                                Belum ada disposisi naskah yang sesuai dengan kriteria filter saat ini.
                            </p>
                        </Card>
                    ) : (
                        items.data.map((item: any) => {
                            const isInbox = filters.tab === 'inbox';
                            const recipientId = isInbox ? item.id : item.recipients?.[0]?.id;
                            const surat = isInbox ? item.disposisi?.surat_masuk : item.surat_masuk;
                            const disposisi = isInbox ? item.disposisi : item;
                            const isOverdue = isInbox ? item.is_overdue : false;
                            const isDueSoon = isInbox ? item.is_due_soon : false;

                            return (
                                <Card
                                    key={item.id}
                                    className={`p-4 transition-all hover:border-emerald-500 dark:hover:border-emerald-500 ${
                                        isOverdue ? 'border-rose-400 bg-rose-50/20 dark:bg-rose-950/10' : ''
                                    }`}
                                >
                                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div className="space-y-2 flex-1">
                                            {/* Baris Atas: Badge Prioritas, Status, Tenggat & Plt */}
                                            <div className="flex flex-wrap items-center gap-2">
                                                {getPriorityBadge(disposisi?.prioritas)}
                                                {getStatusBadge(item.status)}

                                                {/* Penanda Plt */}
                                                {item.is_delegated && (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                        <UserCheck className="w-3 h-3" />
                                                        {item.delegation_label || 'Atas Nama Jabatan (Plt)'}
                                                    </span>
                                                )}

                                                {/* Penanda Tenggat Waktu / Terlambat */}
                                                {isOverdue && (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-rose-600 text-white animate-bounce">
                                                        <AlertTriangle className="w-3 h-3" />
                                                        Terlambat!
                                                    </span>
                                                )}
                                                {isDueSoon && !isOverdue && (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-500 text-white">
                                                        <Clock className="w-3 h-3" />
                                                        Tenggat Mendekat
                                                    </span>
                                                )}

                                                {disposisi?.tenggat_waktu && (
                                                    <span className="text-xs text-slate-500 flex items-center gap-1">
                                                        <Clock className="w-3 h-3" />
                                                        Tenggat: {disposisi.tenggat_waktu}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Perihal & Agenda */}
                                            <div>
                                                <h2 className="text-base font-semibold text-slate-900 dark:text-white">
                                                    {surat?.perihal || 'Naskah Dinas'}
                                                </h2>
                                                <p className="text-xs text-slate-500 mt-0.5 flex flex-wrap gap-2">
                                                    <span>Agenda: <strong className="text-slate-700 dark:text-slate-300">{surat?.nomor_agenda}</strong></span>
                                                    <span>•</span>
                                                    <span>Dari: {surat?.pengirim}</span>
                                                    {isInbox && disposisi?.pemberi && (
                                                        <>
                                                            <span>•</span>
                                                            <span>Pemberi: <strong>{disposisi.pemberi.name}</strong></span>
                                                        </>
                                                    )}
                                                </p>
                                            </div>

                                            {/* Instruksi Ringkas */}
                                            <div className="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-md text-xs text-slate-700 dark:text-slate-300">
                                                <span className="font-semibold text-slate-900 dark:text-white">Instruksi:</span>{' '}
                                                {disposisi?.instruksi}
                                                {disposisi?.catatan && (
                                                    <p className="text-slate-500 mt-1 italic">
                                                        Catatan: "{disposisi.catatan}"
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Aksi & Tombol Mobile Friendly */}
                                        <div className="flex items-center gap-2 self-end md:self-center">
                                            {/* Tombol Disposisi Cepat (<= 3 Ketukan) */}
                                            {surat && (
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    onClick={() => openQuickDisposisiModal(surat)}
                                                    className="text-xs py-1.5 px-3 flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300"
                                                >
                                                    <Sparkles className="w-3.5 h-3.5" />
                                                    <span>Disposisi Cepat</span>
                                                </Button>
                                            )}

                                            <Link
                                                href={`/disposisi/recipient/${recipientId}`}
                                                className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 dark:bg-emerald-600 dark:hover:bg-emerald-700 transition-colors"
                                            >
                                                <span>Detail & Tindak Lanjut</span>
                                                <ArrowRight className="w-3.5 h-3.5" />
                                            </Link>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })
                    )}
                </div>

                {/* Pagination */}
                {items.last_page > 1 && (
                    <div className="flex justify-center gap-1 mt-6">
                        {items.links.map((link: any, idx: number) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                /* Label paginasi aman dari Laravel (misal: &laquo; Sebelumnya / Berikutnya &raquo;) dan bukan input pengguna */
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 text-xs rounded-md border ${
                                    link.active
                                        ? 'bg-emerald-600 text-white border-emerald-600 font-semibold'
                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-50'
                                } ${!link.url ? 'opacity-50 pointer-events-none' : ''}`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Disposisi Cepat Mobile (Alur <= 3 Ketukan) */}
            <Modal
                isOpen={quickModalOpen}
                onClose={() => setQuickModalOpen(false)}
                title="⚡ Disposisi Cepat (1-Touch Preset)"
            >
                {selectedSuratForQuick && (
                    <form onSubmit={handleQuickSubmit} className="space-y-4">
                        {/* Ringkasan Surat Singkat */}
                        <div className="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg text-xs space-y-1 border border-slate-200 dark:border-slate-700">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <p className="font-semibold text-slate-900 dark:text-white line-clamp-2">
                                        {selectedSuratForQuick.perihal}
                                    </p>
                                    <p className="text-slate-500 mt-0.5">
                                        Agenda: <span className="font-mono text-slate-700 dark:text-slate-300">{selectedSuratForQuick.nomor_agenda}</span> • Pengirim: <span className="font-medium text-slate-700 dark:text-slate-300">{selectedSuratForQuick.pengirim}</span>
                                    </p>
                                </div>
                            </div>

                            {/* Tombol Baca Isi Surat di Tengah Alur (Mobile In-Flow Reading) */}
                            <div className="pt-2 border-t border-slate-200 dark:border-slate-700/60">
                                <button
                                    type="button"
                                    onClick={() => setShowFullLetter(!showFullLetter)}
                                    className="w-full flex items-center justify-between px-2.5 py-1.5 rounded-md bg-white dark:bg-slate-700/60 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium hover:bg-slate-100 transition-colors"
                                >
                                    <span className="flex items-center gap-1.5">
                                        <BookOpen className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                        <span>{showFullLetter ? 'Tutup Pratinjau Naskah' : '📖 Baca Isi Naskah Lengkap & Lampiran'}</span>
                                    </span>
                                    {showFullLetter ? (
                                        <ChevronUp className="w-3.5 h-3.5 text-slate-400" />
                                    ) : (
                                        <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                                    )}
                                </button>

                                {/* Panel Membaca Naskah Lengkap */}
                                {showFullLetter && (
                                    <div className="mt-2 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-xs space-y-2 max-h-56 overflow-y-auto">
                                        <div className="grid grid-cols-2 gap-2 text-[11px] pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div>
                                                <span className="text-slate-400 block">Nomor Surat:</span>
                                                <span className="font-medium text-slate-700 dark:text-slate-200">{selectedSuratForQuick.nomor_surat || '-'}</span>
                                            </div>
                                            <div>
                                                <span className="text-slate-400 block">Tanggal Surat:</span>
                                                <span className="font-medium text-slate-700 dark:text-slate-200">{selectedSuratForQuick.tanggal_surat || '-'}</span>
                                            </div>
                                            <div>
                                                <span className="text-slate-400 block">Sifat Naskah:</span>
                                                <span className="font-medium capitalize text-slate-700 dark:text-slate-200">{selectedSuratForQuick.sifat || 'Biasa'}</span>
                                            </div>
                                        </div>

                                        <div>
                                            <span className="text-slate-400 block text-[11px] mb-1 font-semibold">Ringkasan / Isi Naskah:</span>
                                            <p className="text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/80 p-2 rounded border border-slate-200 dark:border-slate-700/50">
                                                {selectedSuratForQuick.ringkasan || selectedSuratForQuick.perihal}
                                            </p>
                                        </div>

                                        {/* Berkas Lampiran */}
                                        {selectedSuratForQuick.attachments && selectedSuratForQuick.attachments.length > 0 && (
                                            <div>
                                                <span className="text-slate-400 block text-[11px] mb-1 font-semibold">Berkas Lampiran ({selectedSuratForQuick.attachments.length}):</span>
                                                <div className="space-y-1">
                                                    {selectedSuratForQuick.attachments.map((att: any) => (
                                                        <a
                                                            key={att.id}
                                                            href={`/surat-masuk/attachments/${att.id}/download`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="flex items-center justify-between p-1.5 rounded bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 hover:bg-emerald-100"
                                                        >
                                                            <span className="flex items-center gap-1.5 truncate max-w-[200px]">
                                                                <Paperclip className="w-3 h-3 flex-shrink-0" />
                                                                <span className="truncate">{att.original_filename}</span>
                                                            </span>
                                                            <span className="flex items-center gap-1 text-[10px] font-medium">
                                                                <Download className="w-3 h-3" />
                                                                Unduh
                                                            </span>
                                                        </a>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Preset Pilihan Cepat (Ketukan 2: 1 Sentuhan Memilih Penerima + Instruksi dari Database) */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                ⚡ Pilih Preset Rekomendasi (1 Sentuhan):
                            </label>

                            <div className="grid grid-cols-1 gap-2 max-h-52 overflow-y-auto pr-1">
                                {quickPresets.length > 0 ? (
                                    quickPresets.map((preset) => {
                                        const isSelected =
                                            quickData.recipients[0]?.position_id === preset.position_id &&
                                            quickData.instruction_template_id === preset.template_id;

                                        return (
                                            <button
                                                key={preset.id}
                                                type="button"
                                                onClick={() => selectQuickPresetItem(preset)}
                                                className={`text-left p-3 rounded-lg border text-xs transition-all ${
                                                    isSelected
                                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 font-semibold ring-1 ring-emerald-500 shadow-sm'
                                                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-400 bg-white dark:bg-slate-800/60'
                                                }`}
                                            >
                                                <div className="flex justify-between items-center">
                                                    <span className="text-slate-900 dark:text-white font-medium">
                                                        Kepada: <strong>{preset.position_name}</strong>
                                                        <span className="text-slate-500 dark:text-slate-400 font-normal ml-1">
                                                            ({preset.unit_name})
                                                        </span>
                                                    </span>
                                                    {isSelected && <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />}
                                                </div>
                                                <p className="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">
                                                    Pejabat: {preset.user_name}
                                                </p>
                                                <div className="mt-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-[11px]">
                                                    <span className="text-emerald-700 dark:text-emerald-400 font-medium">
                                                        Instruksi: "{preset.template_nama}"
                                                    </span>
                                                    <span className="text-slate-400 uppercase text-[10px] font-mono">
                                                        {preset.template_kode}
                                                    </span>
                                                </div>
                                            </button>
                                        );
                                    })
                                ) : (
                                    // Fallback jika belum ada presets
                                    subordinatePositions.slice(0, 4).map((pos) => {
                                        const tpl = instructionTemplates[0] || { id: 1, nama: 'Tindak lanjuti segera', kode: 'TL' };
                                        const isSelected = quickData.recipients[0]?.position_id === pos.id;

                                        return (
                                            <button
                                                key={pos.id}
                                                type="button"
                                                onClick={() => setQuickData({
                                                    ...quickData,
                                                    instruction_template_id: tpl.id,
                                                    instruksi: tpl.nama,
                                                    recipients: [{ position_id: pos.id }],
                                                })}
                                                className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                    isSelected
                                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 font-semibold'
                                                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-400'
                                                }`}
                                            >
                                                <div className="flex justify-between items-center">
                                                    <span>Kepada: <strong>{pos.name}</strong></span>
                                                    {isSelected && <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />}
                                                </div>
                                                <p className="text-slate-500 text-[11px] mt-0.5">
                                                    Instruksi: "{tpl.nama}"
                                                </p>
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>

                        {/* Ketukan 3: Tombol Kirim */}
                        <div className="pt-2 flex justify-end gap-2 border-t border-slate-200 dark:border-slate-700">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setQuickModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="primary"
                                disabled={quickProcessing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-1.5"
                            >
                                {quickProcessing ? 'Mengirim...' : '🚀 Kirim Disposisi Sekarang'}
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>
        </AppLayout>
    );
}
