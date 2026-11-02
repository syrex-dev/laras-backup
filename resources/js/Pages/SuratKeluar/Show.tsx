import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { Badge } from '@/Components/Badge';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Send, ArrowLeft, CheckCircle2, XCircle, RotateCcw, Printer, Edit3, Trash2,
    Clock, UserCheck, ShieldAlert, History, Paperclip, ChevronRight, CornerDownRight, AlertTriangle
} from 'lucide-react';

interface ApprovalStep {
    id: number;
    step_order: number;
    role_type: string;
    status: string;
    catatan: string | null;
    acted_at: string | null;
    user?: { id: number; name: string } | null;
    position?: { id: number; name: string } | null;
    delegated_user?: { id: number; name: string } | null;
}

interface VersionItem {
    id: number;
    version_number: number;
    perihal: string;
    isi_surat: string;
    tujuan: string;
    catatan_perubahan: string | null;
    created_at: string;
    user?: { id: number; name: string } | null;
}

interface SuratKeluarDetail {
    id: number;
    nomor_surat: string | null;
    nomor_agenda: string | null;
    perihal: string;
    tujuan: string;
    isi_surat: string | null;
    tanggal_surat: string | null;
    status: string;
    sifat: string;
    current_approval_step: number;
    alasan_batal?: string | null;
    alasan_tolak?: string | null;
    catatan_revisi?: string | null;
    metadata?: Record<string, any> | null;
    template?: { id: number; nama: string; jenis: string } | null;
    klasifikasi?: { id: number; kode_klasifikasi: string; nama: string } | null;
    org_unit?: { id: number; name: string; code: string } | null;
    pembuat?: { id: number; name: string; position?: { name: string } | null } | null;
    penandatangan?: { id: number; name: string; position?: { name: string } | null } | null;
    surat_masuk?: { id: number; nomor_surat: string; perihal: string } | null;
    disposisi_recipient?: { id: number; status: string; disposisi?: { id: number; surat_masuk_id: number } | null } | null;
    approvals: ApprovalStep[];
    versions: VersionItem[];
    attachments: Array<{ id: number; file_name: string; file_path: string; file_size: number }>;
}

interface ShowProps {
    suratKeluar: SuratKeluarDetail;
    isCurrentReviewer: boolean;
    currentApproval?: ApprovalStep | null;
    canApprove: boolean;
    canEdit: boolean;
    canCancel: boolean;
}

export default function Show({
    suratKeluar,
    isCurrentReviewer,
    currentApproval,
    canApprove,
    canEdit,
    canCancel,
}: ShowProps) {
    const { term } = useTerminology();

    // State Modal Aksi Mobile & Desktop
    const [actionModal, setActionModal] = useState<'approve' | 'revise' | 'reject' | 'cancel' | 'resolve' | 'version' | null>(null);
    const [actionNote, setActionNote] = useState('');
    const [selectedVersion, setSelectedVersion] = useState<VersionItem | null>(null);

    const approveForm = useForm({ catatan: '', auto_allocate_nomor: true });
    const reviseForm = useForm({ catatan: '' });
    const rejectForm = useForm({ alasan: '' });
    const cancelForm = useForm({ alasan_batal: '' });
    const resolveForm = useForm({ catatan: '' });

    const handleApprove = () => {
        approveForm.setData('catatan', actionNote);
        approveForm.patch(`/surat-keluar/${suratKeluar.id}/approve`, {
            onSuccess: () => setActionModal(null),
        });
    };

    const handleRevise = () => {
        reviseForm.setData('catatan', actionNote);
        reviseForm.patch(`/surat-keluar/${suratKeluar.id}/revise`, {
            onSuccess: () => setActionModal(null),
        });
    };

    const handleReject = () => {
        rejectForm.setData('alasan', actionNote);
        rejectForm.patch(`/surat-keluar/${suratKeluar.id}/reject`, {
            onSuccess: () => setActionModal(null),
        });
    };

    const handleCancel = () => {
        cancelForm.setData('alasan_batal', actionNote);
        cancelForm.post(`/surat-keluar/${suratKeluar.id}/cancel`, {
            onSuccess: () => setActionModal(null),
        });
    };

    const handleResolveDisposisi = () => {
        resolveForm.setData('catatan', actionNote);
        resolveForm.post(`/surat-keluar/${suratKeluar.id}/resolve-disposisi`, {
            onSuccess: () => setActionModal(null),
        });
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'bernomor':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Bernomor Resmi</span>;
            case 'disetujui':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Disetujui Penuh</span>;
            case 'diperiksa':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Sedang Diperiksa</span>;
            case 'revisi':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Perlu Revisi</span>;
            case 'ditolak':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">Ditolak</span>;
            case 'dibatalkan':
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">Dibatalkan</span>;
            default:
                return <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Draf Awal</span>;
        }
    };

    return (
        <AppLayout title={`Naskah Keluar: ${suratKeluar.perihal}`}>
            <Head title={`Naskah Keluar: ${suratKeluar.perihal}`} />

            <div className="space-y-6 max-w-6xl mx-auto pb-24 md:pb-8">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div className="flex items-center gap-3">
                        <Link href="/surat-keluar">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-gray-900 dark:text-white line-clamp-1">
                                    {suratKeluar.perihal}
                                </h1>
                                {getStatusBadge(suratKeluar.status)}
                            </div>
                            <div className="flex items-center gap-3 text-xs text-gray-500 mt-1 font-mono">
                                <span>Nomor: {suratKeluar.nomor_surat || '(Belum Bernomor)'}</span>
                                <span>&bull;</span>
                                <span>Tanggal: {suratKeluar.tanggal_surat || '-'}</span>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {canEdit && (
                            <Link href={`/surat-keluar/${suratKeluar.id}/edit`}>
                                <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                                    <Edit3 className="w-3.5 h-3.5" /> Edit Draf
                                </Button>
                            </Link>
                        )}

                        {['bernomor', 'disetujui'].includes(suratKeluar.status) && (
                            <a href={`/surat-keluar/${suratKeluar.id}/pdf`} target="_blank" rel="noopener noreferrer">
                                <Button variant="primary" size="sm" className="flex items-center gap-1.5">
                                    <Printer className="w-3.5 h-3.5" /> Unduh PDF Resmi
                                </Button>
                            </a>
                        )}

                        {canCancel && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 flex items-center gap-1.5"
                                onClick={() => {
                                    setActionNote('');
                                    setActionModal('cancel');
                                }}
                            >
                                <ShieldAlert className="w-3.5 h-3.5" /> Batalkan Naskah
                            </Button>
                        )}
                    </div>
                </div>

                {/* Banner Tautan Balasan Disposisi jika ada */}
                {suratKeluar.surat_masuk && (
                    <div className="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div className="flex items-start gap-3">
                            <CornerDownRight className="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" />
                            <div className="text-sm">
                                <span className="font-semibold text-indigo-950 dark:text-indigo-200">
                                    Surat Balasan untuk Surat Masuk:
                                </span>{' '}
                                <span className="font-mono">{suratKeluar.surat_masuk.nomor_surat}</span> ({suratKeluar.surat_masuk.perihal})
                            </div>
                        </div>

                        {suratKeluar.status === 'bernomor' && suratKeluar.disposisi_recipient && suratKeluar.disposisi_recipient.status !== 'selesai' && (
                            <Button
                                variant="primary"
                                size="sm"
                                className="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-xs"
                                onClick={() => {
                                    setActionNote('Tindak lanjut selesai melalui penerbitan surat balasan resmi.');
                                    setActionModal('resolve');
                                }}
                            >
                                Selesaikan Tindak Lanjut Disposisi
                            </Button>
                        )}
                    </div>
                )}

                {/* Grid Konten Utama: 2 Kolom (Badan Surat & Timeline Pemeriksaan) */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Kolom Kiri (2/3): Tampilan Pratinjau Naskah & Dokumen */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Lembar Naskah Dinas */}
                        <Card className="p-8 shadow-sm space-y-6 bg-white dark:bg-gray-800">
                            {/* Kop Surat Mini Pratinjau */}
                            <div className="text-center border-b-2 border-double border-gray-900 dark:border-gray-200 pb-4">
                                <h2 className="text-base font-bold uppercase tracking-wide">
                                    {term('nama_instansi', 'POLITEKNIK CONTOH INDONESIA')}
                                </h2>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Naskah Dinas Elektronik Resmi &bull; Unit: {suratKeluar.org_unit?.name || 'Pusat'}
                                </p>
                            </div>

                            {/* Metadata Header Surat */}
                            <div className="grid grid-cols-2 text-xs gap-y-1.5 border-b border-gray-100 dark:border-gray-700 pb-4">
                                <div><span className="text-gray-500">Nomor:</span> <strong className="font-mono">{suratKeluar.nomor_surat || '(Draf Tanpa Nomor)'}</strong></div>
                                <div className="text-right"><span className="text-gray-500">Tanggal:</span> <strong>{suratKeluar.tanggal_surat || '-'}</strong></div>
                                <div><span className="text-gray-500">Sifat:</span> <span className="capitalize">{suratKeluar.sifat}</span></div>
                                <div className="text-right"><span className="text-gray-500">Klasifikasi:</span> {suratKeluar.klasifikasi?.kode_klasifikasi || '-'}</div>
                                <div className="col-span-2 mt-2">
                                    <span className="text-gray-500">Tujuan:</span><br />
                                    <strong className="text-sm text-gray-900 dark:text-white">Yth. {suratKeluar.tujuan}</strong>
                                </div>
                            </div>

                            {/* Badan Isi Surat */}
                            <div className="py-2 text-sm leading-relaxed prose dark:prose-invert max-w-none min-h-[160px]"
                                dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(suratKeluar.isi_surat || '<p class="italic text-gray-400">Tidak ada isi naskah.</p>') }}
                            />

                            {/* Area Tanda Tangan Placeholder */}
                            <div className="flex justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                                <div className="text-center w-64 space-y-2">
                                    <div className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        {suratKeluar.penandatangan?.position?.name || 'Pejabat Penandatangan'},
                                    </div>
                                    <div className="p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-[11px] text-gray-400">
                                        [Area Tanda Tangan / TTE]
                                    </div>
                                    <div className="text-xs font-bold underline text-gray-900 dark:text-white">
                                        {suratKeluar.penandatangan?.name || 'Nama Pejabat'}
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Lampiran Dokumen */}
                        {suratKeluar.attachments && suratKeluar.attachments.length > 0 && (
                            <Card className="p-5 space-y-3">
                                <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Paperclip className="w-4 h-4 text-primary-600" />
                                    Lampiran Dokumen ({suratKeluar.attachments.length})
                                </h3>
                                <div className="divide-y divide-gray-100 dark:divide-gray-700">
                                    {suratKeluar.attachments.map((att) => (
                                        <div key={att.id} className="py-2.5 flex justify-between items-center text-xs">
                                            <span className="font-medium text-gray-800 dark:text-gray-200">{att.file_name}</span>
                                            <a
                                                href={`/storage/${att.file_path}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-primary-600 hover:underline font-semibold"
                                            >
                                                Unduh
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        )}
                    </div>

                    {/* Kolom Kanan (1/3): Lini Masa Persetujuan & Riwayat Versi */}
                    <div className="space-y-6">
                        {/* Lini Masa Persetujuan Bertingkat */}
                        <Card className="p-5 space-y-4">
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                <UserCheck className="w-4 h-4 text-primary-600" />
                                Lini Masa Persetujuan
                            </h3>

                            {suratKeluar.approvals && suratKeluar.approvals.length > 0 ? (
                                <div className="space-y-4 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-gray-200 dark:before:bg-gray-700">
                                    {suratKeluar.approvals.map((appr) => {
                                        const isDone = ['diparaf', 'disetujui'].includes(appr.status);
                                        const isRejected = appr.status === 'ditolak';
                                        const isRevising = appr.status === 'revisi';
                                        const isWaiting = appr.status === 'menunggu';

                                        return (
                                            <div key={appr.id} className="relative flex items-start gap-3 pl-1">
                                                <div className={`w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 z-10 text-xs font-bold ${
                                                    isDone
                                                        ? 'bg-emerald-600 text-white'
                                                        : isRejected
                                                        ? 'bg-red-600 text-white'
                                                        : isRevising
                                                        ? 'bg-purple-600 text-white'
                                                        : isWaiting
                                                        ? 'bg-amber-500 text-white animate-pulse'
                                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-600'
                                                }`}>
                                                    {appr.step_order}
                                                </div>

                                                <div className="flex-1 bg-gray-50 dark:bg-gray-800/60 p-3 rounded-xl border border-gray-100 dark:border-gray-700 text-xs">
                                                    <div className="flex justify-between items-start">
                                                        <span className="font-semibold text-gray-900 dark:text-white">
                                                            {appr.user?.name || appr.position?.name || 'Pemeriksa'}
                                                        </span>
                                                        <span className="text-[10px] uppercase font-bold text-gray-400">
                                                            {appr.role_type}
                                                        </span>
                                                    </div>

                                                    <div className="text-[11px] text-gray-500 mt-0.5">
                                                        Status: <strong className="capitalize">{appr.status}</strong>
                                                    </div>

                                                    {appr.catatan && (
                                                        <div className="mt-2 p-2 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 italic">
                                                            &ldquo;{appr.catatan}&rdquo;
                                                        </div>
                                                    )}

                                                    {appr.acted_at && (
                                                        <div className="text-[10px] text-gray-400 mt-1">
                                                            {appr.acted_at}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-xs text-gray-400 italic">
                                    Naskah dinas belum diajukan ke rantai pemeriksaan.
                                </p>
                            )}
                        </Card>

                        {/* Riwayat Versi Draf */}
                        <Card className="p-5 space-y-4">
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                <History className="w-4 h-4 text-primary-600" />
                                Riwayat Versi ({suratKeluar.versions.length})
                            </h3>

                            <div className="space-y-2">
                                {suratKeluar.versions.map((ver) => (
                                    <div
                                        key={ver.id}
                                        onClick={() => {
                                            setSelectedVersion(ver);
                                            setActionModal('version');
                                        }}
                                        className="p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-primary-400 cursor-pointer bg-gray-50/50 dark:bg-gray-800/40 transition-colors text-xs flex justify-between items-center"
                                    >
                                        <div>
                                            <span className="font-bold text-primary-600 dark:text-primary-400">
                                                Versi {ver.version_number}
                                            </span>
                                            <p className="text-gray-500 line-clamp-1 mt-0.5">
                                                {ver.catatan_perubahan || 'Pembaruan substansi draf'}
                                            </p>
                                        </div>
                                        <ChevronRight className="w-4 h-4 text-gray-400" />
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                </div>

                {/* BAR AKSI CEPAT MOBILE & DESKTOP (<= 3 KETUKAN UNTUK PIMPINAN) */}
                {isCurrentReviewer && (
                    <div className="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 p-4 shadow-xl">
                        <div className="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div className="text-xs text-gray-600 dark:text-gray-300">
                                <span className="font-bold text-gray-900 dark:text-white">Giliran Anda:</span>{' '}
                                Tahap {currentApproval?.step_order} ({currentApproval?.role_type?.toUpperCase()})
                            </div>

                            {/* Tombol aksi pimpinan: Setujui / Paraf / Minta Revisi / Tolak */}
                            <div className="flex items-center gap-2 w-full sm:w-auto">
                                <Button
                                    variant="primary"
                                    size="sm"
                                    className="flex-1 sm:flex-none flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
                                    onClick={() => {
                                        setActionNote('');
                                        setActionModal('approve');
                                    }}
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                    {currentApproval?.role_type === 'paraf' ? 'Paraf' : 'Setujui Naskah'}
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 sm:flex-none flex items-center justify-center gap-1.5 text-purple-600 border-purple-300 hover:bg-purple-50 dark:hover:bg-purple-950/30"
                                    onClick={() => {
                                        setActionNote('');
                                        setActionModal('revise');
                                    }}
                                >
                                    <RotateCcw className="w-4 h-4" />
                                    Minta Revisi
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 sm:flex-none flex items-center justify-center gap-1.5 text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-950/30"
                                    onClick={() => {
                                        setActionNote('');
                                        setActionModal('reject');
                                    }}
                                >
                                    <XCircle className="w-4 h-4" />
                                    Tolak
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* MODAL AKSI CEPAT PIMPINAN (Ketukan ke-2 & ke-3) */}
                {actionModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
                        <Card className="w-full max-w-md p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95">
                            {actionModal === 'approve' && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                        Konfirmasi {currentApproval?.role_type === 'paraf' ? 'Paraf' : 'Persetujuan'}
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Naskah akan diteruskan ke tahap berikutnya atau langsung dialokasikan nomor resmi jika ini merupakan persetujuan akhir.
                                    </p>
                                    <textarea
                                        value={actionNote}
                                        onChange={(e) => setActionNote(e.target.value)}
                                        placeholder="Catatan persetujuan (opsional)..."
                                        rows={3}
                                        className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Batal</Button>
                                        <Button variant="primary" size="sm" className="bg-emerald-600 hover:bg-emerald-700" onClick={handleApprove}>
                                            Konfirmasi Setujui
                                        </Button>
                                    </div>
                                </>
                            )}

                            {actionModal === 'revise' && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <RotateCcw className="w-5 h-5 text-purple-600" />
                                        Minta Revisi Naskah
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Draf akan dikembalikan ke pembuat surat dengan catatan koreksi.
                                    </p>
                                    <textarea
                                        value={actionNote}
                                        onChange={(e) => setActionNote(e.target.value)}
                                        placeholder="Tuliskan poin-poin yang harus direvisi (wajib)..."
                                        rows={3}
                                        className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Batal</Button>
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            className="bg-purple-600 hover:bg-purple-700"
                                            disabled={actionNote.trim().length < 3}
                                            onClick={handleRevise}
                                        >
                                            Kirim Catatan Revisi
                                        </Button>
                                    </div>
                                </>
                            )}

                            {actionModal === 'reject' && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <XCircle className="w-5 h-5 text-red-600" />
                                        Tolak Naskah Dinas
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Penolakan bersifat definitif dan mengakhiri alur naskah keluar ini.
                                    </p>
                                    <textarea
                                        value={actionNote}
                                        onChange={(e) => setActionNote(e.target.value)}
                                        placeholder="Alasan penolakan (wajib)..."
                                        rows={3}
                                        className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Batal</Button>
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            className="bg-red-600 hover:bg-red-700"
                                            disabled={actionNote.trim().length < 3}
                                            onClick={handleReject}
                                        >
                                            Konfirmasi Tolak
                                        </Button>
                                    </div>
                                </>
                            )}

                            {actionModal === 'cancel' && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <ShieldAlert className="w-5 h-5 text-red-600" />
                                        Pembatalan Naskah Bernomor Resmi
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Nomor surat yang telah terbit tidak akan dipakai ulang demi integritas audit arsip.
                                    </p>
                                    <textarea
                                        value={actionNote}
                                        onChange={(e) => setActionNote(e.target.value)}
                                        placeholder="Alasan resmi pembatalan naskah (wajib)..."
                                        rows={3}
                                        className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Batal</Button>
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            className="bg-red-600 hover:bg-red-700"
                                            disabled={actionNote.trim().length < 5}
                                            onClick={handleCancel}
                                        >
                                            Konfirmasi Batalkan
                                        </Button>
                                    </div>
                                </>
                            )}

                            {actionModal === 'resolve' && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <CheckCircle2 className="w-5 h-5 text-indigo-600" />
                                        Selesaikan Tindak Lanjut Disposisi
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Tindak lanjut lembar disposisi akan ditandai selesai dengan tautan ke surat balasan resmi ini.
                                    </p>
                                    <textarea
                                        value={actionNote}
                                        onChange={(e) => setActionNote(e.target.value)}
                                        rows={3}
                                        className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Batal</Button>
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            className="bg-indigo-600 hover:bg-indigo-700"
                                            onClick={handleResolveDisposisi}
                                        >
                                            Selesaikan Disposisi
                                        </Button>
                                    </div>
                                </>
                            )}

                            {actionModal === 'version' && selectedVersion && (
                                <>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                        Pratinjau Draf Versi {selectedVersion.version_number}
                                    </h3>
                                    <p className="text-xs text-gray-500">
                                        Oleh: {selectedVersion.user?.name || 'Pengguna'} &bull; Catatan: {selectedVersion.catatan_perubahan || '-'}
                                    </p>
                                    <div
                                        className="max-h-60 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-900 rounded-lg text-xs prose dark:prose-invert max-w-none border border-gray-200 dark:border-gray-700"
                                        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(selectedVersion.isi_surat) }}
                                    />
                                    <div className="flex justify-end pt-2">
                                        <Button variant="outline" size="sm" onClick={() => setActionModal(null)}>Tutup</Button>
                                    </div>
                                </>
                            )}
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
