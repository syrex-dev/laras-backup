import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { Badge } from '@/Components/Badge';
import { Modal } from '@/Components/Modal';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    ArrowLeft, Send, CheckCircle2, Clock, AlertTriangle, Paperclip,
    FileText, User, UserCheck, CornerDownRight, ArrowUpRight, Upload,
    ChevronDown, ShieldAlert
} from 'lucide-react';

interface ShowProps {
    recipient: any;
    timelineTree: any[];
    instructionTemplates: Array<{ id: number; kode: string; nama: string }>;
    subordinatePositions: Array<{ id: number; name: string; org_unit?: { name: string } }>;
}

export default function Show({
    recipient,
    timelineTree,
    instructionTemplates,
    subordinatePositions,
}: ShowProps) {
    const { term } = useTerminology();
    const [isForwardModalOpen, setIsForwardModalOpen] = useState(false);
    const [isFollowupModalOpen, setIsFollowupModalOpen] = useState(false);

    // Form Followup
    const { data: followupData, setData: setFollowupData, post: postFollowup, processing: followupProcessing, errors: followupErrors, reset: resetFollowup } = useForm({
        status: 'selesai',
        catatan: '',
        surat_keluar_id: '',
        lampiran: null as File | null,
    });

    // Form Forward
    const { data: forwardData, setData: setForwardData, post: postForward, processing: forwardProcessing, errors: forwardErrors, reset: resetForward } = useForm({
        instruction_template_id: null as number | null,
        instruksi: '',
        catatan: '',
        recipients: [] as Array<{ position_id?: number; user_id?: number }>,
    });

    const handleFollowupSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postFollowup(`/disposisi/recipient/${recipient.id}/followup`, {
            onSuccess: () => {
                setIsFollowupModalOpen(false);
                resetFollowup();
            },
        });
    };

    const handleForwardSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postForward(`/disposisi/recipient/${recipient.id}/forward`, {
            onSuccess: () => {
                setIsForwardModalOpen(false);
                resetForward();
            },
        });
    };

    const surat = recipient.disposisi?.surat_masuk;
    const isFinished = ['selesai', 'ditolak', 'dikembalikan', 'diteruskan'].includes(recipient.status);

    return (
        <AppLayout title="Detail Disposisi & Tindak Lanjut">
            <Head title={`Disposisi #${recipient.id} - ${surat?.perihal || ''}`} />

            <div className="space-y-6 max-w-6xl mx-auto">
                {/* Navigasi Kembali & Judul */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/disposisi"
                            className="p-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 transition-colors"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-slate-900 dark:text-white">
                                    Lembar Disposisi Naskah
                                </h1>
                                {recipient.is_delegated && (
                                    <span className="px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                        Plt ({recipient.delegation_label})
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5">
                                No. Agenda: <strong>{surat?.nomor_agenda}</strong> • No. Surat: {surat?.nomor_surat}
                            </p>
                        </div>
                    </div>

                    {/* Aksi Utama */}
                    {!isFinished && (
                        <div className="flex items-center gap-2 self-start">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setIsForwardModalOpen(true)}
                                className="text-xs py-2 flex items-center gap-1.5"
                            >
                                <ArrowUpRight className="w-4 h-4" />
                                <span>Teruskan ke Bawahan</span>
                            </Button>
                            <Button
                                type="button"
                                variant="primary"
                                onClick={() => setIsFollowupModalOpen(true)}
                                className="text-xs py-2 flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                <CheckCircle2 className="w-4 h-4" />
                                <span>Tindak Lanjut / Selesaikan</span>
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Kolom Kiri (2/3): Rincian Surat, Instruksi & Lini Masa Pohon */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Kartu Ringkasan Naskah Surat Masuk */}
                        <Card className="p-5 space-y-4">
                            <div className="flex justify-between items-start gap-4">
                                <div>
                                    <span className="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                        Surat Masuk
                                    </span>
                                    <h2 className="text-lg font-bold text-slate-900 dark:text-white mt-1">
                                        {surat?.perihal}
                                    </h2>
                                </div>
                                {recipient.is_overdue && (
                                    <span className="px-2.5 py-1 text-xs font-bold rounded-md bg-rose-600 text-white animate-pulse">
                                        Terlambat!
                                    </span>
                                )}
                            </div>

                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg text-xs">
                                <div>
                                    <span className="text-slate-400 block">Pengirim:</span>
                                    <span className="font-medium text-slate-800 dark:text-slate-200">{surat?.pengirim}</span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block">Tanggal Naskah:</span>
                                    <span className="font-medium text-slate-800 dark:text-slate-200">{surat?.tanggal_surat}</span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block">Prioritas:</span>
                                    <span className="font-semibold capitalize text-emerald-700 dark:text-emerald-400">
                                        {recipient.disposisi?.prioritas || 'Biasa'}
                                    </span>
                                </div>
                            </div>

                            {/* Instruksi dari Atasan */}
                            <div className="border-l-4 border-emerald-500 pl-4 py-1 space-y-1">
                                <span className="text-xs font-semibold text-slate-500">
                                    Instruksi dari {recipient.disposisi?.pemberi?.name} ({recipient.disposisi?.pemberi_position?.name || 'Pimpinan'}):
                                </span>
                                <p className="text-sm font-medium text-slate-900 dark:text-white">
                                    {recipient.disposisi?.instruksi}
                                </p>
                                {recipient.disposisi?.catatan && (
                                    <p className="text-xs text-slate-500 italic mt-1">
                                        Catatan: "{recipient.disposisi.catatan}"
                                    </p>
                                )}
                            </div>

                            {/* Lampiran Asli Surat Masuk */}
                            {surat?.attachments?.length > 0 && (
                                <div>
                                    <span className="text-xs font-semibold text-slate-500 block mb-2">
                                        Berkas Surat Masuk:
                                    </span>
                                    <div className="flex flex-wrap gap-2">
                                        {surat.attachments.map((att: any) => (
                                            <a
                                                key={att.id}
                                                href={`/surat-masuk/attachments/${att.id}/download`}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition-colors"
                                            >
                                                <Paperclip className="w-3.5 h-3.5 text-slate-400" />
                                                <span>{att.file_name}</span>
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </Card>

                        {/* Lini Masa Rantai Pohon Disposisi (Timeline Tree) */}
                        <Card className="p-5 space-y-4">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                                <Clock className="w-4 h-4 text-emerald-600" />
                                <span>Lini Masa Rantai Disposisi Berjenjang</span>
                            </h3>

                            <div className="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                                {timelineTree.map((disp: any, idx: number) => (
                                    <div key={disp.id} className="relative">
                                        {/* Dot Indikator */}
                                        <div className="absolute -left-6 top-1.5 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 bg-emerald-600 flex items-center justify-center text-[10px] text-white font-bold">
                                            {idx + 1}
                                        </div>

                                        <div className="bg-slate-50 dark:bg-slate-800/80 p-3.5 rounded-lg border border-slate-200 dark:border-slate-700 space-y-2">
                                            <div className="flex justify-between items-start gap-2">
                                                <div>
                                                    <span className="text-xs font-bold text-slate-900 dark:text-white">
                                                        {disp.pemberi?.name || 'Pimpinan'}
                                                    </span>
                                                    <span className="text-[11px] text-slate-500 block">
                                                        {disp.pemberi_position?.name || 'Pejabat Pemberi Disposisi'}
                                                    </span>
                                                </div>
                                                <span className="text-[11px] text-slate-400">
                                                    {disp.created_at?.slice(0, 10)}
                                                </span>
                                            </div>

                                            <p className="text-xs text-slate-700 dark:text-slate-300 font-medium">
                                                "{disp.instruksi}"
                                            </p>

                                            {/* Daftar Penerima pada Tingkat ini */}
                                            <div className="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-1.5">
                                                <span className="text-[11px] font-semibold text-slate-500 block">
                                                    Penerima ({disp.recipients?.length || 0}):
                                                </span>
                                                <div className="space-y-1">
                                                    {disp.recipients?.map((rec: any) => (
                                                        <div
                                                            key={rec.id}
                                                            className="flex items-center justify-between text-xs bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded border border-slate-100 dark:border-slate-800"
                                                        >
                                                            <span className="font-medium text-slate-800 dark:text-slate-200">
                                                                {rec.is_delegated
                                                                    ? `${rec.delegated_user?.name || 'Plt'} (Plt. ${rec.recipient_position?.name})`
                                                                    : rec.recipient_user?.name || rec.recipient_position?.name}
                                                            </span>
                                                            <span className="px-1.5 py-0.5 text-[10px] rounded uppercase font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                                {rec.status}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>

                    {/* Kolom Kanan (1/3): Status Penerima & Bukti Tindak Lanjut */}
                    <div className="space-y-6">
                        <Card className="p-5 space-y-4">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                Status Lembar Anda
                            </h3>

                            <div className="space-y-3 text-xs">
                                <div>
                                    <span className="text-slate-400 block">Penerima Bertugas:</span>
                                    <span className="font-semibold text-slate-900 dark:text-white text-sm">
                                        {recipient.display_name}
                                    </span>
                                </div>

                                <div>
                                    <span className="text-slate-400 block">Status Saat Ini:</span>
                                    <span className="inline-block mt-1 px-2.5 py-1 rounded text-xs font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        {recipient.status}
                                    </span>
                                </div>

                                {recipient.dibaca_pada && (
                                    <div>
                                        <span className="text-slate-400 block">Dibaca Pada:</span>
                                        <span className="text-slate-700 dark:text-slate-300">{recipient.dibaca_pada}</span>
                                    </div>
                                )}

                                {recipient.selesai_pada && (
                                    <div>
                                        <span className="text-slate-400 block">Diselesaikan Pada:</span>
                                        <span className="text-slate-700 dark:text-slate-300">{recipient.selesai_pada}</span>
                                    </div>
                                )}

                                {recipient.catatan_tindak_lanjut && (
                                    <div className="p-2.5 bg-slate-50 dark:bg-slate-800 rounded">
                                        <span className="text-slate-400 block font-semibold mb-1">Catatan Hasil:</span>
                                        <p className="text-slate-800 dark:text-slate-200">
                                            {recipient.catatan_tindak_lanjut}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Riwayat Followup & Bukti Lampiran */}
                            {recipient.followups?.length > 0 && (
                                <div className="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
                                    <span className="text-xs font-semibold text-slate-700 dark:text-slate-300 block">
                                        Bukti Penyelesaian:
                                    </span>
                                    {recipient.followups.map((f: any) => (
                                        <div key={f.id} className="space-y-1">
                                            {f.attachments?.map((att: any) => (
                                                <a
                                                    key={att.id}
                                                    href={`/disposisi/attachments/${att.id}/download`}
                                                    className="flex items-center gap-2 p-2 rounded bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 text-xs hover:underline"
                                                >
                                                    <Paperclip className="w-3.5 h-3.5" />
                                                    <span className="truncate">{att.file_name}</span>
                                                </a>
                                            ))}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            {/* Modal Tindak Lanjut */}
            <Modal
                isOpen={isFollowupModalOpen}
                onClose={() => setIsFollowupModalOpen(false)}
                title="Tindak Lanjut & Selesaikan Disposisi"
            >
                <form onSubmit={handleFollowupSubmit} className="space-y-4 text-xs">
                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Status Hasil:
                        </label>
                        <select
                            value={followupData.status}
                            onChange={(e) => setFollowupData('status', e.target.value)}
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                        >
                            <option value="selesai">Selesai (Tugas Tuntas)</option>
                            <option value="diproses">Sedang Diproses (Update Progres)</option>
                            <option value="dikembalikan">Kembalikan / Ditolak (Bukan Wewenang)</option>
                        </select>
                    </div>

                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Uraian Hasil Tindak Lanjut:
                        </label>
                        <textarea
                            rows={4}
                            value={followupData.catatan}
                            onChange={(e) => setFollowupData('catatan', e.target.value)}
                            placeholder="Jelaskan tindakan yang telah diambil atau hasil penyelesaian..."
                            className="w-full p-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                            required
                        />
                    </div>

                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Unggah Bukti Penyelesaian (Opsional, PDF/Gambar):
                        </label>
                        <input
                            type="file"
                            onChange={(e) => setFollowupData('lampiran', e.target.files?.[0] || null)}
                            className="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <Button type="button" variant="secondary" onClick={() => setIsFollowupModalOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" variant="primary" disabled={followupProcessing}>
                            {followupProcessing ? 'Menyimpan...' : 'Simpan Tindak Lanjut'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Teruskan ke Bawahan */}
            <Modal
                isOpen={isForwardModalOpen}
                onClose={() => setIsForwardModalOpen(false)}
                title="Penerusan Disposisi Berjenjang"
            >
                <form onSubmit={handleForwardSubmit} className="space-y-4 text-xs">
                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Pilih Bawahan Penerima:
                        </label>
                        <select
                            onChange={(e) => setForwardData('recipients', [{ position_id: Number(e.target.value) }])}
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                            required
                        >
                            <option value="">-- Pilih Jabatan Bawahan --</option>
                            {subordinatePositions.map((pos) => (
                                <option key={pos.id} value={pos.id}>
                                    {pos.name} ({pos.org_unit?.name || 'Unit'})
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Instruksi Cepat (Template):
                        </label>
                        <select
                            onChange={(e) => {
                                const tplId = Number(e.target.value);
                                const tpl = instructionTemplates.find((t) => t.id === tplId);
                                setForwardData({
                                    ...forwardData,
                                    instruction_template_id: tplId || null,
                                    instruksi: tpl?.nama || '',
                                });
                            }}
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                        >
                            <option value="">-- Pilih Template Instruksi --</option>
                            {instructionTemplates.map((tpl) => (
                                <option key={tpl.id} value={tpl.id}>
                                    {tpl.nama}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Catatan Tambahan untuk Bawahan:
                        </label>
                        <textarea
                            rows={3}
                            value={forwardData.catatan}
                            onChange={(e) => setForwardData('catatan', e.target.value)}
                            placeholder="Petunjuk khusus pelaksanaan..."
                            className="w-full p-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <Button type="button" variant="secondary" onClick={() => setIsForwardModalOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" variant="primary" disabled={forwardProcessing}>
                            {forwardProcessing ? 'Meneruskan...' : 'Kirim ke Bawahan'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
