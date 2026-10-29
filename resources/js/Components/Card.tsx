import React from 'react';
import { clsx } from 'clsx';

export interface CardProps {
    children: React.ReactNode;
    className?: string;
    title?: React.ReactNode;
    subtitle?: React.ReactNode;
    action?: React.ReactNode;
    footer?: React.ReactNode;
}

export const Card: React.FC<CardProps> = ({
    children,
    className,
    title,
    subtitle,
    action,
    footer,
}) => {
    return (
        <div
            className={clsx(
                'bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden',
                className
            )}
        >
            {(title || action) && (
                <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                    <div>
                        {title && <h3 className="font-semibold text-slate-800 text-base">{title}</h3>}
                        {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
                    </div>
                    {action && <div>{action}</div>}
                </div>
            )}
            <div className="p-5">{children}</div>
            {footer && (
                <div className="px-5 py-3 bg-slate-50/75 border-t border-slate-100 flex items-center justify-between">
                    {footer}
                </div>
            )}
        </div>
    );
};
