import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { ShieldCheck, Clock, User, HardDrive, Terminal } from 'lucide-react';

interface LogItem {
    id: number;
    action?: string;
    tindakan?: string;
    description?: string;
    modul?: string;
    subject_type?: string;
    ip_address?: string;
    alamat_ip?: string;
    created_at: string;
    user?: {
        id: number;
        name?: string;
        nama?: string;
        email?: string;
    } | null;
}

interface LogsProps {
    logs: {
        data: LogItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function Logs({ logs }: LogsProps) {
    const list = logs?.data || [];

    return (
        <AppLayout title="Audit Trail Sistem">
            <Head title="Audit Logs" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-600 mb-1 tracking-wide uppercase">
                            <ShieldCheck className="w-4 h-4" />
                            <span>Integritas & Keamanan</span>
                        </div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                            Audit Trail & Log Aktivitas
                        </h1>
                        <p className="text-xs md:text-sm text-slate-500 mt-1">
                            Rekam jejak tidak dapat diubah (append-only) untuk setiap operasi naskah dinas, otentikasi, dan konfigurasi.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Badge variant="primary" dot>
                            {logs?.total || 0} Total Kejadian
                        </Badge>
                    </div>
                </div>

                {/* Table Card */}
                <Card className="overflow-hidden">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Waktu</TableHeaderCell>
                                <TableHeaderCell>Pengguna</TableHeaderCell>
                                <TableHeaderCell>Aksi</TableHeaderCell>
                                <TableHeaderCell>Keterangan / Modul</TableHeaderCell>
                                <TableHeaderCell>IP Address</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {list.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                                        <Terminal className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                        Belum ada riwayat aktivitas tercatat.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                list.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="whitespace-nowrap font-mono text-xs text-slate-500">
                                            <div className="flex items-center gap-1.5">
                                                <Clock className="w-3.5 h-3.5 text-slate-400" />
                                                <span>{new Date(item.created_at).toLocaleString('id-ID')}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm font-semibold text-slate-800">
                                                {item.user?.name || item.user?.nama || 'Sistem / Tamu'}
                                            </div>
                                            <div className="text-xs text-slate-400 truncate">
                                                {item.user?.email || '—'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="info">
                                                {item.action || item.tindakan || 'action'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-md">
                                            <div className="text-xs text-slate-700 truncate">
                                                {item.description || item.modul || item.subject_type || '—'}
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap font-mono text-xs text-slate-500">
                                            {item.ip_address || item.alamat_ip || '127.0.0.1'}
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
