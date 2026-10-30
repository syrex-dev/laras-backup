import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    QrCode, MapPin, CheckCircle2, Clock, AlertTriangle, ArrowLeft,
    FileText, User, UserCheck, ShieldCheck, CornerDownRight
} from 'lucide-react';

interface TrackingProps {
    surat: {
        id: number;
        nomor_agenda: string;
        nomor_surat: string;
        perihal: string;
        pengirim: string;
        tanggal_surat: string;
        tanggal_diterima: string;
        status: string;
        sifat: string;
        qr_token?: string;
    };
    currentPosition: {
        location: string;
        holder: string;
        status_label: string;
        is_overdue: boolean;
        deadline?: string | null;
    };
    disposisiChain: any[];
}

export default function Tracking({ surat, currentPosition, disposisiChain }: TrackingProps) {
    const { term } = useTerminology();

    return (
        <AppLayout title="Pelacakan Naskah & Riwayat Disposisi">
            <Head title={`Lacak Surat #${surat.nomor_agenda} - ${surat.perihal}`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header & Back */}
                <div className="flex items-center gap-3">
                    <Link
                        href={`/surat-masuk/${surat.id}`}
                        className="p-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <div className="flex items-center gap-2">
                            <QrCode className="w-5 h-5 text-emerald-600" />
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">
                                Pelacakan QR Naskah Dinas
                            </h1>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Status verifikasi fisik dan lini masa riwayat disposisi resmi.
                        </p>
                    </div>
                </div>

                {/* Kartu Status & Posisi Surat Saat Ini */}
                <Card className="p-6 border-emerald-500/40 bg-gradient-to-br from-emerald-50/50 via-white to-slate-50 dark:from-emerald-950/20 dark:via-slate-900 dark:to-slate-900">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-2">
                            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <ShieldCheck className="w-3.5 h-3.5" />
                                Terverifikasi Sistem E-Office
                            </span>
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                {surat.perihal}
                            </h2>
                            <p className="text-xs text-slate-600 dark:text-slate-400">
                                No. Agenda: <strong>{surat.nomor_agenda}</strong> • No. Surat: {surat.nomor_surat}
                            </p>
                        </div>

                        {/* Posisi Terkini */}
                        <div className="p-4 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm space-y-1 sm:text-right">
                            <div className="flex items-center sm:justify-end gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                <MapPin className="w-4 h-4 animate-bounce" />
                                <span>Posisi Fisik / Meja Saat Ini:</span>
                            </div>
                            <p className="text-sm font-bold text-slate-900 dark:text-white">
                                {currentPosition.location}
                            </p>
                            <p className="text-xs text-slate-500">
                                Penanggung Jawab: <strong>{currentPosition.holder}</strong>
                            </p>
                            {currentPosition.is_overdue && (
                                <span className="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-600 text-white animate-pulse">
                                    ⚠️ Melewati Batas Tenggat
                                </span>
                            )}
                        </div>
                    </div>
                </Card>

                {/* Riwayat Disposisi Berjenjang */}
                <Card className="p-6 space-y-5">
                    <div className="border-b border-slate-200 dark:border-slate-700 pb-3">
                        <h3 className="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Clock className="w-5 h-5 text-emerald-600" />
                            <span>Riwayat Rantai Disposisi Berjenjang</span>
                        </h3>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Kronologi instruksi pimpinan dan pelaksana dari meja ke meja.
                        </p>
                    </div>

                    {disposisiChain.length === 0 ? (
                        <div className="p-8 text-center text-slate-500 text-xs">
                            Surat masuk belum pernah didisposisikan.
                        </div>
                    ) : (
                        <div className="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
                            {disposisiChain.map((disp, idx) => (
                                <div key={disp.id} className="relative">
                                    {/* Lingkaran Urutan */}
                                    <div className="absolute -left-6 top-1.5 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 bg-emerald-600 flex items-center justify-center text-[10px] text-white font-bold">
                                        {idx + 1}
                                    </div>

                                    <div className="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2.5">
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <h4 className="text-xs font-bold text-slate-900 dark:text-white">
                                                    {disp.pemberi?.name}
                                                </h4>
                                                <span className="text-[11px] text-slate-500 block">
                                                    {disp.pemberi_position?.name || 'Pemberi Disposisi'}
                                                </span>
                                            </div>
                                            <span className="text-[11px] text-slate-400">
                                                {disp.created_at?.slice(0, 10)}
                                            </span>
                                        </div>

                                        <p className="text-xs text-slate-800 dark:text-slate-200 font-medium bg-white dark:bg-slate-800 p-2.5 rounded border border-slate-100 dark:border-slate-700">
                                            "{disp.instruksi}"
                                        </p>

                                        {/* Penerima */}
                                        <div className="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-1.5">
                                            <span className="text-[11px] font-semibold text-slate-500 block">
                                                Penerima Instruksi:
                                            </span>
                                            {disp.recipients?.map((rec: any) => (
                                                <div
                                                    key={rec.id}
                                                    className="flex items-center justify-between text-xs p-2 rounded bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800"
                                                >
                                                    <div>
                                                        <span className="font-semibold text-slate-800 dark:text-slate-200">
                                                            {rec.is_delegated
                                                                ? `${rec.delegated_user?.name || 'Plt'} (Plt. ${rec.recipient_position?.name})`
                                                                : rec.recipient_user?.name || rec.recipient_position?.name}
                                                        </span>
                                                        {rec.catatan_tindak_lanjut && (
                                                            <p className="text-[11px] text-slate-500 italic mt-0.5">
                                                                Hasil: "{rec.catatan_tindak_lanjut}"
                                                            </p>
                                                        )}
                                                    </div>
                                                    <span className={`px-2 py-0.5 text-[10px] rounded uppercase font-bold ${
                                                        rec.status === 'selesai'
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                            : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                                    }`}>
                                                        {rec.status}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
