import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { Card } from '@/Components/Card';
import { Building2, BookOpen, ShieldCheck, ArrowRight, ArrowLeft, Check } from 'lucide-react';

interface WizardProps {
    initialProfile: {
        name: string;
        alias: string;
        timezone: string;
        default_theme: string;
        terminology_dict: Record<string, string>;
    };
}

export default function Wizard({ initialProfile }: WizardProps) {
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm({
        institution_name: initialProfile.name || '',
        institution_alias: initialProfile.alias || '',
        timezone: initialProfile.timezone || 'Asia/Jakarta',
        default_theme: initialProfile.default_theme || 'emerald',
        terminology_dict: initialProfile.terminology_dict || {
            unit_level_1: 'Jurusan',
            unit_level_2: 'Program Studi',
            lead_level_1: 'Direktur',
            lead_level_2: 'Ketua Jurusan',
            lead_level_3: 'Ketua Program Studi',
            admin_office: 'Tata Usaha',
        },
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });

    const handleTerminologyChange = (key: string, value: string) => {
        setData('terminology_dict', {
            ...data.terminology_dict,
            [key]: value,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/setup');
    };

    return (
        <GuestLayout title="Wizard Pemasangan Awal Instansi">
            {/* Step Progress Bar */}
            <div className="mb-8">
                <div className="flex items-center justify-between">
                    {[
                        { num: 1, label: 'Instansi', icon: Building2 },
                        { num: 2, label: 'Kamus Istilah', icon: BookOpen },
                        { num: 3, label: 'Admin Utama', icon: ShieldCheck },
                    ].map((s) => {
                        const Icon = s.icon;
                        const isDone = step > s.num;
                        const isCurrent = step === s.num;
                        return (
                            <div key={s.num} className="flex flex-col items-center flex-1">
                                <div
                                    className={`w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-colors duration-200 ${
                                        isDone
                                            ? 'bg-emerald-600 text-white'
                                            : isCurrent
                                            ? 'bg-emerald-100 text-emerald-800 border-2 border-emerald-600'
                                            : 'bg-slate-100 text-slate-400'
                                    }`}
                                >
                                    {isDone ? <Check className="w-4 h-4" /> : <Icon className="w-4 h-4" />}
                                </div>
                                <span className={`text-[11px] mt-1.5 font-medium ${isCurrent ? 'text-slate-800 font-bold' : 'text-slate-400'}`}>
                                    {s.label}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
                {/* Step 1: Identitas Instansi */}
                {step === 1 && (
                    <div className="space-y-4 animate-in fade-in duration-150">
                        <div className="border-b border-slate-100 pb-2 mb-4">
                            <h2 className="text-sm font-bold text-slate-800">1. Data Profil Instansi</h2>
                            <p className="text-xs text-slate-500">Konfigurasi nama resmi instansi dan zona waktu operasional.</p>
                        </div>

                        <FormInput
                            label="Nama Resmi Instansi"
                            placeholder="Contoh: Politeknik Negeri Bandung"
                            value={data.institution_name}
                            onChange={(e) => setData('institution_name', e.target.value)}
                            error={errors.institution_name}
                            required
                        />

                        <FormInput
                            label="Singkatan / Alias"
                            placeholder="Contoh: POLBAN"
                            value={data.institution_alias}
                            onChange={(e) => setData('institution_alias', e.target.value)}
                            error={errors.institution_alias}
                            required
                        />

                        <Select
                            label="Zona Waktu Operasional"
                            value={data.timezone}
                            onChange={(e) => setData('timezone', e.target.value)}
                            error={errors.timezone}
                            options={[
                                { value: 'Asia/Jakarta', label: 'WIB (Waktu Indonesia Barat)' },
                                { value: 'Asia/Makassar', label: 'WITA (Waktu Indonesia Tengah)' },
                                { value: 'Asia/Jayapura', label: 'WIT (Waktu Indonesia Timur)' },
                            ]}
                            required
                        />

                        <div className="pt-4 flex justify-end">
                            <Button
                                type="button"
                                variant="primary"
                                onClick={() => setStep(2)}
                                disabled={!data.institution_name || !data.institution_alias}
                                icon={<ArrowRight className="w-4 h-4" />}
                            >
                                Lanjut: Kamus Istilah
                            </Button>
                        </div>
                    </div>
                )}

                {/* Step 2: Kamus Istilah Dinamis */}
                {step === 2 && (
                    <div className="space-y-4 animate-in fade-in duration-150">
                        <div className="border-b border-slate-100 pb-2 mb-4">
                            <h2 className="text-sm font-bold text-slate-800">2. Kamus Istilah & Jabatan (Zero Hardcode)</h2>
                            <p className="text-xs text-slate-500">
                                Sesuaikan istilah jabatan dan unit dengan birokrasi instansi Anda (misal: "Jurusan" atau "Bagian").
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <FormInput
                                label="Unit Kerja Utama (Level 1)"
                                value={data.terminology_dict.unit_level_1 || ''}
                                onChange={(e) => handleTerminologyChange('unit_level_1', e.target.value)}
                                placeholder="Jurusan / Bagian / Divisi"
                                required
                            />
                            <FormInput
                                label="Sub-Unit Kerja (Level 2)"
                                value={data.terminology_dict.unit_level_2 || ''}
                                onChange={(e) => handleTerminologyChange('unit_level_2', e.target.value)}
                                placeholder="Program Studi / Seksi"
                                required
                            />
                            <FormInput
                                label="Pimpinan Tertinggi"
                                value={data.terminology_dict.lead_level_1 || ''}
                                onChange={(e) => handleTerminologyChange('lead_level_1', e.target.value)}
                                placeholder="Direktur / Rektor / Ketua"
                                required
                            />
                            <FormInput
                                label="Pimpinan Unit Utama"
                                value={data.terminology_dict.lead_level_2 || ''}
                                onChange={(e) => handleTerminologyChange('lead_level_2', e.target.value)}
                                placeholder="Ketua Jurusan / Kepala Bagian"
                                required
                            />
                        </div>

                        <div className="pt-4 flex justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setStep(1)}
                                icon={<ArrowLeft className="w-4 h-4" />}
                            >
                                Kembali
                            </Button>
                            <Button
                                type="button"
                                variant="primary"
                                onClick={() => setStep(3)}
                                icon={<ArrowRight className="w-4 h-4" />}
                            >
                                Lanjut: Akun Admin
                            </Button>
                        </div>
                    </div>
                )}

                {/* Step 3: Akun Administrator Utama Pertama */}
                {step === 3 && (
                    <div className="space-y-4 animate-in fade-in duration-150">
                        <div className="border-b border-slate-100 pb-2 mb-4">
                            <h2 className="text-sm font-bold text-slate-800">3. Akun Administrator Instansi Pertama</h2>
                            <p className="text-xs text-slate-500">Akun ini memiliki hak akses penuh untuk mengelola pengguna dan konfigurasi.</p>
                        </div>

                        <FormInput
                            label="Nama Lengkap Admin"
                            placeholder="Contoh: Administrator Sistem"
                            value={data.admin_name}
                            onChange={(e) => setData('admin_name', e.target.value)}
                            error={errors.admin_name}
                            required
                        />

                        <FormInput
                            label="Alamat Email Admin"
                            type="email"
                            placeholder="admin@poltek.ac.id"
                            value={data.admin_email}
                            onChange={(e) => setData('admin_email', e.target.value)}
                            error={errors.admin_email}
                            required
                        />

                        <FormInput
                            label="Kata Sandi (Password)"
                            type="password"
                            placeholder="Minimal 6 karakter"
                            value={data.admin_password}
                            onChange={(e) => setData('admin_password', e.target.value)}
                            error={errors.admin_password}
                            required
                        />

                        <div className="pt-4 flex justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setStep(2)}
                                icon={<ArrowLeft className="w-4 h-4" />}
                            >
                                Kembali
                            </Button>
                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                                icon={<Check className="w-4 h-4" />}
                            >
                                Selesaikan Pemasangan
                            </Button>
                        </div>
                    </div>
                )}
            </form>
        </GuestLayout>
    );
}
