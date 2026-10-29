import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { Modal } from '@/Components/Modal';
import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/Components/Table';
import { Badge } from '@/Components/Badge';
import { Building2, Plus, Edit2, Trash2 } from 'lucide-react';

interface OrgUnitItem {
    id: number;
    code: string;
    name: string;
    type: string;
    parent_id?: number | null;
    order_index: number;
    parent?: { id: number; name: string } | null;
    positions?: Array<{ id: number; name: string }>;
}

interface UnitKerjaProps {
    units: OrgUnitItem[];
}

export default function UnitKerja({ units }: UnitKerjaProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUnit, setEditingUnit] = useState<OrgUnitItem | null>(null);

    const { data, setData, post, put, reset, errors, processing } = useForm({
        name: '',
        code: '',
        type: 'bagian',
        parent_id: '',
        order_index: 0,
    });

    const openCreateModal = () => {
        setEditingUnit(null);
        reset();
        setIsModalOpen(true);
    };

    const openEditModal = (unit: OrgUnitItem) => {
        setEditingUnit(unit);
        setData({
            name: unit.name,
            code: unit.code,
            type: unit.type,
            parent_id: unit.parent_id ? String(unit.parent_id) : '',
            order_index: unit.order_index,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingUnit) {
            put(`/unit-kerja/${editingUnit.id}`, {
                onSuccess: () => setIsModalOpen(false),
            });
        } else {
            post('/unit-kerja', {
                onSuccess: () => setIsModalOpen(false),
            });
        }
    };

    const handleDelete = (unit: OrgUnitItem) => {
        if (confirm(`Apakah Anda yakin ingin menghapus unit kerja '${unit.name}'?`)) {
            router.delete(`/unit-kerja/${unit.id}`);
        }
    };

    const typeOptions = [
        { value: 'pimpinan', label: 'Pimpinan Utama' },
        { value: 'jurusan', label: 'Jurusan / Fakultas' },
        { value: 'prodi', label: 'Program Studi / Bagian' },
        { value: 'bagian', label: 'Bagian / Biro / Tata Usaha' },
        { value: 'unit_pelaksana', label: 'Unit Pelaksana Teknis (UPT)' },
    ];

    const parentOptions = [
        { value: '', label: '-- Tanpa Induk (Unit Teratas) --' },
        ...units
            .filter((u) => !editingUnit || u.id !== editingUnit.id)
            .map((u) => ({ value: String(u.id), label: `${u.name} (${u.code})` })),
    ];

    return (
        <AppLayout title="Struktur Unit Kerja">
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <Building2 className="w-6 h-6 text-emerald-600" />
                            Daftar Unit Kerja & Struktur Organisasi
                        </h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            Kelola bagan organisasi instansi, pembagian divisi, dan hierarki persuratan.
                        </p>
                    </div>
                    <Button variant="primary" icon={<Plus className="w-4 h-4" />} onClick={openCreateModal}>
                        Tambah Unit Kerja
                    </Button>
                </div>

                <Card>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Kode</TableHeaderCell>
                                <TableHeaderCell>Nama Unit Kerja</TableHeaderCell>
                                <TableHeaderCell>Tipe</TableHeaderCell>
                                <TableHeaderCell>Unit Induk</TableHeaderCell>
                                <TableHeaderCell>Jumlah Jabatan</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {units.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-8 text-slate-400">
                                        Belum ada unit kerja yang terdaftar.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                units.map((unit) => (
                                    <TableRow key={unit.id}>
                                        <TableCell className="font-mono font-medium text-emerald-700">
                                            {unit.code}
                                        </TableCell>
                                        <TableCell className="font-semibold text-slate-800">
                                            {unit.name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="info">
                                                {unit.type.toUpperCase()}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-slate-600 text-sm">
                                            {unit.parent ? unit.parent.name : '-'}
                                        </TableCell>
                                        <TableCell className="text-slate-600 text-sm">
                                            {unit.positions?.length ?? 0} Jabatan
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon={<Edit2 className="w-3.5 h-3.5" />}
                                                    onClick={() => openEditModal(unit)}
                                                >
                                                    Ubah
                                                </Button>
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon={<Trash2 className="w-3.5 h-3.5" />}
                                                    onClick={() => handleDelete(unit)}
                                                >
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </Card>

                {/* Modal Tambah / Ubah */}
                <Modal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    title={editingUnit ? 'Ubah Data Unit Kerja' : 'Tambah Unit Kerja Baru'}
                >
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <FormInput
                            label="Kode Unit"
                            name="code"
                            placeholder="Contoh: TU, JTK, PIMPINAN"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                            error={errors.code}
                            required
                        />

                        <FormInput
                            label="Nama Unit Kerja"
                            name="name"
                            placeholder="Contoh: Bagian Tata Usaha"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <Select
                            label="Tipe Unit"
                            options={typeOptions}
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            error={errors.type}
                            required
                        />

                        <Select
                            label="Unit Induk (Hirarki)"
                            options={parentOptions}
                            value={data.parent_id}
                            onChange={(e) => setData('parent_id', e.target.value)}
                            error={errors.parent_id}
                        />

                        <FormInput
                            label="Urutan Tampilan"
                            type="number"
                            name="order_index"
                            value={String(data.order_index)}
                            onChange={(e) => setData('order_index', parseInt(e.target.value) || 0)}
                            error={errors.order_index}
                        />

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button variant="secondary" onClick={() => setIsModalOpen(false)} type="button">
                                Batal
                            </Button>
                            <Button variant="primary" type="submit" loading={processing}>
                                {editingUnit ? 'Simpan Perubahan' : 'Tambah Unit'}
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AppLayout>
    );
}
