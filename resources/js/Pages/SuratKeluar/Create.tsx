import React, { useState, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Send, ArrowLeft, Save, CheckCircle, FileText, Paperclip, Sparkles, AlertCircle, CornerDownRight, Eye
} from 'lucide-react';

interface TemplateItem {
    id: number;
    nama: string;
    jenis: string;
    konten_default: string;
    variabel?: string[];
}

interface SignerItem {
    id: number;
    name: string;
    position?: { name: string } | null;
}

interface CreateProps {
    templates: TemplateItem[];
    klasifikasiList: Array<{ id: number; kode_klasifikasi: string; nama: string }>;
    unitsList: Array<{ id: number; code: string; name: string }>;
    signers: SignerItem[];
    supportedVariables: Record<string, string>;
    replyToData?: {
        recipient_id: number;
        surat_masuk_id: number;
        surat_masuk_nomor: string;
        perihal: string;
        tujuan: string;
        klasifikasi_id?: number;
        isi_surat?: string;
    } | null;
}

export default function Create({
    templates,
    klasifikasiList,
    unitsList,
    signers,
    supportedVariables,
    replyToData,
}: CreateProps) {
    const { term } = useTerminology();
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [previewMode, setPreviewMode] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        template_id: '',
        klasifikasi_id: replyToData?.klasifikasi_id ? String(replyToData.klasifikasi_id) : '',
        org_unit_id: '',
        penandatangan_id: '',
        tujuan: replyToData?.tujuan || '',
        perihal: replyToData?.perihal || '',
        isi_surat: replyToData?.isi_surat || '<p>Dengan hormat,</p><p>Bersama surat ini kami sampaikan...</p>',
        sifat: 'biasa',
        tanggal_surat: new Date().toISOString().split('T')[0],
        reply_to_disposisi_recipient_id: replyToData?.recipient_id || '',
        submit_now: false,
        attachments: [] as File[],
    });

    const handleSelectTemplate = (templateId: string) => {
        setData('template_id', templateId);
        const selected = templates.find((t) => String(t.id) === templateId);
        if (selected && selected.konten_default) {
            setData((prev) => ({
                ...prev,
                template_id: templateId,
                isi_surat: selected.konten_default,
            }));
        }
    };

    const handleInsertVariable = (variableToken: string) => {
        const textarea = textareaRef.current;
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = data.isi_surat;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);

        const newText = before + variableToken + after;
        setData('isi_surat', newText);

        setTimeout(() => {
            textarea.focus();
            textarea.setSelectionRange(start + variableToken.length, start + variableToken.length);
        }, 50);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setData('attachments', Array.from(e.target.files));
        }
    };

    const handleSubmit = (submitNow: boolean) => {
        data.submit_now = submitNow;
        post('/surat-keluar');
    };

    return (
        <AppLayout title="Susun Naskah Dinas Keluar">
            <Head title="Susun Naskah Dinas Keluar" />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div className="flex items-center gap-3">
                        <Link href="/surat-keluar">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Send className="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                Susun Naskah Dinas Keluar
                            </h1>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Isi formulir draf naskah dinas, tentukan penandatangan, dan pilih template standar.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Banner Tautan Balasan Disposisi jika ada */}
                {replyToData && (
                    <div className="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 flex items-start gap-3">
                        <CornerDownRight className="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" />
                        <div className="text-sm">
                            <span className="font-semibold text-indigo-900 dark:text-indigo-200">
                                Mode Balasan Disposisi:
                            </span>{' '}
                            Naskah keluar ini terhubung otomatis sebagai tindak lanjut surat masuk{' '}
                            <span className="font-mono font-medium">{replyToData.surat_masuk_nomor}</span>. Saat naskah terbit bernomor, disposisi dapat ditandai selesai.
                        </div>
                    </div>
                )}

                {/* Form Utama */}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit(false);
                    }}
                    className="space-y-6"
                >
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Kolom Kiri: Metadata Utama */}
                        <Card className="p-5 md:col-span-2 space-y-4">
                            <h2 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                <FileText className="w-4 h-4 text-primary-600" />
                                Informasi Naskah Dinas
                            </h2>

                            {/* Template Selector */}
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Pilih Template Naskah Standar (Opsional)
                                </label>
                                <Select
                                    value={data.template_id}
                                    onChange={(e) => handleSelectTemplate(e.target.value)}
                                    className="text-sm"
                                >
                                    <option value="">-- Tanpa Template / Naskah Bebas --</option>
                                    {templates.map((tpl) => (
                                        <option key={tpl.id} value={tpl.id}>
                                            {tpl.nama} ({tpl.jenis.replace('_', ' ').toUpperCase()})
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            {/* Perihal & Tujuan */}
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Perihal Naskah <span className="text-red-500">*</span>
                                    </label>
                                    <FormInput
                                        value={data.perihal}
                                        onChange={(e) => setData('perihal', e.target.value)}
                                        placeholder="Contoh: Undangan Rapat Koordinasi Kurikulum"
                                        className="text-sm"
                                        error={errors.perihal}
                                    />
                                    {errors.perihal && <p className="text-xs text-red-500 mt-1">{errors.perihal}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Tujuan / Penerima <span className="text-red-500">*</span>
                                    </label>
                                    <FormInput
                                        value={data.tujuan}
                                        onChange={(e) => setData('tujuan', e.target.value)}
                                        placeholder="Contoh: Seluruh Ketua Jurusan & Koordinator Program Studi"
                                        className="text-sm"
                                        error={errors.tujuan}
                                    />
                                    {errors.tujuan && <p className="text-xs text-red-500 mt-1">{errors.tujuan}</p>}
                                </div>
                            </div>

                            {/* Editor Badan Naskah & Variabel Token */}
                            <div className="space-y-2 pt-2">
                                <div className="flex justify-between items-center">
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Isi Substansi Naskah Dinas
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => setPreviewMode(!previewMode)}
                                        className="text-xs text-primary-600 hover:text-primary-700 flex items-center gap-1 font-medium"
                                    >
                                        <Eye className="w-3.5 h-3.5" />
                                        {previewMode ? 'Mode Editor' : 'Pratinjau HTML'}
                                    </button>
                                </div>

                                {/* Variable Chips Bar */}
                                <div className="p-2.5 bg-gray-50 dark:bg-gray-800/80 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1">
                                        <Sparkles className="w-3 h-3 text-amber-500" />
                                        Klik variabel untuk menyisipkan ke dalam draf:
                                    </div>
                                    <div className="flex flex-wrap gap-1.5">
                                        {Object.keys(supportedVariables).map((vKey) => (
                                            <button
                                                key={vKey}
                                                type="button"
                                                onClick={() => handleInsertVariable(vKey)}
                                                className="px-2 py-0.5 text-xs bg-white dark:bg-gray-700 hover:bg-primary-50 dark:hover:bg-primary-950 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded shadow-2xs font-mono transition-colors"
                                                title={supportedVariables[vKey]}
                                            >
                                                {vKey}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {previewMode ? (
                                    <div
                                        className="w-full min-h-[220px] p-4 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-sm prose dark:prose-invert max-w-none"
                                        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(data.isi_surat) }}
                                    />
                                ) : (
                                    <textarea
                                        ref={textareaRef}
                                        value={data.isi_surat}
                                        onChange={(e) => setData('isi_surat', e.target.value)}
                                        rows={10}
                                        className="w-full text-sm font-sans rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-primary-500 focus:border-primary-500 leading-relaxed p-3"
                                        placeholder="Ketik substansi surat dinas atau kode HTML di sini..."
                                    />
                                )}
                                {errors.isi_surat && <p className="text-xs text-red-500">{errors.isi_surat}</p>}
                            </div>
                        </Card>

                        {/* Kolom Kanan: Pengaturan & Penandatangan */}
                        <div className="space-y-6">
                            <Card className="p-5 space-y-4">
                                <h2 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700">
                                    Klasifikasi & Pejabat
                                </h2>

                                {/* Klasifikasi Arsip */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Klasifikasi Arsip <span className="text-red-500">*</span>
                                    </label>
                                    <Select
                                        value={data.klasifikasi_id}
                                        onChange={(e) => setData('klasifikasi_id', e.target.value)}
                                        className="text-sm"
                                    >
                                        <option value="">-- Pilih Kode Klasifikasi --</option>
                                        {klasifikasiList.map((k) => (
                                            <option key={k.id} value={k.id}>
                                                {k.kode_klasifikasi} - {k.nama}
                                            </option>
                                        ))}
                                    </Select>
                                    {errors.klasifikasi_id && <p className="text-xs text-red-500 mt-1">{errors.klasifikasi_id}</p>}
                                </div>

                                {/* Unit Pembuat */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {term('unit_level_1', 'Unit Kerja')} Pembuat
                                    </label>
                                    <Select
                                        value={data.org_unit_id}
                                        onChange={(e) => setData('org_unit_id', e.target.value)}
                                        className="text-sm"
                                    >
                                        <option value="">-- Unit Bawaan Pengguna --</option>
                                        {unitsList.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name} ({u.code})
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Pejabat Penandatangan */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Pejabat Penandatangan <span className="text-red-500">*</span>
                                    </label>
                                    <Select
                                        value={data.penandatangan_id}
                                        onChange={(e) => setData('penandatangan_id', e.target.value)}
                                        className="text-sm"
                                    >
                                        <option value="">-- Pilih Pejabat Penandatangan --</option>
                                        {signers.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name} {s.position ? `(${s.position.name})` : ''}
                                            </option>
                                        ))}
                                    </Select>
                                    {errors.penandatangan_id && <p className="text-xs text-red-500 mt-1">{errors.penandatangan_id}</p>}
                                </div>

                                {/* Sifat & Tanggal */}
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sifat</label>
                                        <Select
                                            value={data.sifat}
                                            onChange={(e) => setData('sifat', e.target.value)}
                                            className="text-xs"
                                        >
                                            <option value="biasa">Biasa</option>
                                            <option value="penting">Penting</option>
                                            <option value="segera">Segera</option>
                                            <option value="rahasia">Rahasia</option>
                                        </Select>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                                        <FormInput
                                            type="date"
                                            value={data.tanggal_surat}
                                            onChange={(e) => setData('tanggal_surat', e.target.value)}
                                            className="text-xs px-2"
                                        />
                                    </div>
                                </div>
                            </Card>

                            {/* Lampiran Berkas */}
                            <Card className="p-5 space-y-3">
                                <h2 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                    <Paperclip className="w-4 h-4 text-primary-600" />
                                    Lampiran Dokumen
                                </h2>

                                <input
                                    type="file"
                                    multiple
                                    onChange={handleFileChange}
                                    className="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                                />
                                <p className="text-[11px] text-gray-400">
                                    Format: PDF, DOCX, XLSX, JPG, PNG (Maks 10 MB per berkas).
                                </p>
                            </Card>

                            {/* Tombol Aksi */}
                            <div className="space-y-2">
                                <Button
                                    type="button"
                                    variant="primary"
                                    className="w-full flex items-center justify-center gap-2 py-2.5 shadow-sm"
                                    disabled={processing}
                                    onClick={() => handleSubmit(true)}
                                >
                                    <CheckCircle className="w-4 h-4" />
                                    Ajukan Pemeriksaan Sekarang
                                </Button>

                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full flex items-center justify-center gap-2 py-2.5"
                                    disabled={processing}
                                >
                                    <Save className="w-4 h-4" />
                                    Simpan Sebagai Draf
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
