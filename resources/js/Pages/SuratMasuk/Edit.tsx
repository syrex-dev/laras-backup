import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { useTerminology } from '@/Hooks/useTerminology';
import { Inbox, ArrowLeft, Save, AlertCircle } from 'lucide-react';

interface KlasifikasiItem {
    id: number;
    kode?: string;
    kode_klasifikasi?: string;
    nama: string;
}

interface UnitItem {
    id: number;
    code: string;
    name: string;
}

interface EditProps {
    surat: {
        id: number;
        nomor_agenda: string;
        pengirim: string;
        nomor_surat: string;
        tanggal_surat: string;
        tanggal_diterima: string;
        perihal: string;
        sifat: string;
        jenis: string;
        klasifikasi_id?: number | null;
        unit_tujuan_id?: number | null;
        keterangan?: string | null;
    };
    klasifikasiList: KlasifikasiItem[];
    unitsList: UnitItem[];
}

export default function Edit({ surat, klasifikasiList = [], unitsList = [] }: EditProps) {
    const { term } = useTerminology();

    const { data, setData, put, processing, errors } = useForm({
        pengirim: surat.pengirim || '',
        nomor_surat: surat.nomor_surat || '',
        tanggal_surat: surat.tanggal_surat || '',
        tanggal_diterima: surat.tanggal_diterima || '',
        perihal: surat.perihal || '',
        sifat: surat.sifat || 'biasa',
        jenis: surat.jenis || 'fisik',
        klasifikasi_id: surat.klasifikasi_id ? String(surat.klasifikasi_id) : '',
        unit_tujuan_id: surat.unit_tujuan_id ? String(surat.unit_tujuan_id) : '',
        keterangan: surat.keterangan || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/surat-masuk/${surat.id}`);
    };

    const klasifikasiOptions = [
        { value: '', label: '-- Pilih Kode Klasifikasi Arsip --' },
        ...klasifikasiList.map((k) => ({
            value: String(k.id),
            label: `${k.kode || k.kode_klasifikasi} - ${k.nama}`,
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
        <AppLayout title={`Koreksi Surat Masuk #${surat.nomor_agenda}`}>
            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href={`/surat-masuk/${surat.id}`}>
                            <Button variant="secondary" size="sm" icon={<ArrowLeft className="w-4 h-4" />}>
                                Kembali ke Detail
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                                <Inbox className="w-6 h-6 text-emerald-600" />
                                Koreksi Surat Masuk ({surat.nomor_agenda})
                            </h1>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Perbarui metadata naskah surat masuk yang telah terdaftar di buku agenda.
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card title="Data Utama Surat Masuk">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <FormInput
                                label="Instansi / Pihak Pengirim"
                                value={data.pengirim}
                                onChange={(e) => setData('pengirim', e.target.value)}
                                error={errors.pengirim}
                                required
                            />
                            <FormInput
                                label="Nomor Surat Asal"
                                value={data.nomor_surat}
                                onChange={(e) => setData('nomor_surat', e.target.value)}
                                error={errors.nomor_surat}
                                required
                            />
                            <FormInput
                                label="Tanggal Surat Asal"
                                type="date"
                                value={data.tanggal_surat}
                                onChange={(e) => setData('tanggal_surat', e.target.value)}
                                error={errors.tanggal_surat}
                                required
                            />
                            <FormInput
                                label="Tanggal Diterima TU"
                                type="date"
                                value={data.tanggal_diterima}
                                onChange={(e) => setData('tanggal_diterima', e.target.value)}
                                error={errors.tanggal_diterima}
                                required
                            />
                        </div>

                        <div className="mt-4">
                            <FormInput
                                label="Perihal / Ringkasan Isi Surat"
                                value={data.perihal}
                                onChange={(e) => setData('perihal', e.target.value)}
                                error={errors.perihal}
                                required
                            />
                        </div>
                    </Card>

                    <Card title="Klasifikasi & Tujuan">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Select
                                label="Klasifikasi Kearsipan"
                                value={data.klasifikasi_id}
                                onChange={(e) => setData('klasifikasi_id', e.target.value)}
                                options={klasifikasiOptions}
                                error={errors.klasifikasi_id}
                                required
                            />
                            <Select
                                label={`${term('unit_level_1', 'Unit Kerja')} Tujuan`}
                                value={data.unit_tujuan_id}
                                onChange={(e) => setData('unit_tujuan_id', e.target.value)}
                                options={unitOptions}
                                error={errors.unit_tujuan_id}
                            />
                            <Select
                                label="Sifat Surat"
                                value={data.sifat}
                                onChange={(e) => setData('sifat', e.target.value)}
                                options={sifatOptions}
                                error={errors.sifat}
                            />
                            <Select
                                label="Jenis Naskah"
                                value={data.jenis}
                                onChange={(e) => setData('jenis', e.target.value)}
                                options={jenisOptions}
                                error={errors.jenis}
                            />
                        </div>

                        <div className="mt-4">
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Keterangan Tambahan / Catatan Staf TU
                            </label>
                            <textarea
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                rows={3}
                                className="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Link href={`/surat-masuk/${surat.id}`}>
                            <Button variant="outline" type="button">
                                Batal
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" disabled={processing} className="gap-2">
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Menyimpan Perubahan...' : 'Simpan Perubahan'}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
