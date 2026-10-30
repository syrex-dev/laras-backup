import React from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { FormInput } from '@/Components/FormInput';
import { Button } from '@/Components/Button';
import { PageProps } from '@/types';
import { LogIn } from 'lucide-react';

interface LoginProps {
    showDemoAccounts?: boolean;
}

export default function Login({ showDemoAccounts = false }: LoginProps) {
    const { props } = usePage<PageProps>();
    const { institution } = props;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <GuestLayout title="Masuk ke Sistem E-Office">
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <h2 className="text-base font-bold text-slate-900">Selamat Datang</h2>
                    <p className="text-xs text-slate-500 mt-0.5">
                        Silakan masukkan kredensial akun Anda untuk mengakses naskah dan tata usaha.
                    </p>
                </div>

                <FormInput
                    label="Alamat Email"
                    type="email"
                    name="email"
                    placeholder="nama@contoh.ac.id"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="username"
                    required
                />

                <FormInput
                    label="Kata Sandi (Password)"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoComplete="current-password"
                    required
                />

                <div className="flex items-center justify-between text-xs pt-1">
                    <label className="flex items-center gap-2 cursor-pointer select-none text-slate-600">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                        />
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <div className="pt-2">
                    <Button
                        type="submit"
                        variant="primary"
                        loading={processing}
                        className="w-full"
                        icon={<LogIn className="w-4 h-4" />}
                    >
                        Masuk ke Sistem
                    </Button>
                </div>

                {/* Info Bantuan Demo */}
                {showDemoAccounts && (
                    <div className="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-100 text-[11px] text-slate-500 space-y-1">
                        <p className="font-semibold text-slate-700">Akun Percontohan Demo (Password: password):</p>
                        <p>• Admin: <span className="font-mono text-slate-700">admin@contoh.ac.id</span></p>
                        <p>• Direktur: <span className="font-mono text-slate-700">direktur@contoh.ac.id</span></p>
                        <p>• Staf TU: <span className="font-mono text-slate-700">tu@contoh.ac.id</span></p>
                    </div>
                )}
            </form>
        </GuestLayout>
    );
}
