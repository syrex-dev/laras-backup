import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { Modal } from '@/Components/Modal';
import {
    FileText, Plus, Search, Edit3, Trash2, Eye,
    CheckCircle2, AlertCircle, Copy, Sparkles, Building2
} from 'lucide-react';
import axios from 'axios';

interface TemplateItem {
    id: number;
    nama: string;
    jenis: string;
    kode: string;
    format_nomor: string | null;
    konten_default: string;
    variabel: string[];
    use_institution_header: boolean;
    is_active: boolean;
    created_at: string;
    created_by?: { name: string } | null;
}

interface IndexProps {
    templates: {
        data: TemplateItem[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        jenis: string;
        search: string;
    };
    supportedVariables: Record<string, string>;
    institution?: any;
}

export default function Index({ templates, filters, supportedVariables, institution }: IndexProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState<TemplateItem | null>(null);
    const [previewModalOpen, setPreviewModalOpen] = useState(false);
    const [previewContent, setPreviewContent] = useState<string>('');
    const [previewLoading, setPreviewLoading] = useState(false);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nama: '',
        jenis: 'nota_dinas',
        kode: '',
        format_nomor: '',
        konten_default: '',
        use_institution_header: true,
        is_active: true,
    });

    const openCreateModal = () => {
        setEditingTemplate(null);
        reset();
        clearErrors();
        setData({
            nama: '',
            jenis: 'nota_dinas',
            kode: '',
            format_nomor: '{urut}/ND/{kode_unit}/{bulan_romawi}/{tahun}',
            konten_default: '<p>Nomor: {nomor_surat}</p><p>Tanggal: {tanggal_surat}</p><p>Kepada Yth. {tujuan}</p><p>Perihal: {perihal}</p><p>Sehubungan dengan pelaksanaan tugas di {unit_pembuat}, bersama ini disampaikan hal-hal sebagai berikut:</p><p style="margin-top: 30px; text-align: right;">{jabatan_penandatangan},<br /><br /><br /><strong>{nama_penandatangan}</strong></p>',
            use_institution_header: true,
            is_active: true,
        });
        setModalOpen(true);
    };

    const openEditModal = (tpl: TemplateItem) => {
        setEditingTemplate(tpl);
        clearErrors();
        setData({
            nama: tpl.nama,
            jenis: tpl.jenis,
            kode: tpl.kode || '',
            format_nomor: tpl.format_nomor || '',
            konten_default: tpl.konten_default,
            use_institution_header: tpl.use_institution_header,
            is_active: tpl.is_active,
        });
        setModalOpen(true);
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingTemplate) {
            put(`/surat-keluar/templates/${editingTemplate.id}`, {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        } else {
            post('/surat-keluar/templates', {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        }
    };

    const handleDelete = (tpl: TemplateItem) => {
        if (confirm(`Apakah Anda yakin ingin menghapus template '${tpl.nama}'?`)) {
            router.delete(`/surat-keluar/templates/${tpl.id}`);
        }
    };

    const handlePreview = async (konten: string) => {
        setPreviewLoading(true);
        setPreviewModalOpen(true);
        try {
            const res = await axios.post('/surat-keluar/templates/preview', {
                konten_default: konten,
            });
            setPreviewContent(res.data.rendered);
        } catch (err) {
            setPreviewContent('<p class="text-rose-500">Gagal memuat pratinjau template.</p>');
        } finally {
            setPreviewLoading(false);
        }
    };

    const insertVariable = (varKey: string) => {
        setData('konten_default', data.konten_default + ' ' + varKey);
    };

    const getJenisBadge = (jenis: string) => {
        const map: Record<string, { label: string; color: string }> = {
            nota_dinas: { label: 'Nota Dinas', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' },
            surat_tugas: { label: 'Surat Tugas', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' },
            edaran: { label: 'Surat Edaran', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' },
            sk: { label: 'Surat Keputusan (SK)', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' },
            undangan: { label: 'Undangan Resmi', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300' },
            biasa: { label: 'Surat Biasa', color: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' },
        };
        const item = map[jenis] || { label: jenis, color: 'bg-slate-100 text-slate-800' };
        return <span className={`px-2 py-0.5 rounded text-xs font-semibold ${item.color}`}>{item.label}</span>;
    };

    return (
        <AppLayout title="Master Template Surat Keluar">
            <Head title="Master Template Surat Keluar" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                            <FileText className="w-6 h-6 text-emerald-600" />
                            <span>Master Template Surat Keluar</span>
                        </h1>
                        <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            Kelola format baku naskah dinas, variabel otomatis, dan format penomoran per jenis surat.
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="primary"
                        onClick={openCreateModal}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium flex items-center gap-1.5"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Tambah Template Baru</span>
                    </Button>
                </div>

                {/* Filter & Search Bar */}
                <Card className="p-4">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get('/surat-keluar/templates', { ...filters, search: searchQuery }, { preserveState: true });
                        }}
                        className="grid grid-cols-1 md:grid-cols-12 gap-3 items-center"
                    >
                        <div className="md:col-span-8 relative">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari nama template atau kode naskah..."
                                className="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                        </div>

                        <div className="md:col-span-4">
                            <select
                                value={filters.jenis}
                                onChange={(e) => router.get('/surat-keluar/templates', { ...filters, jenis: e.target.value }, { preserveState: true })}
                                className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                <option value="semua">Semua Kategori Naskah</option>
                                <option value="nota_dinas">Nota Dinas</option>
                                <option value="surat_tugas">Surat Tugas</option>
                                <option value="edaran">Surat Edaran</option>
                                <option value="sk">Surat Keputusan (SK)</option>
                                <option value="undangan">Undangan</option>
                                <option value="biasa">Surat Biasa</option>
                            </select>
                        </div>
                    </form>
                </Card>

                {/* Grid Template Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {templates.data.length === 0 ? (
                        <div className="md:col-span-2 py-12 text-center bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                            <FileText className="w-10 h-10 text-slate-400 mx-auto mb-2 opacity-60" />
                            <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada template surat.</p>
                            <p className="text-xs text-slate-500 mt-1">Gunakan tombol "Tambah Template Baru" untuk menyusun draf master.</p>
                        </div>
                    ) : (
                        templates.data.map((tpl) => (
                            <Card key={tpl.id} className="p-5 flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                                <div className="space-y-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <div className="flex items-center gap-2 mb-1">
                                                {getJenisBadge(tpl.jenis)}
                                                {tpl.kode && (
                                                    <span className="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                        {tpl.kode}
                                                    </span>
                                                )}
                                            </div>
                                            <h3 className="font-semibold text-slate-900 dark:text-white text-base">
                                                {tpl.nama}
                                            </h3>
                                        </div>

                                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium ${
                                            tpl.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600'
                                        }`}>
                                            {tpl.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </div>

                                    {tpl.format_nomor && (
                                        <div className="bg-slate-50 dark:bg-slate-800/80 p-2 rounded text-xs font-mono text-slate-600 dark:text-slate-300 truncate">
                                            Format: {tpl.format_nomor}
                                        </div>
                                    )}

                                    {tpl.variabel && tpl.variabel.length > 0 && (
                                        <div>
                                            <span className="text-[11px] font-medium text-slate-500 block mb-1">Variabel Dipakai:</span>
                                            <div className="flex flex-wrap gap-1">
                                                {tpl.variabel.map((v, i) => (
                                                    <span key={i} className="text-[10px] font-mono px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-emerald-700 dark:text-emerald-400">
                                                        {v}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <button
                                        type="button"
                                        onClick={() => handlePreview(tpl.konten_default)}
                                        className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                    >
                                        <Eye className="w-3.5 h-3.5" />
                                        <span>Pratinjau Naskah</span>
                                    </button>

                                    <div className="flex items-center gap-1">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => openEditModal(tpl)}
                                            className="text-xs py-1 px-2.5 flex items-center gap-1"
                                        >
                                            <Edit3 className="w-3 h-3" />
                                            <span>Ubah</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => handleDelete(tpl)}
                                            className="text-xs py-1 px-2.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                        >
                                            <Trash2 className="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        ))
                    )}
                </div>

                {/* Pagination */}
                {templates.last_page > 1 && (
                    <div className="flex justify-center gap-1 mt-6">
                        {templates.links.map((link: any, idx: number) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                /* Label paginasi aman dari Laravel (misal: &laquo; Sebelumnya / Berikutnya &raquo;) dan bukan input pengguna */
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 text-xs rounded-md border ${
                                    link.active
                                        ? 'bg-emerald-600 text-white border-emerald-600 font-semibold'
                                        : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700'
                                } ${!link.url ? 'opacity-50 pointer-events-none' : ''}`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Form Tambah/Ubah Template */}
            <Modal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                title={editingTemplate ? `Ubah Template: ${editingTemplate.nama}` : 'Tambah Master Template Surat'}
            >
                <form onSubmit={handleFormSubmit} className="space-y-4 max-h-[80vh] overflow-y-auto pr-1">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Template <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            placeholder="Contoh: Nota Dinas Standar Internal"
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            required
                        />
                        {errors.nama && <p className="text-xs text-rose-500 mt-1">{errors.nama}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jenis Naskah <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={data.jenis}
                                onChange={(e) => setData('jenis', e.target.value)}
                                className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                <option value="nota_dinas">Nota Dinas</option>
                                <option value="surat_tugas">Surat Tugas</option>
                                <option value="edaran">Surat Edaran</option>
                                <option value="sk">Surat Keputusan (SK)</option>
                                <option value="undangan">Undangan</option>
                                <option value="biasa">Surat Biasa</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kode Template (Unik)
                            </label>
                            <input
                                type="text"
                                value={data.kode}
                                onChange={(e) => setData('kode', e.target.value)}
                                placeholder="Contoh: ND-STD"
                                className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none uppercase font-mono"
                            />
                            {errors.kode && <p className="text-xs text-rose-500 mt-1">{errors.kode}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Format Nomor Otomatis (Opsional)
                        </label>
                        <input
                            type="text"
                            value={data.format_nomor}
                            onChange={(e) => setData('format_nomor', e.target.value)}
                            placeholder="{urut}/ND/{kode_unit}/{bulan_romawi}/{tahun}"
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono text-xs"
                        />
                    </div>

                    {/* Variabel Helper Buttons */}
                    <div>
                        <span className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1">
                            <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                            <span>Variabel Otomatis (Klik untuk menyisipkan):</span>
                        </span>
                        <div className="flex flex-wrap gap-1 p-2 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 max-h-28 overflow-y-auto">
                            {Object.entries(supportedVariables).map(([varToken, desc]) => (
                                <button
                                    key={varToken}
                                    type="button"
                                    onClick={() => insertVariable(varToken)}
                                    title={desc}
                                    className="px-2 py-0.5 rounded text-[11px] font-mono bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 transition-colors"
                                >
                                    {varToken}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Isi Konten Baku Template (HTML / Teks) <span className="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                onClick={() => handlePreview(data.konten_default)}
                                className="text-xs text-emerald-600 hover:underline flex items-center gap-1"
                            >
                                <Eye className="w-3 h-3" />
                                <span>Uji Pratinjau</span>
                            </button>
                        </div>
                        <textarea
                            rows={8}
                            value={data.konten_default}
                            onChange={(e) => setData('konten_default', e.target.value)}
                            placeholder="Tuliskan format naskah dinas dan sisipkan variabel..."
                            className="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            required
                        />
                        {errors.konten_default && <p className="text-xs text-rose-500 mt-1">{errors.konten_default}</p>}
                    </div>

                    <div className="flex items-center gap-4 pt-1">
                        <label className="flex items-center gap-2 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                checked={data.use_institution_header}
                                onChange={(e) => setData('use_institution_header', e.target.checked)}
                                className="rounded text-emerald-600 focus:ring-emerald-500"
                            />
                            <span className="text-slate-700 dark:text-slate-300">Gunakan Kop Surat Resmi Instansi</span>
                        </label>

                        <label className="flex items-center gap-2 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded text-emerald-600 focus:ring-emerald-500"
                            />
                            <span className="text-slate-700 dark:text-slate-300">Aktif Digunakan</span>
                        </label>
                    </div>

                    <div className="pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" variant="primary" disabled={processing} className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium">
                            {processing ? 'Menyimpan...' : editingTemplate ? 'Simpan Perubahan' : 'Buat Template'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Pratinjau Naskah */}
            <Modal
                isOpen={previewModalOpen}
                onClose={() => setPreviewModalOpen(false)}
                title="📄 Pratinjau Naskah Dinas Terisi Variabel"
            >
                <div className="space-y-4">
                    {/* Kop Surat Instansi Mockup */}
                    <div className="p-3 bg-slate-50 dark:bg-slate-800/80 rounded border border-slate-200 dark:border-slate-700 text-center space-y-1">
                        <h4 className="font-bold text-sm tracking-wide uppercase text-slate-900 dark:text-white">
                            {institution?.name || 'POLITEKNIK CONTOH INDONESIA'}
                        </h4>
                        <p className="text-xs text-slate-500">
                            {institution?.address || 'Jl. Telekomunikasi No. 1, Terusan Buahbatu, Bandung'}
                        </p>
                        <hr className="border-t-2 border-slate-900 dark:border-slate-100 my-2" />
                    </div>

                    {/* Konten Terender */}
                    {previewLoading ? (
                        <div className="py-12 text-center text-xs text-slate-500">Memuat pratinjau...</div>
                    ) : (
                        <div
                            className="p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-xs leading-relaxed max-h-80 overflow-y-auto"
                            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(previewContent) }}
                        />
                    )}

                    <div className="flex justify-end">
                        <Button type="button" variant="secondary" onClick={() => setPreviewModalOpen(false)}>
                            Tutup
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
