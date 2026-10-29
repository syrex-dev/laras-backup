import React, { useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { Badge } from '@/Components/Badge';
import { Modal } from '@/Components/Modal';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    ArrowLeft, Printer, Download, Eye, Ban, Edit2, FileText, CheckCircle2,
    Clock, ShieldAlert, History, AlertTriangle, X
} from 'lucide-react';

interface AttachmentItem {
    id: number;
    file_name: string;
    file_path: string;
    mime_type: string;
    file_size: number;
    formatted_size: string;
    created_at: string;
    uploader?: { id: number; name: string } | null;
}

interface AuditLogItem {
    id: number;
    action: string;
    description: string;
    created_at: string;
    properties?: Record<string, any> | null;
    causer?: { id: number; name: string; email: string } | null;
}

interface SuratMasukDetail {
    id: number;
    nomor_agenda: string;
    nomor_surat: string;
    tanggal_surat: string;
    tanggal_diterima: string;
    pengirim: string;
    perihal: string;
    sifat: string;
    jenis: string;
    status: string;
    keterangan?: string | null;
    alasan_batal?: string | null;
    dibatalkan_pada?: string | null;
    qr_token?: string | null;
    klasifikasi?: { id: number; kode: string; nama: string } | null;
    unit_tujuan?: { id: number; name: string; code: string } | null;
    user?: { id: number; name: string } | null;
    dibatalkan_oleh?: { id: number; name: string } | null;
    attachments: AttachmentItem[];
}

interface ShowProps {
    surat: SuratMasukDetail;
    auditLogs: AuditLogItem[];
}

export default function Show({ surat, auditLogs }: ShowProps) {
    const { term } = useTerminology();
    const [isCancelModalOpen, setIsCancelModalOpen] = useState(false);
    const [previewAttachment, setPreviewAttachment] = useState<AttachmentItem | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        alasan_batal: '',
    });

    const handleCancelSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/surat-masuk/${surat.id}/cancel`, {
            onSuccess: () => {
                setIsCancelModalOpen(false);
                reset();
            },
        });
    };

    const isDibatalkan = surat.status === 'dibatalkan';

    const getSifatBadgeVariant = (sifat: string) => {
        switch (sifat) {
            case 'rahasia':
                return 'danger';
            case 'segera':
            case 'penting':
                return 'warning';
            default:
                return 'info';
        }
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
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

    return (
        <AppLayout title={`Surat Masuk - ${surat.nomor_agenda}`}>
            <div className="max-w-5xl mx-auto space-y-6">
                {/* Header & Navigasi */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href="/surat-masuk">
                            <Button variant="secondary" size="sm" icon={<ArrowLeft className="w-4 h-4" />}>
                                Kembali ke Daftar
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-slate-900 font-mono">
                                    {surat.nomor_agenda}
                                </h1>
                                <Badge variant={getStatusBadgeVariant(surat.status)}>
                                    {surat.status.toUpperCase()}
                                </Badge>
                                <Badge variant={getSifatBadgeVariant(surat.sifat)}>
                                    {surat.sifat.toUpperCase()}
                                </Badge>
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Diterima pada {new Date(surat.tanggal_diterima).toLocaleDateString('id-ID', { dateStyle: 'long' })} oleh {surat.user?.name || 'Petugas TU'}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center flex-wrap gap-2">
                        <a
                            href={`/surat-masuk/${surat.id}/print-disposisi`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="secondary" size="sm" icon={<Printer className="w-4 h-4 text-emerald-600" />}>
                                Cetak Disposisi
                            </Button>
                        </a>

                        <a
                            href={`/surat-masuk/${surat.id}/print-bukti`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="secondary" size="sm" icon={<Printer className="w-4 h-4 text-slate-600" />}>
                                Bukti Terima
                            </Button>
                        </a>

                        {!isDibatalkan && (
                            <>
                                <Link href={`/surat-masuk/${surat.id}/edit`}>
                                    <Button variant="secondary" size="sm" icon={<Edit2 className="w-4 h-4" />}>
                                        Ubah
                                    </Button>
                                </Link>

                                <Button
                                    variant="danger"
                                    size="sm"
                                    icon={<Ban className="w-4 h-4" />}
                                    onClick={() => setIsCancelModalOpen(true)}
                                >
                                    Batalkan Surat
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Banner Jika Surat Dibatalkan */}
                {isDibatalkan && (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3">
                        <ShieldAlert className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div className="text-sm">
                            <h3 className="font-bold text-rose-800">
                                Registrasi Surat Masuk Telah Dibatalkan
                            </h3>
                            <p className="text-xs text-rose-700 mt-1">
                                <strong>Alasan Pembatalan:</strong> {surat.alasan_batal}
                            </p>
                            <p className="text-[11px] text-rose-500 mt-1">
                                Dibatalkan oleh <strong>{surat.dibatalkan_oleh?.name || 'Petugas'}</strong> pada{' '}
                                {surat.dibatalkan_pada ? new Date(surat.dibatalkan_pada).toLocaleString('id-ID') : '-'}
                            </p>
                        </div>
                    </div>
                )}

                {/* Detail Informasi Naskah */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <Card title="Rincian Naskah Masuk">
                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-xs">
                                <div>
                                    <dt className="text-slate-500 font-medium">Asal Surat / Pengirim</dt>
                                    <dd className="text-slate-900 font-semibold text-sm mt-0.5">{surat.pengirim}</dd>
                                </div>

                                <div>
                                    <dt className="text-slate-500 font-medium">Nomor Surat Asal</dt>
                                    <dd className="text-slate-900 font-mono font-medium mt-0.5">{surat.nomor_surat}</dd>
                                </div>

                                <div>
                                    <dt className="text-slate-500 font-medium">Tanggal Surat</dt>
                                    <dd className="text-slate-900 font-medium mt-0.5">
                                        {new Date(surat.tanggal_surat).toLocaleDateString('id-ID', { dateStyle: 'long' })}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-slate-500 font-medium">Media Penerimaan</dt>
                                    <dd className="text-slate-900 font-medium mt-0.5 uppercase">{surat.jenis}</dd>
                                </div>

                                <div className="sm:col-span-2">
                                    <dt className="text-slate-500 font-medium">Klasifikasi Arsip</dt>
                                    <dd className="text-slate-900 font-medium mt-0.5">
                                        {surat.klasifikasi ? `${surat.klasifikasi.kode} - ${surat.klasifikasi.nama}` : '-'}
                                    </dd>
                                </div>

                                <div className="sm:col-span-2">
                                    <dt className="text-slate-500 font-medium">{term('unit_level_1', 'Unit Kerja')} Tujuan</dt>
                                    <dd className="text-slate-900 font-medium mt-0.5">
                                        {surat.unit_tujuan ? `${surat.unit_tujuan.name} (${surat.unit_tujuan.code})` : 'Pimpinan Utama'}
                                    </dd>
                                </div>

                                <div className="sm:col-span-2 border-t border-slate-100 pt-3">
                                    <dt className="text-slate-500 font-medium">Perihal Surat</dt>
                                    <dd className="text-slate-800 text-sm mt-1 whitespace-pre-line leading-relaxed font-medium">
                                        {surat.perihal}
                                    </dd>
                                </div>

                                {surat.keterangan && (
                                    <div className="sm:col-span-2 border-t border-slate-100 pt-3">
                                        <dt className="text-slate-500 font-medium">Keterangan Tambahan</dt>
                                        <dd className="text-slate-600 mt-1 whitespace-pre-line">
                                            {surat.keterangan}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </Card>

                        {/* Daftar Berkas Lampiran Privat */}
                        <Card title={`Berkas Lampiran Privat (${surat.attachments.length})`}>
                            {surat.attachments.length === 0 ? (
                                <p className="text-xs text-slate-400 py-4 text-center">
                                    Tidak ada berkas lampiran yang diunggah untuk surat ini.
                                </p>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {surat.attachments.map((att) => (
                                        <div key={att.id} className="py-3 flex items-center justify-between gap-3 text-xs">
                                            <div className="flex items-center gap-3 overflow-hidden">
                                                <div className="w-8 h-8 rounded bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                                                    <FileText className="w-4 h-4" />
                                                </div>
                                                <div className="truncate">
                                                    <div className="font-semibold text-slate-800 truncate">{att.file_name}</div>
                                                    <div className="text-[11px] text-slate-400">
                                                        {(att.file_size / 1024).toFixed(1)} KB • Diunggah oleh {att.uploader?.name || 'Petugas'}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 shrink-0">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon={<Eye className="w-3.5 h-3.5" />}
                                                    onClick={() => setPreviewAttachment(att)}
                                                >
                                                    Pratinjau
                                                </Button>

                                                <a
                                                    href={`/surat-masuk/${surat.id}/attachments/${att.id}/download`}
                                                    download
                                                >
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        icon={<Download className="w-3.5 h-3.5" />}
                                                    >
                                                        Unduh
                                                    </Button>
                                                </a>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card>
                    </div>

                    {/* Sidebar: Riwayat Jejak Audit */}
                    <div className="space-y-6">
                        <Card title="Riwayat Jejak Audit (Append-Only)">
                            <div className="flow-root">
                                <ul className="-mb-8">
                                    {auditLogs.map((log, idx) => (
                                        <li key={log.id}>
                                            <div className="relative pb-6">
                                                {idx !== auditLogs.length - 1 && (
                                                    <span
                                                        className="absolute top-4 left-3 -ml-px h-full w-0.5 bg-slate-200"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                                <div className="relative flex space-x-3 text-xs">
                                                    <div>
                                                        <span className="h-6 w-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center ring-4 ring-white">
                                                            <History className="w-3 h-3" />
                                                        </span>
                                                    </div>
                                                    <div className="flex-1 pt-0.5">
                                                        <p className="font-semibold text-slate-800">
                                                            {log.action}
                                                        </p>
                                                        <p className="text-slate-600 text-[11px] mt-0.5">
                                                            {log.description}
                                                        </p>
                                                        <div className="flex items-center gap-2 text-[10px] text-slate-400 mt-1">
                                                            <span>{log.causer?.name || 'Sistem'}</span>
                                                            <span>•</span>
                                                            <span>{new Date(log.created_at).toLocaleString('id-ID')}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </Card>
                    </div>
                </div>

                {/* Modal Pratinjau Berkas Lampiran */}
                {previewAttachment && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden">
                            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-emerald-600" />
                                    <h3 className="font-bold text-slate-900 text-sm truncate">
                                        {previewAttachment.file_name}
                                    </h3>
                                </div>
                                <div className="flex items-center gap-2">
                                    <a
                                        href={`/surat-masuk/${surat.id}/attachments/${previewAttachment.id}/download`}
                                        className="text-xs text-emerald-600 hover:underline flex items-center gap-1 font-medium"
                                    >
                                        <Download className="w-3.5 h-3.5" /> Unduh
                                    </a>
                                    <button
                                        type="button"
                                        onClick={() => setPreviewAttachment(null)}
                                        className="p-1 rounded-lg text-slate-400 hover:text-slate-700"
                                    >
                                        <X className="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                            <div className="flex-1 bg-slate-100 overflow-auto flex items-center justify-center">
                                {previewAttachment.mime_type.startsWith('image/') ? (
                                    <img
                                        src={`/surat-masuk/${surat.id}/attachments/${previewAttachment.id}/preview`}
                                        alt={previewAttachment.file_name}
                                        className="max-w-full max-h-full object-contain p-4"
                                    />
                                ) : (
                                    <iframe
                                        src={`/surat-masuk/${surat.id}/attachments/${previewAttachment.id}/preview`}
                                        className="w-full h-full border-0"
                                        title={previewAttachment.file_name}
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal Pembatalan Surat */}
                <Modal
                    isOpen={isCancelModalOpen}
                    onClose={() => setIsCancelModalOpen(false)}
                    title="Batalkan Registrasi Surat Masuk"
                >
                    <form onSubmit={handleCancelSubmit} className="space-y-4">
                        <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2.5 text-xs text-amber-800">
                            <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
                            <div>
                                <span className="font-semibold">Perhatian:</span> Pembatalan akan mengubah status surat menjadi <strong>Dibatalkan</strong> dan mencatat alasan pembatalan ke jejak audit append-only yang tidak dapat dihapus.
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alasan Pembatalan <span className="text-rose-500">*</span>
                            </label>
                            <textarea
                                rows={4}
                                placeholder="Tuliskan alasan resmi pembatalan registrasi naskah (Contoh: Salah input nomor agenda, berkas ditarik oleh pengirim)..."
                                value={data.alasan_batal}
                                onChange={(e) => setData('alasan_batal', e.target.value)}
                                className="block w-full rounded-lg border border-slate-300 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                                required
                            />
                            {errors.alasan_batal && (
                                <p className="mt-1 text-xs text-rose-600 font-medium">{errors.alasan_batal}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <Button variant="secondary" onClick={() => setIsCancelModalOpen(false)} type="button">
                                Batal
                            </Button>
                            <Button variant="danger" type="submit" loading={processing} icon={<Ban className="w-4 h-4" />}>
                                Konfirmasi Pembatalan
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AppLayout>
    );
}
