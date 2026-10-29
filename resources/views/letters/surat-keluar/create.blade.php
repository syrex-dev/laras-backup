@extends('layouts.app')

@section('title', 'Buat Surat Keluar')

@section('content')
<div x-data="suratDrafting()" class="space-y-6">
    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
                <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
                <span>/</span>
                <a href="{{ route('surat-keluar.index') }}" style="color:var(--text-muted); text-decoration:none;">Surat Keluar</a>
                <span>/</span>
                <span style="color:var(--color-primary); font-weight:600;">Buat</span>
            </div>
            <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
                Drafting Surat Keluar
            </h1>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
                Otomasi penyusunan dokumen surat keluar dengan live preview &amp; templat resmi.
            </p>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <template x-if="saving">
                <span style="font-size:0.78rem; color:var(--text-muted);">Menghubungkan ke server...</span>
            </template>
            <div style="display:flex; align-items:center; gap:4px; background:var(--bg-surface-2); padding:3px; border-radius:var(--radius-base); border:1px solid var(--border-color);">
                <button type="button" @click="viewMode = 'split'" :class="viewMode === 'split' ? 'btn-primary' : 'btn-ghost'" class="btn btn-sm" style="padding:4px 10px; font-size:0.75rem;">
                    Split Preview
                </button>
                <button type="button" @click="viewMode = 'full'" :class="viewMode === 'full' ? 'btn-primary' : 'btn-ghost'" class="btn btn-sm" style="padding:4px 10px; font-size:0.75rem;">
                    Form Saja
                </button>
            </div>
        </div>
    </div>

    {{-- Main Area --}}
    <div style="display:flex; flex-direction:column; gap:24px;" class="lg:flex-row">
        {{-- Form Section --}}
        <div :style="viewMode === 'split' ? 'flex: 1 1 55%; min-width: 0;' : 'flex: 1 1 100%; min-width: 0;'">
            <form action="{{ route('surat-keluar.store') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Formulir Surat Keluar</h2>
                        <span class="badge badge-primary">Draft Baru</span>
                    </div>

                    <div class="card-body" style="display:flex; flex-direction:column; gap:18px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                            {{-- Klasifikasi --}}
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Klasifikasi Surat <span style="color:var(--color-danger);">*</span></label>
                                <select name="klasifikasi_id" required x-model="formData.klasifikasi_id" @change="debouncedPreview()" class="form-control">
                                    <option value="">— Pilih Klasifikasi —</option>
                                    @foreach($klasifikasi as $kl)
                                        <option value="{{ $kl->id }}">[{{ $kl->kode_klasifikasi }}] {{ $kl->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Templat --}}
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Templat Surat <span style="color:var(--color-danger);">*</span></label>
                                <select name="templat_surat_id" required x-model="formData.templat_surat_id" @change="debouncedPreview()" class="form-control">
                                    <option value="">— Pilih Templat —</option>
                                    @foreach($templates as $t)
                                        <option value="{{ $t->id }}">{{ $t->judul ?? ('Template ' . $t->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                            {{-- Tanggal Surat --}}
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Tanggal Surat <span style="color:var(--color-danger);">*</span></label>
                                <input type="date" name="tanggal_surat" required x-model="formData.tanggal_surat" @input="debouncedPreview()" class="form-control">
                            </div>

                            {{-- Penyetuju --}}
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Penyetuju (Pimpinan) <span style="color:var(--color-danger);">*</span></label>
                                <select name="penyetuju_id" required x-model="formData.penyetuju_id" @change="debouncedPreview()" class="form-control">
                                    <option value="">— Pilih Pimpinan —</option>
                                    @foreach($pimpinan as $p)
                                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Tujuan --}}
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Penerima / Tujuan Surat <span style="color:var(--color-danger);">*</span></label>
                            <input type="text" name="tujuan" required x-model="formData.tujuan" @input="debouncedPreview()" placeholder="Contoh: Rektor Politeknik Mardira Indonesia" class="form-control">
                        </div>

                        {{-- Perihal --}}
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Perihal <span style="color:var(--color-danger);">*</span></label>
                            <input type="text" name="perihal" required x-model="formData.perihal" @input="debouncedPreview()" placeholder="Perihal atau judul surat resmi..." class="form-control">
                        </div>

                        {{-- Lampiran --}}
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Keterangan Lampiran <span style="font-weight:normal; color:var(--text-muted);">(opsional)</span></label>
                            <input type="text" name="lampiran" x-model="formData.lampiran" @input="debouncedPreview()" placeholder="Contoh: 1 Berkas, Lampiran I, -" class="form-control">
                        </div>

                        {{-- Isi Surat --}}
                        <div class="form-group" style="margin:0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <label class="form-label" style="margin:0;">Isi / Narasi Surat <span style="color:var(--color-danger);">*</span></label>
                                <span style="font-size:0.72rem; color:var(--text-muted);">Markdown Supported</span>
                            </div>
                            <textarea name="isi" rows="10" x-model="formData.isi" @input="debouncedPreview()" placeholder="Tuliskan isi surat secara lengkap di sini..." class="form-control" style="font-family:inherit;"></textarea>
                        </div>
                    </div>

                    <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <a href="{{ route('surat-keluar.index') }}" class="btn btn-ghost btn-sm">
                            &larr; Batal &amp; Kembali
                        </a>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" @click="refreshPreview(true)" class="btn btn-outline btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <span>Perbarui Preview</span>
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span>Simpan &amp; Ajukan Draft</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Preview Section --}}
        <div x-show="viewMode === 'split'" style="flex: 1 1 45%; min-width: 0;" class="hidden lg:block">
            <div class="card" style="height: calc(100vh - 180px); display:flex; flex-direction:column; position:sticky; top:84px;">
                <div class="card-header">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:8px; height:8px; border-radius:50%; background:var(--color-success); display:inline-block;"></span>
                        <h3 class="card-title" style="font-size:0.88rem;">Live PDF Preview Engine</h3>
                    </div>
                    <button type="button" @click="refreshPreview(true)" class="btn-icon" title="Refresh Preview">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 4v6h-6"></path>
                            <path d="M1 20v-6h6"></path>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                </div>
                <div style="flex:1; position:relative; background:var(--bg-surface-2);">
                    <template x-if="loadingPreview">
                        <div style="position:absolute; inset:0; z-index:10; background:rgba(0,0,0,0.3); backdrop-filter:blur(2px); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px;">
                            <span style="color:white; font-size:0.8rem; font-weight:600;">Memuat preview PDF...</span>
                        </div>
                    </template>
                    <iframe id="pdf-preview" :src="previewUrl" style="width:100%; height:100%; border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function suratDrafting() {
        return {
            viewMode: 'split',
            loadingPreview: false,
            saving: false,
            previewUrl: '',
            formData: {
                klasifikasi_id: '{{ old('klasifikasi_id', '') }}',
                templat_surat_id: '{{ old('templat_surat_id', '') }}',
                tanggal_surat: '{{ old('tanggal_surat', date('Y-m-d')) }}',
                tujuan: '{{ old('tujuan', '') }}',
                penyetuju_id: '{{ old('penyetuju_id', '') }}',
                perihal: '{{ old('perihal', '') }}',
                lampiran: '{{ old('lampiran', '') }}',
                isi: '{{ old('isi', '') }}',
            },

            init() {
                this.debouncedPreview = this.debounce(() => this.refreshPreview(false), 800);
                this.refreshPreview(false);
            },

            async refreshPreview(isManual = false) {
                const selectEl = document.querySelector('select[name="templat_surat_id"]');
                if (selectEl) {
                    this.formData.templat_surat_id = selectEl.value;
                }

                if (!this.formData.templat_surat_id) {
                    if (isManual) alert('Silakan pilih Templat Surat terlebih dahulu.');
                    return;
                }

                if (!this.formData.tujuan && !this.formData.perihal) return;

                this.loadingPreview = true;
                try {
                    const response = await fetch('{{ route('surat-keluar.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.formData)
                    });

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/pdf') || contentType.includes('application/octet-stream')) {
                        const blob = await response.blob();
                        this.previewUrl = URL.createObjectURL(blob);
                    } else {
                        const text = await response.text();
                        const blob = new Blob([text], { type: 'text/html' });
                        this.previewUrl = URL.createObjectURL(blob);
                    }
                } catch (error) {
                    console.error('Preview error:', error);
                } finally {
                    this.loadingPreview = false;
                }
            },

            debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }
        }
    }
</script>
@endsection
