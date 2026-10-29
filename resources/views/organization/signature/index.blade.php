@extends('layouts.app')

@section('title', 'Tanda Tangan Digital')

@section('content')
<div style="max-width:820px; margin:0 auto;" class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div style="margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:var(--text-muted); margin-bottom:4px;">
            <a href="{{ route('dashboard') }}" style="color:var(--text-muted); text-decoration:none;">Dashboard</a>
            <span>/</span>
            <span style="color:var(--text-title); font-weight:600;">Profil</span>
            <span>/</span>
            <span style="color:var(--color-primary); font-weight:600;">Tanda Tangan Digital</span>
        </div>
        <h1 style="font-size:1.4rem; font-weight:700; color:var(--text-title); margin:0;">
            Tanda Tangan Digital
        </h1>
        <p style="font-size:0.82rem; color:var(--text-muted); margin:4px 0 0;">
            Kelola spesimen tanda tangan resmi pimpinan untuk pembubuhan otomatis pada surat keluar yang disetujui.
        </p>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Two column grid --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:20px;">
        {{-- Current Signature Preview --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tanda Tangan Aktif</h2>
                <span class="badge badge-primary">Spesimen</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:240px; text-align:center;">
                @if($tandaTangan && $tandaTangan->path_tanda_tangan)
                    <div style="padding:16px; background:var(--bg-surface-2); border:1px solid var(--border-color); border-radius:var(--radius-base); margin-bottom:12px; max-width:280px; width:100%;">
                        <img src="{{ asset('storage/' . $tandaTangan->path_tanda_tangan) }}"
                             alt="Tanda Tangan Digital"
                             style="max-height:120px; max-width:100%; object-fit:contain; margin:0 auto; display:block;">
                    </div>
                    <span class="badge badge-success" style="font-size:0.78rem;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Tanda tangan tersimpan &amp; aktif</span>
                    </span>
                @else
                    <div style="color:var(--text-muted); padding:20px;">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px; opacity:0.4;">
                            <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        <p style="font-size:0.85rem; margin:0;">Belum ada spesimen tanda tangan yang diunggah.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Upload New Signature --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Unggah Tanda Tangan Baru</h2>
                <span class="badge badge-info">PNG Transparan</span>
            </div>
            <form action="{{ route('profile.signature.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                        Disarankan menggunakan berkas format PNG transparan dengan resolusi tajam agar hasil cetak PDF surat terlihat bersih dan profesional.
                    </p>

                    <div style="border:2px dashed var(--border-color); border-radius:var(--radius-base); padding:24px 16px; text-align:center; background:var(--bg-surface-2);">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px; color:var(--text-muted);">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div style="font-size:0.85rem; margin-bottom:4px;">
                            <label for="tanda_tangan" style="color:var(--color-primary); font-weight:600; cursor:pointer; text-decoration:underline;">
                                Pilih Berkas Tanda Tangan
                            </label>
                            <input type="file" name="tanda_tangan" id="tanda_tangan" accept="image/png,image/jpeg" required style="display:none;" onchange="updateSigFileName(this)">
                        </div>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin:0;">Format PNG / JPG (Maksimal 2MB)</p>
                        <p id="sig-file-name" style="font-size:0.8rem; font-weight:600; color:var(--color-success-dark); margin:8px 0 0; display:none;"></p>
                    </div>

                    @error('tanda_tangan')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="card-footer" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Simpan Spesimen Tanda Tangan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateSigFileName(input) {
        const nameLabel = document.getElementById('sig-file-name');
        if (input.files && input.files.length > 0) {
            nameLabel.innerText = 'Berkas terpilih: ' + input.files[0].name;
            nameLabel.style.display = 'block';
        } else {
            nameLabel.innerText = '';
            nameLabel.style.display = 'none';
        }
    }
</script>
@endsection
