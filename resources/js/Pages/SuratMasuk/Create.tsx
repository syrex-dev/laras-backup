import React, { useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { useTerminology } from '@/Hooks/useTerminology';
import { Inbox, ArrowLeft, Upload, Paperclip, X, AlertCircle, FileText, CheckCircle2 } from 'lucide-react';

interface KlasifikasiItem {
    id: number;
    kode: string;
    nama: string;
}

interface UnitItem {
    id: number;
    code: string;
    name: string;
}

interface CreateProps {
    klasifikasiList: KlasifikasiItem[];
    unitsList: UnitItem[];
}

export default function Create({ klasifikasiList, unitsList }: CreateProps) {
    const { term } = useTerminology();
    const today = new Date().toISOString().split('T')[0];

    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);

    const { data, setData, post, processing, errors } = useForm({
        pengirim: '',
        nomor_surat: '',
        tanggal_surat: '',
        tanggal_diterima: today,
        perihal: '',
        sifat: 'biasa',
        jenis: 'fisik',
        klasifikasi_id: '',
        unit_tujuan_id: '',
        keterangan: '',
        lampiran: [] as File[],
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const filesArray = Array.from(e.target.files);
            const combined = [...selectedFiles, ...filesArray];
            setSelectedFiles(combined);
            setData('lampiran', combined);
        }
    };

    const removeFile = (index: number) => {
        const updated = selectedFiles.filter((_, i) => i !== index);
        setSelectedFiles(updated);
        setData('lampiran', updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/surat-masuk');
    };

    const klasifikasiOptions = [
        { value: '', label: '-- Pilih Kode Klasifikasi Arsip --' },
        ...klasifikasiList.map((k) => ({
            value: String(k.id),
            label: `${k.kode} - ${k.nama}`,
        })),
    ];

    const unitOptions = [
        { value: '', label: `-- Pilih ${term('unit_level_1', 'Unit Kerja')} Tujuan (Opsional) --` },
        ...unitsList.map((u) => ({
            value: String(u.id),
            label: `${u.name} (${u.code})`,
        })),
    ];

    const sifatOptions = [
        { value: 'biasa', label: 'Biasa' },
        { value: 'penting', label: 'Penting' },
        { value: 'segera', label: 'Segera' },
        { value: 'rahasia', label: 'Rahasia' },
    ];

    const jenisOptions = [
        { value: 'fisik', label: 'Fisik (Naskah Cetak / Kurir)' },
        { value: 'email', label: 'Email Resmi (Surat Elektronik)' },
        { value: 'undangan', label: 'Undangan Resmi' },
        { value: 'edaran', label: 'Surat Edaran' },
        { value: 'permohonan', label: 'Permohonan / Proposal' },
        { value: 'umum', label: 'Naskah Umum' },
    ];

    return (
        <AppLayout title="Registrasi Surat Masuk">
            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/surat-masuk">
                            <Button variant="secondary" size="sm" icon={<ArrowLeft className="w-4 h-4" />}>
                                Kembali
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                                <Inbox className="w-6 h-6 text-emerald-600" />
                                Registrasi Surat Masuk Baru
                            </h1>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Catat naskah masuk ke buku agenda digital. Nomor agenda akan digenerate otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                {errors.lampiran && (
                    <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg flex items-center gap-2">
                        <AlertCircle className="w-4 h-4 shrink-0" />
                        <span>{errors.lampiran}</span>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card title="Informasi Naskah & Pengirim" subtitle="Data identitas surat dari instansi atau pihak luar">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <FormInput
                                label="Asal Surat / Nama Pengirim"
                                name="pengirim"
                                placeholder="Contoh: Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi"
                                value={data.pengirim}
                                onChange={(e) => setData('pengirim', e.target.value)}
                                error={errors.pengirim}
                                required
                            />

                            <FormInput
                                label="Nomor Surat Asal"
                                name="nomor_surat"
                                placeholder="Contoh: 1234/UN.1/TU/2026"
                                value={data.nomor_surat}
                                onChange={(e) => setData('nomor_surat', e.target.value)}
                                error={errors.nomor_surat}
                                required
                            />

                            <FormInput
                                label="Tanggal Surat (Tanggal yang Tertera)"
                                type="date"
                                name="tanggal_surat"
                                value={data.tanggal_surat}
                                onChange={(e) => setData('tanggal_surat', e.target.value)}
                                error={errors.tanggal_surat}
                                required
                            />

                            <FormInput
                                label="Tanggal Diterima di Instansi"
                                type="date"
                                name="tanggal_diterima"
                                value={data.tanggal_diterima}
                                onChange={(e) => setData('tanggal_diterima', e.target.value)}
                                error={errors.tanggal_diterima}
                                required
                            />
                        </div>
                    </Card>

                    <Card title="Substansi & Klasifikasi Naskah" subtitle="Rincian perihal, derajat urgensi, dan unit tujuan">
                        <div className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Perihal Surat <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    rows={3}
                                    placeholder="Uraikan perihal pokok naskah dinas masuk..."
                                    value={data.perihal}
                                    onChange={(e) => setData('perihal', e.target.value)}
                                    className="block w-full rounded-lg border border-slate-300 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    required
                                />
                                {errors.perihal && (
                                    <p className="mt-1 text-xs text-rose-600 font-medium">{errors.perihal}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Select
                                    label="Sifat / Prioritas"
                                    options={sifatOptions}
                                    value={data.sifat}
                                    onChange={(e) => setData('sifat', e.target.value)}
                                    error={errors.sifat}
                                    required
                                />

                                <Select
                                    label="Jenis / Media Penerimaan"
                                    options={jenisOptions}
                                    value={data.jenis}
                                    onChange={(e) => setData('jenis', e.target.value)}
                                    error={errors.jenis}
                                    required
                                />

                                <Select
                                    label="Klasifikasi Arsip"
                                    options={klasifikasiOptions}
                                    value={data.klasifikasi_id}
                                    onChange={(e) => setData('klasifikasi_id', e.target.value)}
                                    error={errors.klasifikasi_id}
                                    required
                                />

                                <Select
                                    label={`${term('unit_level_1', 'Unit Kerja')} Tujuan`}
                                    options={unitOptions}
                                    value={data.unit_tujuan_id}
                                    onChange={(e) => setData('unit_tujuan_id', e.target.value)}
                                    error={errors.unit_tujuan_id}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Keterangan Tambahan / Ringkasan Isi
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Catatan khusus dari petugas penerima atau deskripsi singkat..."
                                    value={data.keterangan}
                                    onChange={(e) => setData('keterangan', e.target.value)}
                                    className="block w-full rounded-lg border border-slate-300 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                {errors.keterangan && (
                                    <p className="mt-1 text-xs text-rose-600 font-medium">{errors.keterangan}</p>
                                )}
                            </div>
                        </div>
                    </Card>

                    <Card title="Unggah Berkas Lampiran Privat" subtitle="Format yang didukung: PDF, JPG, PNG (Maks 10 MB per berkas)">
                        <div className="space-y-4">
                            <div className="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-emerald-500 transition-colors bg-slate-50/50">
                                <input
                                    type="file"
                                    id="file-upload"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={handleFileChange}
                                    className="hidden"
                                />
                                <label htmlFor="file-upload" className="cursor-pointer flex flex-col items-center justify-center">
                                    <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                        <Upload className="w-6 h-6" />
                                    </div>
                                    <span className="text-sm font-semibold text-emerald-700 hover:underline">
                                        Klik untuk memilih berkas lampiran
                                    </span>
                                    <span className="text-xs text-slate-500 mt-1">
                                        Berkas disimpan aman di penyimpanan privat dan dilindungi otorisasi
                                    </span>
                                </label>
                            </div>

                            {selectedFiles.length > 0 && (
                                <div className="space-y-2">
                                    <h4 className="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Berkas Terpilih ({selectedFiles.length}):
                                    </h4>
                                    <div className="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                                        {selectedFiles.map((file, idx) => (
                                            <div key={idx} className="p-3 flex items-center justify-between text-xs">
                                                <div className="flex items-center gap-2 overflow-hidden">
                                                    <FileText className="w-4 h-4 text-emerald-600 shrink-0" />
                                                    <span className="font-medium text-slate-800 truncate">{file.name}</span>
                                                    <span className="text-slate-400">
                                                        ({(file.size / 1024).toFixed(1)} KB)
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(idx)}
                                                    className="text-rose-500 hover:text-rose-700 p-1"
                                                >
                                                    <X className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </Card>

                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                        <Link href="/surat-masuk">
                            <Button variant="secondary" type="button">
                                Batal
                            </Button>
                        </Link>
                        <Button
                            variant="primary"
                            type="submit"
                            loading={processing}
                            icon={<CheckCircle2 className="w-4 h-4" />}
                        >
                            Simpan & Terbitkan Nomor Agenda
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
