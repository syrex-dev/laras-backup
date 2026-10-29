import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { Badge } from '@/Components/Badge';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Inbox, Plus, Search, Filter, RotateCcw, Eye, Paperclip, Printer, FileText, ChevronLeft, ChevronRight
} from 'lucide-react';

interface AttachmentItem {
    id: number;
    file_name: string;
}

interface SuratMasukRow {
    id: number;
    nomor_agenda: string;
    nomor_surat: string;
    pengirim: string;
    perihal: string;
    tanggal_surat: string;
    tanggal_diterima: string;
    sifat: string;
    jenis: string;
    status: string;
    unit_tujuan?: { id: number; name: string; code: string } | null;
    klasifikasi?: { id: number; kode: string; nama: string } | null;
    attachments?: AttachmentItem[];
}

interface IndexProps {
    suratMasuk: {
        data: SuratMasukRow[];
        current_page: number;
        last_page: number;
        prev_page_url?: string | null;
        next_page_url?: string | null;
        total: number;
        from: number;
        to: number;
    };
    klasifikasiList: Array<{ id: number; kode: string; nama: string }>;
    unitsList: Array<{ id: number; code: string; name: string }>;
    filters: {
        search?: string;
        status?: string;
        sifat?: string;
        unit_id?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function Index({ suratMasuk, klasifikasiList, unitsList, filters }: IndexProps) {
    const { term } = useTerminology();

    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'semua');
    const [sifat, setSifat] = useState(filters.sifat || 'semua');
    const [unitId, setUnitId] = useState(filters.unit_id || 'semua');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const applyFilters = (customFilters = {}) => {
        router.get(
            '/surat-masuk',
            {
                search: search || undefined,
                status: status !== 'semua' ? status : undefined,
                sifat: sifat !== 'semua' ? sifat : undefined,
                unit_id: unitId !== 'semua' ? unitId : undefined,
                start_date: startDate || undefined,
                end_date: endDate || undefined,
                ...customFilters,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const handleResetFilters = () => {
        setSearch('');
        setStatus('semua');
        setSifat('semua');
        setUnitId('semua');
        setStartDate('');
        setEndDate('');
        router.get('/surat-masuk');
    };

    const getSifatBadgeVariant = (s: string) => {
        switch (s) {
            case 'rahasia':
                return 'danger';
            case 'segera':
            case 'penting':
                return 'warning';
            default:
                return 'info';
        }
    };

    const getStatusBadgeVariant = (st: string) => {
        switch (st) {
            case 'dibatalkan':
                return 'danger';
            case 'selesai':
            case 'diarsipkan':
                return 'success';
            case 'didisposisi':
                return 'primary';
            default:
                return 'info';
        }
    };

    const statusOptions = [
        { value: 'semua', label: 'Semua Status' },
        { value: 'terdaftar', label: 'Terdaftar' },
        { value: 'didisposisi', label: 'Didisposisikan' },
        { value: 'diproses', label: 'Sedang Diproses' },
        { value: 'selesai', label: 'Selesai' },
        { value: 'diarsipkan', label: 'Diarsipkan' },
        { value: 'dibatalkan', label: 'Dibatalkan' },
    ];

    const sifatOptions = [
        { value: 'semua', label: 'Semua Sifat' },
        { value: 'biasa', label: 'Biasa' },
        { value: 'penting', label: 'Penting' },
        { value: 'segera', label: 'Segera' },
        { value: 'rahasia', label: 'Rahasia' },
    ];

    const unitOptions = [
        { value: 'semua', label: `Semua ${term('unit_level_1', 'Unit Kerja')}` },
        ...unitsList.map((u) => ({ value: String(u.id), label: `${u.name} (${u.code})` })),
    ];

    return (
        <AppLayout title="Buku Agenda Surat Masuk">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <Inbox className="w-6 h-6 text-emerald-600" />
                            Buku Agenda Surat Masuk Digital
                        </h1>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Pencatatan naskah dinas masuk, disposisi, dan pelacakan arsip aktif.
                        </p>
                    </div>

                    <Link href="/surat-masuk/create">
                        <Button variant="primary" icon={<Plus className="w-4 h-4" />}>
                            Registrasi Surat Masuk
                        </Button>
                    </Link>
                </div>

                {/* Filter & Search Bar */}
                <Card>
                    <form onSubmit={handleSearchSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div className="md:col-span-2">
                                <label className="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                    Cari Naskah (Perihal, Pengirim, Nomor)
                                </label>
                                <div className="relative">
                                    <input
                                        type="text"
                                        placeholder="Ketik kata kunci pencarian..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                                </div>
                            </div>

                            <div>
                                <Select
                                    label="Status Naskah"
                                    options={statusOptions}
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                />
                            </div>

                            <div>
                                <Select
                                    label="Sifat / Urgensi"
                                    options={sifatOptions}
                                    value={sifat}
                                    onChange={(e) => setSifat(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end pt-2 border-t border-slate-100">
                            <div>
                                <Select
                                    label={`${term('unit_level_1', 'Unit Kerja')} Tujuan`}
                                    options={unitOptions}
                                    value={unitId}
                                    onChange={(e) => setUnitId(e.target.value)}
                                />
                            </div>

                            <FormInput
                                label="Periode Tanggal Terima (Mulai)"
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                            />

                            <FormInput
                                label="Periode Tanggal Terima (Sampai)"
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                            />

                            <div className="flex items-center gap-2">
                                <Button variant="primary" type="submit" icon={<Filter className="w-4 h-4" />} className="flex-1">
                                    Terapkan Filter
                                </Button>
                                <Button variant="secondary" type="button" icon={<RotateCcw className="w-4 h-4" />} onClick={handleResetFilters}>
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </form>
                </Card>

                {/* Tabel Data Surat Masuk */}
                <Card>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Nomor Agenda</TableHeaderCell>
                                <TableHeaderCell>Pengirim & Nomor Asal</TableHeaderCell>
                                <TableHeaderCell>Perihal Surat</TableHeaderCell>
                                <TableHeaderCell>Tgl Diterima</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Lampiran</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {suratMasuk.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-12 text-slate-400">
                                        <Inbox className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                        Belum ada data surat masuk yang sesuai dengan kriteria filter.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                suratMasuk.data.map((surat) => (
                                    <TableRow key={surat.id}>
                                        <TableCell>
                                            <Link
                                                href={`/surat-masuk/${surat.id}`}
                                                className="font-mono font-bold text-emerald-700 hover:underline block"
                                            >
                                                {surat.nomor_agenda}
                                            </Link>
                                            <Badge variant={getSifatBadgeVariant(surat.sifat)}>
                                                {surat.sifat.toUpperCase()}
                                            </Badge>
                                        </TableCell>

                                        <TableCell>
                                            <div className="font-semibold text-slate-800 text-xs sm:text-sm">
                                                {surat.pengirim}
                                            </div>
                                            <div className="text-xs text-slate-400 font-mono">
                                                No: {surat.nomor_surat}
                                            </div>
                                        </TableCell>

                                        <TableCell className="max-w-xs">
                                            <div className="line-clamp-2 text-xs text-slate-700 font-medium">
                                                {surat.perihal}
                                            </div>
                                            {surat.unit_tujuan && (
                                                <div className="text-[11px] text-slate-400 mt-0.5">
                                                    Tujuan: {surat.unit_tujuan.name}
                                                </div>
                                            )}
                                        </TableCell>

                                        <TableCell className="text-xs text-slate-600 whitespace-nowrap">
                                            {new Date(surat.tanggal_diterima).toLocaleDateString('id-ID', { dateStyle: 'medium' })}
                                        </TableCell>

                                        <TableCell>
                                            <Badge variant={getStatusBadgeVariant(surat.status)}>
                                                {surat.status.toUpperCase()}
                                            </Badge>
                                        </TableCell>

                                        <TableCell>
                                            {surat.attachments && surat.attachments.length > 0 ? (
                                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                    <Paperclip className="w-3 h-3" />
                                                    {surat.attachments.length} berkas
                                                </span>
                                            ) : (
                                                <span className="text-xs text-slate-400">-</span>
                                            )}
                                        </TableCell>

                                        <TableCell className="text-right whitespace-nowrap">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <Link href={`/surat-masuk/${surat.id}`}>
                                                    <Button variant="secondary" size="sm" icon={<Eye className="w-3.5 h-3.5" />}>
                                                        Detail
                                                    </Button>
                                                </Link>
                                                <a
                                                    href={`/surat-masuk/${surat.id}/print-disposisi`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Button variant="secondary" size="sm" icon={<Printer className="w-3.5 h-3.5" />} title="Cetak Lembar Disposisi" />
                                                </a>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination Server-Side */}
                    {suratMasuk.total > 0 && (
                        <div className="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
                            <div>
                                Menampilkan <strong>{suratMasuk.from}</strong> - <strong>{suratMasuk.to}</strong> dari total <strong>{suratMasuk.total}</strong> naskah
                            </div>
                            <div className="flex items-center gap-2">
                                {suratMasuk.prev_page_url ? (
                                    <Link href={suratMasuk.prev_page_url} preserveState>
                                        <Button variant="secondary" size="sm" icon={<ChevronLeft className="w-4 h-4" />}>
                                            Sebelumnya
                                        </Button>
                                    </Link>
                                ) : (
                                    <Button variant="secondary" size="sm" disabled icon={<ChevronLeft className="w-4 h-4" />}>
                                        Sebelumnya
                                    </Button>
                                )}

                                <span className="font-semibold text-slate-700 px-2">
                                    Halaman {suratMasuk.current_page} dari {suratMasuk.last_page}
                                </span>

                                {suratMasuk.next_page_url ? (
                                    <Link href={suratMasuk.next_page_url} preserveState>
                                        <Button variant="secondary" size="sm" icon={<ChevronRight className="w-4 h-4" />}>
                                            Selanjutnya
                                        </Button>
                                    </Link>
                                ) : (
                                    <Button variant="secondary" size="sm" disabled icon={<ChevronRight className="w-4 h-4" />}>
                                        Selanjutnya
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
