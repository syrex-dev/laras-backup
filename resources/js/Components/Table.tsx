import React from 'react';
import { clsx } from 'clsx';
import { Inbox } from 'lucide-react';

export const Table: React.FC<{ children: React.ReactNode; className?: string }> = ({
    children,
    className,
}) => {
    return (
        <div className="w-full overflow-hidden border border-slate-200 rounded-xl bg-white shadow-xs">
            <div className="overflow-x-auto">
                <table className={clsx('w-full text-left border-collapse text-sm', className)}>
                    {children}
                </table>
            </div>
        </div>
    );
};

export const TableHead: React.FC<{ children: React.ReactNode; className?: string }> = ({
    children,
    className,
}) => (
    <thead className={clsx('bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider', className)}>
        {children}
    </thead>
);

export const TableBody: React.FC<{ children: React.ReactNode; className?: string }> = ({
    children,
    className,
}) => (
    <tbody className={clsx('divide-y divide-slate-100 text-slate-700', className)}>
        {children}
    </tbody>
);

export const TableRow: React.FC<{ children: React.ReactNode; className?: string; onClick?: () => void }> = ({
    children,
    className,
    onClick,
}) => (
    <tr
        onClick={onClick}
        className={clsx(
            'transition-colors duration-100',
            onClick ? 'cursor-pointer hover:bg-slate-50/80' : 'hover:bg-slate-50/50',
            className
        )}
    >
        {children}
    </tr>
);

export const TableHeaderCell: React.FC<{ children: React.ReactNode; className?: string }> = ({
    children,
    className,
}) => <th className={clsx('py-3.5 px-4 font-semibold select-none', className)}>{children}</th>;

export const TableCell: React.FC<{ children: React.ReactNode; className?: string; colSpan?: number }> = ({
    children,
    className,
    colSpan,
}) => (
    <td colSpan={colSpan} className={clsx('py-3.5 px-4 align-middle text-slate-700', className)}>
        {children}
    </td>
);

export const TableEmpty: React.FC<{ message?: string; colSpan: number }> = ({
    message = 'Tidak ada data untuk ditampilkan',
    colSpan,
}) => (
    <tr>
        <td colSpan={colSpan} className="py-12 text-center text-slate-400">
            <div className="flex flex-col items-center justify-center gap-2">
                <Inbox className="w-8 h-8 text-slate-300 stroke-[1.5]" />
                <p className="text-sm">{message}</p>
            </div>
        </td>
    </tr>
);
