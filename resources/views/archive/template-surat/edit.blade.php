@extends('layouts.app')

@section('title', 'Edit Templat Surat')

@section('content')
<div style="max-width:820px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="{{ route('template-surat.index') }}" style="color:var(--text-muted); text-decoration:none;">Templat Surat</a>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Edit</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Edit Templat Surat
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Perbarui konfigurasi dan struktur templat: <strong style="color:var(--color-primary);">{{ $templateSurat->nama_templat }}</strong>
        </p>
    </div>

    {{-- Card Form --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Perbarui Data Templat</h2>
            <span class="badge badge-warning">Edit Mode</span>
        </div>

        @if($errors->any())
        <div style="padding:16px 20px 0;">
            <div class="alert alert-danger" style="margin:0;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('template-surat.update', $templateSurat->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card-body" style="display:flex; flex-direction:column; gap:20px;">
                {{-- Nama Templat --}}
                <div class="form-group" style="margin:0;">
                    <label for="nama_templat" class="form-label">
                        Nama Templat <span style="color:var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="nama_templat" name="nama_templat"
                        value="{{ old('nama_templat', $templateSurat->nama_templat) }}" required
                        placeholder="Contoh: Surat Keputusan HIMASE"
                        class="form-control">
                    @error('nama_templat') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                {{-- Pilihan Tindakan Berkas --}}
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tindakan Berkas / Struktur</label>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                        <label style="border:1px solid var(--border-color); border-radius:var(--radius-base); padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; background:var(--bg-surface-2);">
                            <input type="radio" name="tipe_input" value="keep" onclick="toggleInputMode('keep')" checked style="accent-color:var(--color-primary);">
                            <div>
                                <span style="display:block; font-size:0.875rem; font-weight:600; color:var(--text-title);">Tetap Gunakan</span>
                                <span style="display:block; font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Simpan struktur berkas saat ini</span>
                            </div>
                        </label>

                        <label style="border:1px solid var(--border-color); border-radius:var(--radius-base); padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; background:var(--bg-surface-2);">
                            <input type="radio" name="tipe_input" value="upload" onclick="toggleInputMode('upload')" style="accent-color:var(--color-primary);">
                            <div>
                                <span style="display:block; font-size:0.875rem; font-weight:600; color:var(--text-title);">Unggah Berkas Baru</span>
                                <span style="display:block; font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Ganti dengan file HTML baru</span>
                            </div>
                        </label>

                        <label style="border:1px solid var(--border-color); border-radius:var(--radius-base); padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; background:var(--bg-surface-2);">
                            <input type="radio" name="tipe_input" value="textarea" onclick="toggleInputMode('textarea')" style="accent-color:var(--color-primary);">
                            <div>
                                <span style="display:block; font-size:0.875rem; font-weight:600; color:var(--text-title);">Edit Lewat Teks</span>
                                <span style="display:block; font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Modifikasi kode HTML</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Info File Terpasang --}}
                <div id="keep-mode-container">
                    <div class="alert alert-info" style="margin:0;">
                        <span>Berkas aktif saat ini:</span>
                        <code class="font-mono badge badge-info" style="margin-left:6px;">{{ $templateSurat->path_file }}</code>
                    </div>
                </div>

                {{-- Upload New Mode --}}
                <div id="upload-mode-container" class="form-group" style="margin:0; display:none;">
                    <label class="form-label">Unggah Berkas HTML Baru (.html)</label>
                    <div style="border:2px dashed var(--border-color); border-radius:var(--radius-base); padding:28px 20px; text-align:center; background:var(--bg-surface-2);">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px; color:var(--text-muted);">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div style="font-size:0.85rem; color:var(--text-title); margin-bottom:4px;">
                            <label for="file_template" style="color:var(--color-primary); font-weight:600; cursor:pointer; text-decoration:underline;">
                                Pilih Berkas HTML Baru
                            </label>
                            <input id="file_template" name="file_template" type="file" accept=".html" style="display:none;" onchange="updateFileName(this)">
                        </div>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin:0;">Maksimal 5MB</p>
                        <p id="selected-file-name" style="font-size:0.8rem; font-weight:600; color:var(--color-success-dark); margin:10px 0 0; display:none;"></p>
                    </div>
                </div>

                {{-- Textarea Mode --}}
                <div id="textarea-mode-container" class="form-group" style="margin:0; display:none;">
                    <label class="form-label">Struktur Kode HTML Templat</label>
                    <p class="form-hint" style="margin-bottom:8px;">
                        Variabel yang tersedia:
                        <code class="font-mono badge badge-primary">&#123;&#123; nomor_surat &#125;&#125;</code>,
                        <code class="font-mono badge badge-primary">&#123;&#123; perihal &#125;&#125;</code>,
                        <code class="font-mono badge badge-primary">&#123;&#123; isi &#125;&#125;</code>,
                        <code class="font-mono badge badge-primary">&#123;&#123; tanggal_surat &#125;&#125;</code>.
                    </p>
                    <textarea name="konten_html" id="konten_html" rows="14"
                        class="form-control font-mono" style="font-size:0.8rem; line-height:1.5;">{{ old('konten_html', $htmlContent) }}</textarea>
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <a href="{{ route('template-surat.index') }}" class="btn btn-outline btn-sm">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Perbarui Templat</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleInputMode(mode) {
        const keepContainer = document.getElementById('keep-mode-container');
        const uploadContainer = document.getElementById('upload-mode-container');
        const textareaContainer = document.getElementById('textarea-mode-container');
        const fileInput = document.getElementById('file_template');
        const textareaInput = document.getElementById('konten_html');

        if (mode === 'keep') {
            keepContainer.style.display = 'block';
            uploadContainer.style.display = 'none';
            textareaContainer.style.display = 'none';
            fileInput.required = false;
            textareaInput.required = false;
        } else if (mode === 'upload') {
            keepContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
            textareaContainer.style.display = 'none';
            fileInput.required = true;
            textareaInput.required = false;
        } else {
            keepContainer.style.display = 'none';
            uploadContainer.style.display = 'none';
            textareaContainer.style.display = 'block';
            fileInput.required = false;
            textareaInput.required = true;
        }
    }

    function updateFileName(input) {
        const nameLabel = document.getElementById('selected-file-name');
        if (input.files && input.files.length > 0) {
            nameLabel.innerText = 'Berkas terpilih: ' + input.files[0].name;
            nameLabel.style.display = 'block';
        } else {
            nameLabel.innerText = '';
            nameLabel.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleInputMode('keep');
    });
</script>
@endsection
