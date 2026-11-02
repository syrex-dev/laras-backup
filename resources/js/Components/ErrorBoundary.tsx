import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    public state: State = {
        hasError: false,
        error: null,
    };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('Uncaught React ErrorBoundary error:', error, errorInfo);
    }

    private handleReload = () => {
        window.location.reload();
    };

    private handleGoHome = () => {
        window.location.href = '/dashboard';
    };

    public render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4">
                    <div className="max-w-md w-full bg-white rounded-2xl border border-slate-200/80 shadow-xl p-8 text-center space-y-5">
                        <div className="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto shadow-inner">
                            <AlertTriangle className="w-8 h-8" />
                        </div>

                        <div>
                            <h2 className="text-xl font-bold text-slate-900 tracking-tight">
                                Terjadi Kesalahan Tampilan
                            </h2>
                            <p className="text-xs text-slate-500 mt-2 leading-relaxed">
                                Komponen antarmuka mengalami kendala tak terduga. Data Anda tetap aman di basis data server.
                            </p>
                        </div>

                        {this.state.error && (
                            <div className="text-left bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-600 overflow-x-auto max-h-32">
                                {this.state.error.message}
                            </div>
                        )}

                        <div className="flex items-center justify-center gap-3 pt-2">
                            <button
                                type="button"
                                onClick={this.handleReload}
                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors"
                            >
                                <RefreshCw className="w-4 h-4" />
                                <span>Muat Ulang</span>
                            </button>
                            <button
                                type="button"
                                onClick={this.handleGoHome}
                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-xs"
                            >
                                <Home className="w-4 h-4" />
                                <span>Ke Dashboard</span>
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
