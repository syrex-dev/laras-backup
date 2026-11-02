import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { Badge } from '@/Components/Badge';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Send, Plus, Search, Filter, RotateCcw, Eye, Edit3, Printer, FileText, ChevronLeft, ChevronRight, CornerDownRight, BookOpen
} from 'lucide-react';

interface SuratKeluarRow {
    id: number;
    nomor_surat: string | null;
    nomor_agenda: string | null;
    perihal: string;
    tujuan: string;
    tanggal_surat: string | null;
    status: string;
    sifat: string;
    reply_to_surat_masuk_id?: number | null;
    org_unit?: { id: number; name: string; code: string } | null;
    penandatangan?: { id: number; name: string; position?: { name: string } | null } | null;
    pembuat?: { id: number; name: string } | null;
    template?: { id: number; nama: string; jenis: string } | null;
}

interface IndexProps {
    suratKeluar: {
        data: SuratKeluarRow[];
        current_page: number;
        last_page: number;
        prev_page_url?: string | null;
        next_page_url?: string | null;
        total: number;
        from: number;
        to: number;
    };
    klasifikasiList: Array<{ id: number; kode_klasifikasi: string; nama: string }>;
    unitsList: Array<{ id: number; code: string; name: string }>;
    filters: {
        search?: string;
        status?: string;
        sifat?: string;
        org_unit_id?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function Index({ suratKeluar, klasifikasiList, unitsList, filters }: IndexProps) {
    const { term } = useTerminology();

    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [sifat, setSifat] = useState(filters.sifat || '');
    const [unitId, setUnitId] = useState(filters.org_unit_id || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const handleFilter = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        router.get(route('surat-keluar.index'), {
            search: search || undefined,
            status: status || undefined,
            sifat: sifat || undefined,
            org_unit_id: unitId || undefined,
            start_date: startDate || undefined,
            end_date: endDate || undefined,
        }, { preserveState: true, replace: true });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setSifat('');
        setUnitId('');
        setStartDate('');
        setEndDate('');
        router.get(route('surat-keluar.index'), {}, { preserveState: true, replace: true });
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'bernomor':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Bernomor Resmi</span>;
            case 'disetujui':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Disetujui Penuh</span>;
            case 'diperiksa':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Sedang Diperiksa</span>;
            case 'revisi':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Perlu Revisi</span>;
            case 'ditolak':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">Ditolak</span>;
            case 'dibatalkan':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">Dibatalkan</span>;
            default:
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Draf Awal</span>;
        }
    };

    const getSifatBadge = (sf: string) => {
        switch (sf) {
            case 'rahasia':
                return <Badge variant="danger">Rahasia</Badge>;
            case 'segera':
                return <Badge variant="warning">Segera</Badge>;
            case 'penting':
                return <Badge variant="info">Penting</Badge>;
            default:
                return <Badge variant="muted">Biasa</Badge>;
        }
    };

    return (
        <AppLayout title="Naskah Dinas Keluar">
            <Head title="Naskah Dinas Keluar" />

            <div className="space-y-6">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Send className="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            Naskah Dinas Keluar
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Penyusunan draf, alur pemeriksaan bertingkat, dan penerbitan nomor resmi naskah dinas {term('nama_instansi', 'instansi')}.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/surat-keluar/templates">
                            <Button variant="outline" className="flex items-center gap-2">
                                <BookOpen className="w-4 h-4" />
                                Template Naskah
                            </Button>
                        </Link>
                        <Link href={route('surat-keluar.create')}>
                            <Button variant="primary" className="flex items-center gap-2 shadow-sm">
                                <Plus className="w-4 h-4" />
                                Buat Naskah Baru
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Filter Box */}
                <Card className="p-5">
                    <form onSubmit={handleFilter} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                            {/* Search */}
                            <div className="xl:col-span-2">
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Pencarian</label>
                                <div className="relative">
                                    <FormInput
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Cari perihal, nomor, tujuan..."
                                        className="pl-8 text-sm"
                                    />
                                    <Search className="w-4 h-4 text-gray-400 absolute left-2.5 top-3" />
                                </div>
                            </div>

                            {/* Status */}
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status Naskah</label>
                                <Select
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="text-sm"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="draf">Draf</option>
                                    <option value="diperiksa">Sedang Diperiksa</option>
                                    <option value="revisi">Perlu Revisi</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="bernomor">Bernomor Resmi</option>
                                    <option value="ditolak">Ditolak</option>
                                    <option value="dibatalkan">Dibatalkan</option>
                                </Select>
                            </div>

                            {/* Sifat */}
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Sifat Naskah</label>
                                <Select
                                    value={sifat}
                                    onChange={(e) => setSifat(e.target.value)}
                                    className="text-sm"
                                >
                                    <option value="">Semua Sifat</option>
                                    <option value="biasa">Biasa</option>
                                    <option value="penting">Penting</option>
                                    <option value="segera">Segera</option>
                                    <option value="rahasia">Rahasia</option>
                                </Select>
                            </div>

                            {/* Unit Pembuat */}
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Unit Pembuat</label>
                                <Select
                                    value={unitId}
                                    onChange={(e) => setUnitId(e.target.value)}
                                    className="text-sm"
                                >
                                    <option value="">Semua Unit</option>
                                    {unitsList.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name} ({u.code})
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            {/* Tanggal */}
                            <div className="grid grid-cols-2 gap-1.5 xl:col-span-1">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Dari</label>
                                    <FormInput
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                        className="text-xs px-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Sampai</label>
                                    <FormInput
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        className="text-xs px-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <Button type="button" variant="outline" size="sm" onClick={handleReset} className="flex items-center gap-1.5">
                                <RotateCcw className="w-3.5 h-3.5" />
                                Reset
                            </Button>
                            <Button type="submit" variant="primary" size="sm" className="flex items-center gap-1.5">
                                <Filter className="w-3.5 h-3.5" />
                                Terapkan Filter
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Table Data */}
                <Card className="overflow-hidden">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell className="w-12">No</TableHeaderCell>
                                <TableHeaderCell>Nomor & Tanggal</TableHeaderCell>
                                <TableHeaderCell>Perihal & Tujuan</TableHeaderCell>
                                <TableHeaderCell>Unit & Penandatangan</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell>Sifat</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {suratKeluar.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <div className="flex flex-col items-center justify-center space-y-2">
                                            <Send className="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                            <p className="font-medium">Belum ada naskah dinas keluar yang cocok.</p>
                                            <Link href={route('surat-keluar.create')}>
                                                <Button variant="outline" size="sm" className="mt-2">Buat Draf Pertama</Button>
                                            </Link>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                suratKeluar.data.map((row, idx) => (
                                    <TableRow key={row.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                        <TableCell className="text-gray-500 font-mono text-xs">
                                            {suratKeluar.from + idx}
                                        </TableCell>

                                        <TableCell>
                                            <div className="font-semibold text-gray-900 dark:text-white font-mono text-xs">
                                                {row.nomor_surat || <span className="text-gray-400 font-sans italic font-normal">Belum Bernomor</span>}
                                            </div>
                                            <div className="text-xs text-gray-500 mt-0.5">
                                                {row.tanggal_surat || 'Tanggal Belum Ditetapkan'}
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            <div className="font-semibold text-gray-900 dark:text-white line-clamp-2">
                                                {row.perihal}
                                            </div>
                                            <div className="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                                <span>Yth. {row.tujuan}</span>
                                                {row.reply_to_surat_masuk_id && (
                                                    <span className="inline-flex items-center gap-0.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium ml-1">
                                                        <CornerDownRight className="w-3 h-3" /> Balasan Disposisi
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            <div className="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                {row.org_unit?.name || 'Unit Pusat'}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                TTD: {row.penandatangan?.name || '-'}
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            {getStatusBadge(row.status)}
                                        </TableCell>

                                        <TableCell>
                                            {getSifatBadge(row.sifat)}
                                        </TableCell>

                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Link href={route('surat-keluar.show', row.id)}>
                                                    <Button variant="ghost" size="sm" title="Lihat Detail & Lini Masa">
                                                        <Eye className="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                                    </Button>
                                                </Link>

                                                {['draf', 'revisi'].includes(row.status) && (
                                                    <Link href={route('surat-keluar.edit', row.id)}>
                                                        <Button variant="ghost" size="sm" title="Edit Draf">
                                                            <Edit3 className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                                        </Button>
                                                    </Link>
                                                )}

                                                {row.status === 'bernomor' && (
                                                    <a href={route('surat-keluar.pdf', row.id)} target="_blank" rel="noopener noreferrer">
                                                        <Button variant="ghost" size="sm" title="Unduh PDF Resmi">
                                                            <Printer className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                                        </Button>
                                                    </a>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    {suratKeluar.total > 0 && (
                        <div className="flex flex-col sm:flex-row justify-between items-center gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 text-xs text-gray-500">
                            <div>
                                Menampilkan <span className="font-semibold text-gray-700 dark:text-gray-200">{suratKeluar.from}</span> sampai{' '}
                                <span className="font-semibold text-gray-700 dark:text-gray-200">{suratKeluar.to}</span> dari{' '}
                                <span className="font-semibold text-gray-700 dark:text-gray-200">{suratKeluar.total}</span> naskah
                            </div>

                            <div className="flex items-center gap-2">
                                {suratKeluar.prev_page_url ? (
                                    <Link href={suratKeluar.prev_page_url} preserveState>
                                        <Button variant="outline" size="sm" className="flex items-center gap-1">
                                            <ChevronLeft className="w-3.5 h-3.5" /> Sebelumnya
                                        </Button>
                                    </Link>
                                ) : (
                                    <Button variant="outline" size="sm" disabled className="opacity-50">
                                        <ChevronLeft className="w-3.5 h-3.5" /> Sebelumnya
                                    </Button>
                                )}

                                <span className="px-2 font-medium">
                                    Halaman {suratKeluar.current_page} dari {suratKeluar.last_page}
                                </span>

                                {suratKeluar.next_page_url ? (
                                    <Link href={suratKeluar.next_page_url} preserveState>
                                        <Button variant="outline" size="sm" className="flex items-center gap-1">
                                            Berikutnya <ChevronRight className="w-3.5 h-3.5" />
                                        </Button>
                                    </Link>
                                ) : (
                                    <Button variant="outline" size="sm" disabled className="opacity-50">
                                        Berikutnya <ChevronRight className="w-3.5 h-3.5" />
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
