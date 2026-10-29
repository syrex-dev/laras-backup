import React from 'react';
import { clsx } from 'clsx';

export interface SelectOption {
    value: string | number;
    label: string;
}

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    helperText?: string;
    options: SelectOption[];
    placeholder?: string;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
    ({ label, error, helperText, options, placeholder, className, id, required, ...props }, ref) => {
        const selectId = id || props.name || Math.random().toString(36).substring(7);

        return (
            <div className="w-full">
                {label && (
                    <label
                        htmlFor={selectId}
                        className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5"
                    >
                        {label} {required && <span className="text-rose-500">*</span>}
                    </label>
                )}
                <div className="relative rounded-lg shadow-xs">
                    <select
                        ref={ref}
                        id={selectId}
                        required={required}
                        className={clsx(
                            'block w-full rounded-lg border text-sm transition-all duration-150',
                            'focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500',
                            'px-3.5 py-2.5 bg-white cursor-pointer',
                            error
                                ? 'border-rose-300 text-rose-900 focus:ring-rose-500 focus:border-rose-500'
                                : 'border-slate-300 text-slate-900 hover:border-slate-400',
                            props.disabled && 'bg-slate-50 text-slate-500 cursor-not-allowed',
                            className
                        )}
                        {...props}
                    >
                        {placeholder && <option value="">{placeholder}</option>}
                        {options.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                </div>
                {error && <p className="mt-1.5 text-xs text-rose-600 font-medium">{error}</p>}
                {helperText && !error && (
                    <p className="mt-1.5 text-xs text-slate-500">{helperText}</p>
                )}
            </div>
        );
    }
);

Select.displayName = 'Select';
