import React from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { FormInput } from '@/Components/FormInput';
import { Button } from '@/Components/Button';
import { PageProps } from '@/types';
import { LogIn } from 'lucide-react';

interface DemoAccount {
    role: string;
    email: string;
}

interface DemoAccountsInfo {
    password: string;
    accounts: DemoAccount[];
}

interface LoginProps {
    demoAccounts?: DemoAccountsInfo | null;
}

export default function Login({ demoAccounts = null }: LoginProps) {
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
                    placeholder="nama@instansi.id"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoComplete="username"
                    required
                />

                <FormInput
                    label="Kata Sandi"
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
                {demoAccounts && demoAccounts.accounts && demoAccounts.accounts.length > 0 && (
                    <div className="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-100 text-[11px] text-slate-500 space-y-1">
                        <p className="font-semibold text-slate-700">Akun Percontohan Demo (Password: {demoAccounts.password}):</p>
                        {demoAccounts.accounts.map((acc) => (
                            <p key={acc.email}>
                                • {acc.role}: <span className="font-mono text-slate-700">{acc.email}</span>
                            </p>
                        ))}
                    </div>
                )}
            </form>
        </GuestLayout>
    );
}
