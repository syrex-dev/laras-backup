import React from 'react';
import { clsx } from 'clsx';

export interface BadgeProps {
    children: React.ReactNode;
    variant?: 'primary' | 'success' | 'danger' | 'warning' | 'info' | 'muted';
    className?: string;
    dot?: boolean;
}

export const Badge: React.FC<BadgeProps> = ({
    children,
    variant = 'muted',
    className,
    dot = false,
}) => {
    const variants = {
        primary: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        success: 'bg-teal-50 text-teal-700 border-teal-200',
        danger: 'bg-rose-50 text-rose-700 border-rose-200',
        warning: 'bg-amber-50 text-amber-700 border-amber-200',
        info: 'bg-sky-50 text-sky-700 border-sky-200',
        muted: 'bg-slate-100 text-slate-600 border-slate-200',
    };

    const dotColors = {
        primary: 'bg-emerald-500',
        success: 'bg-teal-500',
        danger: 'bg-rose-500',
        warning: 'bg-amber-500',
        info: 'bg-sky-500',
        muted: 'bg-slate-400',
    };

    return (
        <span
            className={clsx(
                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border select-none',
                variants[variant],
                className
            )}
        >
            {dot && <span className={clsx('w-1.5 h-1.5 rounded-full', dotColors[variant])} />}
            {children}
        </span>
    );
};
