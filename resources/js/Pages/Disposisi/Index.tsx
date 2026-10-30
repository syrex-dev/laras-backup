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
    ArrowRight, ChevronRight, UserCheck, ShieldAlert, Sparkles, FileText
} from 'lucide-react';

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
            pengirim: string;
            tanggal_surat: string;
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
}

export default function Index({
    items,
    filters,
    stats,
    instructionTemplates,
    subordinatePositions,
}: IndexProps) {
    const { term } = useTerminology();
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [quickModalOpen, setQuickModalOpen] = useState(false);
    const [selectedSuratForQuick, setSelectedSuratForQuick] = useState<any>(null);

    // Form Quick Disposisi Mobile (<= 3 Ketukan)
    const { data: quickData, setData: setQuickData, post: postQuick, processing: quickProcessing, reset: resetQuick } = useForm({
        surat_masuk_id: null as number | null,
        instruction_template_id: null as number | null,
        instruksi: '',
        recipients: [] as Array<{ position_id?: number; user_id?: number }>,
    });

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/disposisi', { ...filters, search: searchQuery }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/disposisi', { ...filters, [key]: value }, { preserveState: true });
    };

    // Alur 3 Ketukan Mobile:
    // Ketukan 1: Buka Modal Cepat
    const openQuickDisposisiModal = (surat: any) => {
        setSelectedSuratForQuick(surat);
        setQuickData({
            surat_masuk_id: surat.id,
            instruction_template_id: instructionTemplates[0]?.id || null,
            instruksi: instructionTemplates[0]?.nama || 'Tindak lanjuti segera',
            recipients: subordinatePositions[0] ? [{ position_id: subordinatePositions[0].id }] : [],
        });
        setQuickModalOpen(true);
    };

    // Ketukan 2: Pilih preset kombinasi cepat (Penerima + Instruksi) dalam 1 tap
    const selectQuickPreset = (positionId: number, templateId: number, templateName: string) => {
        setQuickData({
            ...quickData,
            instruction_template_id: templateId,
            instruksi: templateName,
            recipients: [{ position_id: positionId }],
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
                        <div className="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg text-xs space-y-1">
                            <p className="font-semibold text-slate-900 dark:text-white">
                                {selectedSuratForQuick.perihal}
                            </p>
                            <p className="text-slate-500">
                                Agenda: {selectedSuratForQuick.nomor_agenda} • Pengirim: {selectedSuratForQuick.pengirim}
                            </p>
                        </div>

                        {/* Preset Pilihan Cepat (Ketukan 2: 1 Sentuhan Memilih Penerima + Instruksi) */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Pilih Preset Instan (1 Ketukan):
                            </label>
                            <div className="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto">
                                {subordinatePositions.slice(0, 4).map((pos) => {
                                    const tpl = instructionTemplates[0] || { id: 1, nama: 'Tindak lanjuti segera' };
                                    const isSelected = quickData.recipients[0]?.position_id === pos.id;

                                    return (
                                        <button
                                            key={pos.id}
                                            type="button"
                                            onClick={() => selectQuickPreset(pos.id, tpl.id, tpl.nama)}
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
                                })}
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
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
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
