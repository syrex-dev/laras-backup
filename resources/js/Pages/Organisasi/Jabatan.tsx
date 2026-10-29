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
import { Briefcase, Plus, Edit2, Trash2, CheckCircle2, XCircle } from 'lucide-react';

interface OrgUnit {
    id: number;
    name: string;
    code: string;
}

interface PositionItem {
    id: number;
    org_unit_id: number;
    name: string;
    level: number;
    is_penandatangan: boolean;
    org_unit?: OrgUnit | null;
    users?: Array<{ id: number; name: string }>;
}

interface JabatanProps {
    positions: PositionItem[];
    units: OrgUnit[];
}

export default function Jabatan({ positions, units }: JabatanProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingPos, setEditingPos] = useState<PositionItem | null>(null);

    const { data, setData, post, put, reset, errors, processing } = useForm({
        name: '',
        org_unit_id: '',
        level: 1,
        is_penandatangan: false,
    });

    const openCreateModal = () => {
        setEditingPos(null);
        reset();
        setIsModalOpen(true);
    };

    const openEditModal = (pos: PositionItem) => {
        setEditingPos(pos);
        setData({
            name: pos.name,
            org_unit_id: String(pos.org_unit_id),
            level: pos.level,
            is_penandatangan: pos.is_penandatangan,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingPos) {
            put(`/jabatan/${editingPos.id}`, {
                onSuccess: () => setIsModalOpen(false),
            });
        } else {
            post('/jabatan', {
                onSuccess: () => setIsModalOpen(false),
            });
        }
    };

    const handleDelete = (pos: PositionItem) => {
        if (confirm(`Apakah Anda yakin ingin menghapus jabatan '${pos.name}'?`)) {
            router.delete(`/jabatan/${pos.id}`);
        }
    };

    const unitOptions = [
        { value: '', label: '-- Pilih Unit Kerja --' },
        ...units.map((u) => ({ value: String(u.id), label: `${u.name} (${u.code})` })),
    ];

    const levelOptions = [
        { value: '1', label: 'Level 1 - Pimpinan Tertinggi (Direktur/Ketua)' },
        { value: '2', label: 'Level 2 - Wakil Pimpinan / Kepala Biro / Dekan' },
        { value: '3', label: 'Level 3 - Kepala Bagian / Ketua Jurusan' },
        { value: '4', label: 'Level 4 - Kepala Subbagian / Ketua Prodi / Koordinator' },
        { value: '5', label: 'Level 5 - Pelaksana Teknis / Staf Administrasi' },
    ];

    return (
        <AppLayout title="Master Jabatan Struktural">
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <Briefcase className="w-6 h-6 text-emerald-600" />
                            Master Jabatan Struktural & Fungsional
                        </h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            Kelola hierarki jabatan, hak penandatangan naskah dinas, dan penugasan pejabat.
                        </p>
                    </div>
                    <Button variant="primary" icon={<Plus className="w-4 h-4" />} onClick={openCreateModal}>
                        Tambah Jabatan
                    </Button>
                </div>

                <Card>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Level</TableHeaderCell>
                                <TableHeaderCell>Nama Jabatan</TableHeaderCell>
                                <TableHeaderCell>Unit Kerja</TableHeaderCell>
                                <TableHeaderCell>Hak TTE / Tanda Tangan</TableHeaderCell>
                                <TableHeaderCell>Pejabat Aktif</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {positions.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-8 text-slate-400">
                                        Belum ada jabatan yang terdaftar.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                positions.map((pos) => (
                                    <TableRow key={pos.id}>
                                        <TableCell>
                                            <Badge variant={pos.level <= 2 ? 'primary' : 'muted'}>
                                                Level {pos.level}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-semibold text-slate-800">
                                            {pos.name}
                                        </TableCell>
                                        <TableCell className="text-slate-600 text-sm">
                                            {pos.org_unit ? `${pos.org_unit.name} (${pos.org_unit.code})` : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {pos.is_penandatangan ? (
                                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                                    Penandatangan
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-200">
                                                    <XCircle className="w-3.5 h-3.5 text-slate-400" />
                                                    Bukan Penandatangan
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-slate-600 text-sm">
                                            {pos.users && pos.users.length > 0 ? (
                                                <span className="font-medium text-slate-700">{pos.users[0].name}</span>
                                            ) : (
                                                <span className="text-amber-600 text-xs italic">Kosong (Belum ada pejabat)</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon={<Edit2 className="w-3.5 h-3.5" />}
                                                    onClick={() => openEditModal(pos)}
                                                >
                                                    Ubah
                                                </Button>
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon={<Trash2 className="w-3.5 h-3.5" />}
                                                    onClick={() => handleDelete(pos)}
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

                {/* Modal Tambah / Ubah Jabatan */}
                <Modal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    title={editingPos ? 'Ubah Data Jabatan' : 'Tambah Jabatan Baru'}
                >
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Select
                            label="Unit Kerja Naungan"
                            options={unitOptions}
                            value={data.org_unit_id}
                            onChange={(e) => setData('org_unit_id', e.target.value)}
                            error={errors.org_unit_id}
                            required
                        />

                        <FormInput
                            label="Nama Jabatan"
                            name="name"
                            placeholder="Contoh: Ketua Jurusan, Kepala Subbagian TU"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <Select
                            label="Level Hierarki Jabatan"
                            options={levelOptions}
                            value={String(data.level)}
                            onChange={(e) => setData('level', parseInt(e.target.value) || 1)}
                            error={errors.level}
                            required
                        />

                        <div className="pt-2">
                            <label className="flex items-center gap-2 cursor-pointer select-none text-slate-700 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.is_penandatangan}
                                    onChange={(e) => setData('is_penandatangan', e.target.checked)}
                                    className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                                />
                                <span>Jabatan ini memiliki hak penandatangan resmi naskah dinas keluar (TTE)</span>
                            </label>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button variant="secondary" onClick={() => setIsModalOpen(false)} type="button">
                                Batal
                            </Button>
                            <Button variant="primary" type="submit" loading={processing}>
                                {editingPos ? 'Simpan Perubahan' : 'Tambah Jabatan'}
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AppLayout>
    );
}
