import React from 'react';
import { clsx } from 'clsx';

export interface FormInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    helperText?: string;
    icon?: React.ReactNode;
}

export const FormInput = React.forwardRef<HTMLInputElement, FormInputProps>(
    ({ label, error, helperText, icon, className, id, required, ...props }, ref) => {
        const inputId = id || props.name || Math.random().toString(36).substring(7);

        return (
            <div className="w-full">
                {label && (
                    <label
                        htmlFor={inputId}
                        className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5"
                    >
                        {label} {required && <span className="text-rose-500">*</span>}
                    </label>
                )}
                <div className="relative rounded-lg shadow-xs">
                    {icon && (
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            {icon}
                        </div>
                    )}
                    <input
                        ref={ref}
                        id={inputId}
                        required={required}
                        className={clsx(
                            'block w-full rounded-lg border text-sm transition-all duration-150',
                            'focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500',
                            icon ? 'pl-9.5' : 'pl-3.5',
                            'pr-3.5 py-2.5',
                            error
                                ? 'border-rose-300 text-rose-900 placeholder-rose-300 focus:ring-rose-500 focus:border-rose-500'
                                : 'border-slate-300 text-slate-900 placeholder-slate-400 bg-white hover:border-slate-400',
                            props.disabled && 'bg-slate-50 text-slate-500 cursor-not-allowed',
                            className
                        )}
                        {...props}
                    />
                </div>
                {error && <p className="mt-1.5 text-xs text-rose-600 font-medium">{error}</p>}
                {helperText && !error && (
                    <p className="mt-1.5 text-xs text-slate-500">{helperText}</p>
                )}
            </div>
        );
    }
);

FormInput.displayName = 'FormInput';
