import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Modal } from '@/Components/Modal';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { Archive, Plus, Edit2, Trash2, FolderTree, AlertCircle } from 'lucide-react';

interface KlasifikasiItem {
    id: number;
    kode_klasifikasi: string;
    nama: string;
    uraian?: string | null;
    retensi_aktif?: number | null;
    retensi_inaktif?: number | null;
    surat_masuk_count?: number;
    surat_keluar_count?: number;
}

interface KlasifikasiProps {
    klasifikasi: {
        data: KlasifikasiItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function KlasifikasiSuratPage({ klasifikasi }: KlasifikasiProps) {
    const list = klasifikasi?.data || [];
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<KlasifikasiItem | null>(null);

    const { data, setData, post, put, reset, errors, processing } = useForm({
        kode_klasifikasi: '',
        nama: '',
        uraian: '',
        retensi_aktif: 2,
        retensi_inaktif: 5,
    });

    const openCreateModal = () => {
        setEditingItem(null);
        reset();
        setIsModalOpen(true);
    };

    const openEditModal = (item: KlasifikasiItem) => {
        setEditingItem(item);
        setData({
            kode_klasifikasi: item.kode_klasifikasi,
            nama: item.nama,
            uraian: item.uraian || '',
            retensi_aktif: item.retensi_aktif || 2,
            retensi_inaktif: item.retensi_inaktif || 5,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingItem) {
            put(`/klasifikasi-surat/${editingItem.id}`, {
                onSuccess: () => {
                    setIsModalOpen(false);
                    reset();
                },
            });
        } else {
            post('/klasifikasi-surat', {
                onSuccess: () => {
                    setIsModalOpen(false);
                    reset();
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Yakin ingin menghapus klasifikasi naskah ini?')) {
            router.delete(`/klasifikasi-surat/${id}`);
        }
    };

    return (
        <AppLayout title="Klasifikasi Kearsipan">
            <Head title="Klasifikasi Surat" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-emerald-600 mb-1 tracking-wide uppercase">
                            <FolderTree className="w-4 h-4" />
                            <span>Kearsipan Dinamis</span>
                        </div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                            Pola Klasifikasi Surat & Naskah Dinas
                        </h1>
                        <p className="text-xs md:text-sm text-slate-500 mt-1">
                            Kode klasifikasi pembagian urusan fungsi organisasi untuk penomoran baku dan jadwal retensi arsip (JRA).
                        </p>
                    </div>

                    <Button variant="primary" onClick={openCreateModal} className="gap-2">
                        <Plus className="w-4 h-4" />
                        <span>Tambah Klasifikasi</span>
                    </Button>
                </div>

                {/* Table */}
                <Card className="overflow-hidden">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Kode Klasifikasi</TableHeaderCell>
                                <TableHeaderCell>Nama Perihal</TableHeaderCell>
                                <TableHeaderCell>Uraian / Deskripsi</TableHeaderCell>
                                <TableHeaderCell>Jadwal Retensi</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {list.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                                        <Archive className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                        Belum ada data klasifikasi naskah dinas.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                list.map((item) => (
                                    <TableRow key={item.id} hover>
                                        <TableCell className="font-mono text-xs font-bold text-emerald-700 bg-emerald-50/50 px-3 py-1 rounded">
                                            {item.kode_klasifikasi}
                                        </TableCell>
                                        <TableCell className="font-semibold text-slate-800 text-sm">
                                            {item.nama}
                                        </TableCell>
                                        <TableCell className="max-w-xs text-xs text-slate-500 truncate">
                                            {item.uraian || '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-xs text-slate-600">
                                            <Badge variant="muted">
                                                Aktif: {item.retensi_aktif ?? 2} th / Inaktif: {item.retensi_inaktif ?? 5} th
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openEditModal(item)}
                                                    className="p-1.5 text-slate-500 hover:text-slate-800"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(item.id)}
                                                    className="p-1.5 text-slate-500 hover:text-rose-600"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </Card>

                {/* Modal Form */}
                <Modal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    title={editingItem ? 'Edit Klasifikasi Surat' : 'Tambah Klasifikasi Surat Baru'}
                >
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <FormInput
                            label="Kode Klasifikasi"
                            value={data.kode_klasifikasi}
                            onChange={(e) => setData('kode_klasifikasi', e.target.value)}
                            error={errors.kode_klasifikasi}
                            placeholder="Contoh: HM.02 atau TU.01"
                            required
                        />

                        <FormInput
                            label="Nama Perihal"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            error={errors.nama}
                            placeholder="Contoh: Hubungan Masyarakat / Kunjungan Kerja"
                            required
                        />

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Uraian Lengkap
                            </label>
                            <textarea
                                value={data.uraian}
                                onChange={(e) => setData('uraian', e.target.value)}
                                rows={3}
                                className="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Uraian peruntukan klasifikasi..."
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <FormInput
                                label="Retensi Aktif (Tahun)"
                                type="number"
                                value={data.retensi_aktif}
                                onChange={(e) => setData('retensi_aktif', parseInt(e.target.value) || 0)}
                                error={errors.retensi_aktif}
                            />
                            <FormInput
                                label="Retensi Inaktif (Tahun)"
                                type="number"
                                value={data.retensi_inaktif}
                                onChange={(e) => setData('retensi_inaktif', parseInt(e.target.value) || 0)}
                                error={errors.retensi_inaktif}
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-2">
                            <Button variant="outline" type="button" onClick={() => setIsModalOpen(false)}>
                                Batal
                            </Button>
                            <Button variant="primary" type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AppLayout>
    );
}
