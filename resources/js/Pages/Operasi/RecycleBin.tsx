import React from 'react';
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { Button } from '@/Components/Button';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { Trash2, RotateCcw, AlertTriangle, Archive, FileText } from 'lucide-react';

interface TrashedItem {
    id: number;
    type: string;
    display_type: string;
    nomor_surat?: string | null;
    nomor_agenda?: string | null;
    perihal?: string | null;
    deleted_at?: string;
}

interface RecycleBinProps {
    trashedItems: TrashedItem[];
}

export default function RecycleBin({ trashedItems = [] }: RecycleBinProps) {
    const handleRestore = (type: string, id: number) => {
        if (confirm('Kembalikan naskah ini ke daftar aktif?')) {
            router.post(`/admin/recycle-bin/${type}/${id}/restore`);
        }
    };

    const handleForceDelete = (type: string, id: number) => {
        if (confirm('PERINGATAN: Naskah akan dihapus permanen dari basis data dan tidak dapat dipulihkan! Lanjutkan?')) {
            router.delete(`/admin/recycle-bin/${type}/${id}/force-delete`);
        }
    };

    return (
        <AppLayout title="Tempat Sampah Naskah">
            <Head title="Recycle Bin" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-rose-600 mb-1 tracking-wide uppercase">
                            <Trash2 className="w-4 h-4" />
                            <span>Pemulihan Data</span>
                        </div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                            Tempat Sampah (Recycle Bin)
                        </h1>
                        <p className="text-xs md:text-sm text-slate-500 mt-1">
                            Daftar naskah surat yang dihapus sementara (soft-delete). Anda dapat memulihkan naskah atau menghapusnya secara permanen.
                        </p>
                    </div>

                    <div>
                        <Badge variant="warning" dot>
                            {trashedItems.length} Naskah Terhapus
                        </Badge>
                    </div>
                </div>

                {/* Table Card */}
                <Card className="overflow-hidden">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Tipe Dokumen</TableHeaderCell>
                                <TableHeaderCell>Nomor Naskah / Agenda</TableHeaderCell>
                                <TableHeaderCell>Perihal</TableHeaderCell>
                                <TableHeaderCell>Waktu Dihapus</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {trashedItems.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                                        <Trash2 className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                        Tempat sampah kosong. Tidak ada data naskah yang terhapus.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                trashedItems.map((item) => (
                                    <TableRow key={`${item.type}-${item.id}`}>
                                        <TableCell>
                                            <Badge variant={item.type === 'surat-masuk' ? 'primary' : 'info'}>
                                                {item.display_type || item.type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-mono text-xs font-semibold text-slate-700">
                                            {item.nomor_surat || item.nomor_agenda || '—'}
                                        </TableCell>
                                        <TableCell className="max-w-xs">
                                            <div className="text-sm font-medium text-slate-800 truncate">
                                                {item.perihal || '—'}
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-xs text-slate-500">
                                            {item.deleted_at ? new Date(item.deleted_at).toLocaleString('id-ID') : '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleRestore(item.type, item.id)}
                                                    className="gap-1 text-xs"
                                                >
                                                    <RotateCcw className="w-3.5 h-3.5 text-emerald-600" />
                                                    <span>Pulihkan</span>
                                                </Button>
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    onClick={() => handleForceDelete(item.type, item.id)}
                                                    className="gap-1 text-xs"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                    <span>Hapus Permanen</span>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </Card>
            </div>
        </AppLayout>
    );
}
