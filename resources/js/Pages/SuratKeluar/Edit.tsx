import React, { useState, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { useTerminology } from '@/Hooks/useTerminology';
import {
    Send, ArrowLeft, Save, CheckCircle, FileText, Paperclip, Sparkles, AlertCircle, Eye
} from 'lucide-react';

interface SuratKeluarData {
    id: number;
    nomor_surat: string | null;
    template_id: number | null;
    klasifikasi_id: number;
    org_unit_id: number | null;
    penandatangan_id: number | null;
    tujuan: string;
    perihal: string;
    isi_surat: string | null;
    sifat: string;
    status: string;
    catatan_revisi?: string | null;
}

interface TemplateItem {
    id: number;
    nama: string;
    jenis: string;
    konten_default: string;
}

interface EditProps {
    suratKeluar: SuratKeluarData;
    templates: TemplateItem[];
    klasifikasiList: Array<{ id: number; kode_klasifikasi: string; nama: string }>;
    unitsList: Array<{ id: number; code: string; name: string }>;
    signers: Array<{ id: number; name: string; position?: { name: string } | null }>;
    supportedVariables: Record<string, string>;
}

export default function Edit({
    suratKeluar,
    templates,
    klasifikasiList,
    unitsList,
    signers,
    supportedVariables,
}: EditProps) {
    const { term } = useTerminology();
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [previewMode, setPreviewMode] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        template_id: suratKeluar.template_id ? String(suratKeluar.template_id) : '',
        klasifikasi_id: String(suratKeluar.klasifikasi_id),
        org_unit_id: suratKeluar.org_unit_id ? String(suratKeluar.org_unit_id) : '',
        penandatangan_id: suratKeluar.penandatangan_id ? String(suratKeluar.penandatangan_id) : '',
        tujuan: suratKeluar.tujuan || '',
        perihal: suratKeluar.perihal || '',
        isi_surat: suratKeluar.isi_surat || '',
        sifat: suratKeluar.sifat || 'biasa',
        catatan_perubahan: '',
        submit_now: false,
        attachments: [] as File[],
    });

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
        put(route('surat-keluar.update', suratKeluar.id));
    };

    return (
        <AppLayout title={`Edit Draf - ${suratKeluar.perihal}`}>
            <Head title={`Edit Draf - ${suratKeluar.perihal}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div className="flex items-center gap-3">
                        <Link href={route('surat-keluar.show', suratKeluar.id)}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <FileText className="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                Edit Draf Naskah Dinas
                            </h1>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Perubahan substansial pada isi, perihal, atau tujuan akan otomatis mencatat riwayat versi baru.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Catatan Revisi jika sedang dalam status revisi */}
                {suratKeluar.status === 'revisi' && suratKeluar.catatan_revisi && (
                    <div className="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800 flex items-start gap-3">
                        <AlertCircle className="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" />
                        <div>
                            <h3 className="text-sm font-semibold text-purple-900 dark:text-purple-200">
                                Catatan Pemeriksa untuk Revisi:
                            </h3>
                            <p className="text-sm text-purple-800 dark:text-purple-300 mt-1 italic">
                                &ldquo;{suratKeluar.catatan_revisi}&rdquo;
                            </p>
                        </div>
                    </div>
                )}

                {/* Form Edit */}
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
                            <h2 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700">
                                Substansi Naskah Dinas
                            </h2>

                            {/* Perihal & Tujuan */}
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Perihal Naskah <span className="text-red-500">*</span>
                                    </label>
                                    <FormInput
                                        value={data.perihal}
                                        onChange={(e) => setData('perihal', e.target.value)}
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
                                        className="w-full min-h-[240px] p-4 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-sm prose dark:prose-invert max-w-none"
                                        dangerouslySetInnerHTML={{ __html: data.isi_surat }}
                                    />
                                ) : (
                                    <textarea
                                        ref={textareaRef}
                                        value={data.isi_surat}
                                        onChange={(e) => setData('isi_surat', e.target.value)}
                                        rows={11}
                                        className="w-full text-sm font-sans rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-primary-500 focus:border-primary-500 leading-relaxed p-3"
                                    />
                                )}
                            </div>

                            {/* Catatan Perubahan Versi */}
                            <div>
                                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Catatan Riwayat Perubahan Versi (Opsional)
                                </label>
                                <FormInput
                                    value={data.catatan_perubahan}
                                    onChange={(e) => setData('catatan_perubahan', e.target.value)}
                                    placeholder="Contoh: Menyesuaikan klausul kerja sama sesuai arahan reviewer"
                                    className="text-xs"
                                />
                            </div>
                        </Card>

                        {/* Kolom Kanan: Pengaturan */}
                        <div className="space-y-6">
                            <Card className="p-5 space-y-4">
                                <h2 className="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b pb-2 border-gray-100 dark:border-gray-700">
                                    Klasifikasi & Pejabat
                                </h2>

                                {/* Klasifikasi */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Klasifikasi Arsip
                                    </label>
                                    <Select
                                        value={data.klasifikasi_id}
                                        onChange={(e) => setData('klasifikasi_id', e.target.value)}
                                        className="text-sm"
                                    >
                                        {klasifikasiList.map((k) => (
                                            <option key={k.id} value={k.id}>
                                                {k.kode_klasifikasi} - {k.nama}
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Penandatangan */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Pejabat Penandatangan
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
                                </div>

                                {/* Sifat */}
                                <div>
                                    <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sifat Naskah</label>
                                    <Select
                                        value={data.sifat}
                                        onChange={(e) => setData('sifat', e.target.value)}
                                        className="text-sm"
                                    >
                                        <option value="biasa">Biasa</option>
                                        <option value="penting">Penting</option>
                                        <option value="segera">Segera</option>
                                        <option value="rahasia">Rahasia</option>
                                    </Select>
                                </div>
                            </Card>

                            {/* Tombol Simpan & Submit */}
                            <div className="space-y-2">
                                <Button
                                    type="button"
                                    variant="primary"
                                    className="w-full flex items-center justify-center gap-2 py-2.5 shadow-sm"
                                    disabled={processing}
                                    onClick={() => handleSubmit(true)}
                                >
                                    <CheckCircle className="w-4 h-4" />
                                    Simpan & Ajukan Pemeriksaan
                                </Button>

                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full flex items-center justify-center gap-2 py-2.5"
                                    disabled={processing}
                                >
                                    <Save className="w-4 h-4" />
                                    Simpan Pembaruan Saja
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
