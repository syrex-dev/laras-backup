import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Badge } from '@/Components/Badge';
import { Button } from '@/Components/Button';
import { PenTool, Upload, CheckCircle2, AlertCircle, FileCheck } from 'lucide-react';

interface TandaTanganProps {
    tandaTangan?: {
        id: number;
        path_tanda_tangan?: string;
    } | null;
}

export default function TandaTangan({ tandaTangan }: TandaTanganProps) {
    const { data, setData, post, processing, errors } = useForm({
        tanda_tangan: null as File | null,
    });

    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setData('tanda_tangan', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/profil/tanda-tangan', {
            forceFormData: true,
            onSuccess: () => {
                setPreviewUrl(null);
            },
        });
    };

    return (
        <AppLayout title="Spesimen Tanda Tangan">
            <Head title="Tanda Tangan Digital" />

            <div className="space-y-6 max-w-4xl mx-auto">
                {/* Header */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div className="flex items-center gap-2 text-xs font-semibold text-emerald-600 mb-1 tracking-wide uppercase">
                        <PenTool className="w-4 h-4" />
                        <span>Validasi & Pengesahan</span>
                    </div>
                    <h1 className="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                        Spesimen Tanda Tangan Digital Pimpinan
                    </h1>
                    <p className="text-xs md:text-sm text-slate-500 mt-1">
                        Kelola spesimen tanda tangan resmi pimpinan untuk pembubuhan otomatis pada naskah dinas keluar yang disetujui.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Spesimen Aktif */}
                    <Card
                        title="Spesimen Aktif"
                        subtitle="Pratinjau tanda tangan yang saat ini tersimpan"
                    >
                        <div className="flex flex-col items-center justify-center min-h-[220px] p-6 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            {tandaTangan?.path_tanda_tangan ? (
                                <div className="space-y-4">
                                    <div className="p-4 bg-white rounded-xl shadow-xs border border-slate-200 inline-block max-w-[260px]">
                                        <img
                                            src={`/storage/${tandaTangan.path_tanda_tangan}`}
                                            alt="Spesimen Tanda Tangan"
                                            className="max-h-28 object-contain mx-auto"
                                        />
                                    </div>
                                    <div>
                                        <Badge variant="success" dot>
                                            Spesimen Aktif Digunakan
                                        </Badge>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-slate-400 space-y-2">
                                    <FileCheck className="w-10 h-10 mx-auto text-slate-300" />
                                    <p className="text-sm font-medium">Belum ada spesimen tanda tangan</p>
                                    <p className="text-xs text-slate-400">Unggah tanda tangan resmi Anda pada formulir di sebelah kanan.</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Form Unggah */}
                    <Card
                        title="Unggah Spesimen Baru"
                        subtitle="Format PNG atau JPG dengan latar belakang transparan (maks. 2MB)"
                    >
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-2">
                                    Pilih Berkas Tanda Tangan
                                </label>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg"
                                    onChange={handleFileChange}
                                    className="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                                />
                                {errors.tanda_tangan && (
                                    <p className="text-xs text-rose-600 mt-1 flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5" />
                                        <span>{errors.tanda_tangan}</span>
                                    </p>
                                )}
                            </div>

                            {previewUrl && (
                                <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                                    <p className="text-xs font-semibold text-slate-600 mb-2">Pratinjau Pilihan Baru:</p>
                                    <img
                                        src={previewUrl}
                                        alt="Pratinjau Baru"
                                        className="max-h-24 object-contain mx-auto"
                                    />
                                </div>
                            )}

                            <Button
                                type="submit"
                                variant="primary"
                                disabled={processing || !data.tanda_tangan}
                                className="w-full gap-2"
                            >
                                <Upload className="w-4 h-4" />
                                <span>{processing ? 'Menyimpan...' : 'Simpan Tanda Tangan'}</span>
                            </Button>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
