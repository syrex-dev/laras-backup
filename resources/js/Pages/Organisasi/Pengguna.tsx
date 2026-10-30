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
import { Users, UserPlus, Edit2, Trash2, Shield, AlertCircle, CheckCircle } from 'lucide-react';

interface PositionItem {
    id: number;
    name: string;
    org_unit?: { id: number; name: string; code: string } | null;
}

interface RoleItem {
    id: number;
    name: string;
}

interface UserItem {
    id: number;
    name: string;
    nip?: string | null;
    email: string;
    phone?: string | null;
    is_active: boolean;
    position_id?: number | null;
    position?: PositionItem | null;
    roles?: Array<{ id: number; name: string }>;
}

interface PenggunaProps {
    users: {
        data: UserItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    positions: PositionItem[];
    roles: RoleItem[];
    quota: {
        active_count: number;
        max_users: number;
    };
}

export default function Pengguna({ users, positions, roles, quota }: PenggunaProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserItem | null>(null);

    const { data, setData, post, put, reset, errors, processing } = useForm({
        name: '',
        nip: '',
        email: '',
        phone: '',
        password: '',
        position_id: '',
        role: 'Staf TU',
        is_active: true,
    });

    const isQuotaFull = quota.active_count >= quota.max_users;

    const openCreateModal = () => {
        setEditingUser(null);
        reset();
        setData({
            name: '',
            nip: '',
            email: '',
            phone: '',
            password: '',
            position_id: '',
            role: 'Staf TU',
            is_active: true,
        });
        setIsModalOpen(true);
    };

    const openEditModal = (user: UserItem) => {
        setEditingUser(user);
        setData({
            name: user.name,
            nip: user.nip || '',
            email: user.email,
            phone: user.phone || '',
            password: '',
            position_id: user.position_id ? String(user.position_id) : '',
            role: user.roles && user.roles.length > 0 ? user.roles[0].name : 'Staf TU',
            is_active: user.is_active,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingUser) {
            put(`/users/${editingUser.id}`, {
                onSuccess: () => setIsModalOpen(false),
            });
        } else {
            post('/users', {
                onSuccess: () => setIsModalOpen(false),
            });
        }
    };

    const handleDelete = (user: UserItem) => {
        if (confirm(`Apakah Anda yakin ingin menghapus akun pengguna '${user.name}'?`)) {
            router.delete(`/users/${user.id}`);
        }
    };

    const positionOptions = [
        { value: '', label: '-- Tanpa Jabatan Tertentu --' },
        ...positions.map((p) => ({
            value: String(p.id),
            label: `${p.name} (${p.org_unit ? p.org_unit.name : 'Pusat'})`,
        })),
    ];

    const roleOptions = roles.map((r) => ({
        value: r.name,
        label: r.name,
    }));

    return (
        <AppLayout title="Manajemen Pengguna & Penugasan">
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <Users className="w-6 h-6 text-emerald-600" />
                            Manajemen Akun Pengguna & Hak Akses
                        </h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            Kelola akun pegawai, penugasan jabatan struktural, dan peran otorisasi sistem.
                        </p>
                    </div>
                    <Button
                        variant="primary"
                        icon={<UserPlus className="w-4 h-4" />}
                        onClick={openCreateModal}
                        disabled={isQuotaFull}
                    >
                        Tambah Pengguna Baru
                    </Button>
                </div>

                {/* Banner Kuota Lisensi */}
                <div className="p-4 rounded-xl border bg-gradient-to-r from-emerald-50 to-teal-50 border-emerald-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-emerald-600/10 flex items-center justify-center text-emerald-600">
                            <Shield className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="font-semibold text-sm text-slate-800">
                                Entitlement Kuota Pengguna Aktif
                            </h3>
                            <p className="text-xs text-slate-500">
                                Saat ini terdaftar <strong className="text-emerald-700">{quota.active_count}</strong> pengguna aktif dari batas kuota paket <strong className="text-slate-800">{quota.max_users}</strong> pengguna.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {isQuotaFull ? (
                            <span className="inline-flex items-center gap-1.5 text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full">
                                <AlertCircle className="w-3.5 h-3.5" /> Kuota Penuh
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                                <CheckCircle className="w-3.5 h-3.5" /> Kuota Tersedia ({quota.max_users - quota.active_count} sisa)
                            </span>
                        )}
                    </div>
                </div>

                {/* Error Banner jika ada */}
                {errors.is_active && (
                    <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg flex items-center gap-2">
                        <AlertCircle className="w-4 h-4 shrink-0" />
                        <span>{errors.is_active}</span>
                    </div>
                )}

                <Card>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Nama & NIP</TableHeaderCell>
                                <TableHeaderCell>Email & Kontak</TableHeaderCell>
                                <TableHeaderCell>Jabatan & Unit</TableHeaderCell>
                                <TableHeaderCell>Peran (RBAC)</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {users.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-8 text-slate-400">
                                        Belum ada pengguna yang terdaftar.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>
                                            <div className="font-semibold text-slate-800">{user.name}</div>
                                            <div className="text-xs text-slate-400 font-mono">
                                                NIP: {user.nip || '-'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm text-slate-700">{user.email}</div>
                                            {user.phone && (
                                                <div className="text-xs text-slate-400">{user.phone}</div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {user.position ? (
                                                <div>
                                                    <div className="font-medium text-slate-700 text-sm">
                                                        {user.position.name}
                                                    </div>
                                                    <div className="text-xs text-slate-400">
                                                        {user.position.org_unit?.name || '-'}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-slate-400 italic">Non-Struktural</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles && user.roles.length > 0 ? (
                                                    user.roles.map((r) => (
                                                        <Badge
                                                            key={r.id}
                                                            variant={
                                                                r.name.includes('Admin')
                                                                    ? 'danger'
                                                                    : r.name.includes('Pimpinan')
                                                                    ? 'primary'
                                                                    : 'info'
                                                            }
                                                        >
                                                            {r.name}
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-xs text-slate-400">-</span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.is_active ? (
                                                <Badge variant="success">Aktif</Badge>
                                            ) : (
                                                <Badge variant="muted">Non-Aktif</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon={<Edit2 className="w-3.5 h-3.5" />}
                                                    onClick={() => openEditModal(user)}
                                                >
                                                    Ubah
                                                </Button>
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon={<Trash2 className="w-3.5 h-3.5" />}
                                                    onClick={() => handleDelete(user)}
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

                {/* Modal Tambah / Ubah Pengguna */}
                <Modal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    title={editingUser ? 'Ubah Data Pengguna' : 'Tambah Pengguna Baru'}
                >
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <FormInput
                            label="Nama Lengkap & Gelar"
                            name="name"
                            placeholder="Contoh: Dr. Ir. Budi Santoso, M.T."
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormInput
                                label="NIP (Nomor Induk Pegawai)"
                                name="nip"
                                placeholder="198501012010121001"
                                value={data.nip}
                                onChange={(e) => setData('nip', e.target.value)}
                                error={errors.nip}
                            />

                            <FormInput
                                label="Nomor Telepon / WhatsApp"
                                name="phone"
                                placeholder="081234567890"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                error={errors.phone}
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <FormInput
                                label="Alamat Email Resmi"
                                type="email"
                                name="email"
                                placeholder="nama@instansi.id"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                required
                            />

                            <FormInput
                                label={editingUser ? 'Kata Sandi Baru (Kosongkan jika tidak diubah)' : 'Kata Sandi'}
                                type="password"
                                name="password"
                                placeholder="Minimal 6 karakter"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                required={!editingUser}
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Select
                                label="Penugasan Jabatan Struktural"
                                options={positionOptions}
                                value={data.position_id}
                                onChange={(e) => setData('position_id', e.target.value)}
                                error={errors.position_id}
                            />

                            <Select
                                label="Peran Otorisasi (RBAC)"
                                options={roleOptions}
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                error={errors.role}
                                required
                            />
                        </div>

                        <div className="pt-2">
                            <label className="flex items-center gap-2 cursor-pointer select-none text-slate-700 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                                />
                                <span>Akun Aktif (Dapat masuk ke sistem dan mengelola dokumen)</span>
                            </label>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <Button variant="secondary" onClick={() => setIsModalOpen(false)} type="button">
                                Batal
                            </Button>
                            <Button variant="primary" type="submit" loading={processing}>
                                {editingUser ? 'Simpan Perubahan' : 'Daftarkan Pengguna'}
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AppLayout>
    );
}
