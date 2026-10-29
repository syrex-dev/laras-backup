import React from 'react';
import { useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { Card } from '@/Components/Card';
import { Button } from '@/Components/Button';
import { FormInput } from '@/Components/FormInput';
import { Select } from '@/Components/Select';
import { Save, Building2, BookOpen } from 'lucide-react';

interface ProfilProps {
    profile: {
        id: number;
        name: string;
        alias: string;
        address?: string | null;
        email?: string | null;
        phone?: string | null;
        website?: string | null;
        timezone: string;
        default_theme: string;
        terminology_dict: Record<string, string>;
    };
}

export default function Profil({ profile }: ProfilProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: profile.name || '',
        alias: profile.alias || '',
        address: profile.address || '',
        email: profile.email || '',
        phone: profile.phone || '',
        website: profile.website || '',
        timezone: profile.timezone || 'Asia/Jakarta',
        default_theme: profile.default_theme || 'emerald',
        terminology_dict: profile.terminology_dict || {
            unit_level_1: 'Jurusan',
            unit_level_2: 'Program Studi',
            lead_level_1: 'Direktur',
            lead_level_2: 'Ketua Jurusan',
            lead_level_3: 'Ketua Program Studi',
            admin_office: 'Tata Usaha',
        },
    });

    const handleTerminologyChange = (key: string, value: string) => {
        setData('terminology_dict', {
            ...data.terminology_dict,
            [key]: value,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/pengaturan/profil');
    };

    return (
        <AppLayout title="Profil Instansi & Kamus Istilah">
            <form onSubmit={handleSubmit} className="space-y-6 max-w-4xl">
                {/* Identitas Instansi */}
                <Card
                    title={
                        <div className="flex items-center gap-2">
                            <Building2 className="w-5 h-5 text-emerald-600" />
                            <span>Identitas Instansi</span>
                        </div>
                    }
                    subtitle="Informasi resmi instansi untuk kop surat dan identitas sistem tata usaha."
                >
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormInput
                            label="Nama Resmi Instansi"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <FormInput
                            label="Singkatan / Alias"
                            value={data.alias}
                            onChange={(e) => setData('alias', e.target.value)}
                            error={errors.alias}
                            required
                        />

                        <FormInput
                            label="Email Resmi Tata Usaha"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />

                        <FormInput
                            label="Nomor Telepon / Kontak"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />

                        <FormInput
                            label="Website Portal Resmi"
                            value={data.website}
                            onChange={(e) => setData('website', e.target.value)}
                            error={errors.website}
                        />

                        <Select
                            label="Zona Waktu"
                            value={data.timezone}
                            onChange={(e) => setData('timezone', e.target.value)}
                            error={errors.timezone}
                            options={[
                                { value: 'Asia/Jakarta', label: 'WIB (Asia/Jakarta)' },
                                { value: 'Asia/Makassar', label: 'WITA (Asia/Makassar)' },
                                { value: 'Asia/Jayapura', label: 'WIT (Asia/Jayapura)' },
                            ]}
                            required
                        />

                        <div className="col-span-1 md:col-span-2">
                            <FormInput
                                label="Alamat Kantor Lengkap"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                error={errors.address}
                            />
                        </div>
                    </div>
                </Card>

                {/* Kamus Istilah Dinamis (Zero Hardcode) */}
                <Card
                    title={
                        <div className="flex items-center gap-2">
                            <BookOpen className="w-5 h-5 text-emerald-600" />
                            <span>Kamus Istilah Dinamis (*Zero Hardcoded Terms*)</span>
                        </div>
                    }
                    subtitle="Ubah label jabatan dan unit di bawah ini; seluruh teks di aplikasi langsung berubah secara dinamis."
                >
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormInput
                            label="Unit Kerja Utama (Level 1)"
                            helperText="Label untuk jurusan/bagian. Misal: 'Jurusan' atau 'Bagian'"
                            value={data.terminology_dict.unit_level_1 || ''}
                            onChange={(e) => handleTerminologyChange('unit_level_1', e.target.value)}
                            required
                        />

                        <FormInput
                            label="Sub-Unit Kerja (Level 2)"
                            helperText="Label untuk prodi/seksi. Misal: 'Program Studi' atau 'Subbagian'"
                            value={data.terminology_dict.unit_level_2 || ''}
                            onChange={(e) => handleTerminologyChange('unit_level_2', e.target.value)}
                            required
                        />

                        <FormInput
                            label="Pimpinan Tertinggi"
                            helperText="Label jabatan pucuk pimpinan. Misal: 'Direktur', 'Rektor', atau 'Ketua'"
                            value={data.terminology_dict.lead_level_1 || ''}
                            onChange={(e) => handleTerminologyChange('lead_level_1', e.target.value)}
                            required
                        />

                        <FormInput
                            label="Pimpinan Unit Utama"
                            helperText="Label pimpinan unit level 1. Misal: 'Ketua Jurusan' atau 'Kepala Bagian'"
                            value={data.terminology_dict.lead_level_2 || ''}
                            onChange={(e) => handleTerminologyChange('lead_level_2', e.target.value)}
                            required
                        />

                        <FormInput
                            label="Pimpinan Sub-Unit"
                            helperText="Misal: 'Ketua Program Studi' atau 'Kepala Seksi'"
                            value={data.terminology_dict.lead_level_3 || ''}
                            onChange={(e) => handleTerminologyChange('lead_level_3', e.target.value)}
                        />

                        <FormInput
                            label="Kantor Tata Usaha / Administrasi"
                            helperText="Misal: 'Tata Usaha' atau 'Sekretariat'"
                            value={data.terminology_dict.admin_office || ''}
                            onChange={(e) => handleTerminologyChange('admin_office', e.target.value)}
                        />
                    </div>
                </Card>

                <div className="flex justify-end">
                    <Button
                        type="submit"
                        variant="primary"
                        loading={processing}
                        icon={<Save className="w-4 h-4" />}
                    >
                        Simpan Perubahan Profil & Istilah
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
