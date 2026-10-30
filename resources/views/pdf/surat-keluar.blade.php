<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $surat->nomor_surat ?? 'Naskah Keluar' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .header-logo {
            width: 18%;
            text-align: center;
            vertical-align: middle;
        }
        .header-text {
            width: 82%;
            text-align: center;
            vertical-align: middle;
        }
        .instansi-nama {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }
        .instansi-detail {
            font-size: 9pt;
            color: #333333;
            margin-top: 4px;
            line-height: 1.3;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 11pt;
        }
        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .content-body {
            margin-top: 15px;
            margin-bottom: 30px;
            text-align: justify;
            font-size: 11pt;
            line-height: 1.6;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 48%;
            float: right;
            text-align: center;
        }
        .signature-placeholder {
            border: 1px dashed #9ca3af;
            background-color: #f9fafb;
            color: #6b7280;
            font-size: 9pt;
            padding: 22px 10px;
            margin: 12px auto;
            width: 85%;
            border-radius: 4px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .signature-nip {
            font-size: 10pt;
            color: #4b5563;
        }
        .footer-note {
            margin-top: 50px;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    @php
        /** @var \App\Domain\Instansi\Models\InstitutionProfile $institution */
        $institution = $institution ?? \App\Domain\Instansi\Models\InstitutionProfile::first() ?? \App\Domain\Instansi\Models\InstitutionProfile::current();
        
        $logoBase64 = null;
        if (!empty($institution->logo_path)) {
            $logoFile = storage_path('app/public/' . ltrim($institution->logo_path, '/'));
            if (!file_exists($logoFile)) {
                $logoFile = public_path('storage/' . ltrim($institution->logo_path, '/'));
            }
            if (file_exists($logoFile) && is_file($logoFile)) {
                $mime = mime_content_type($logoFile) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
            }
        }
    @endphp

    {{-- KOP SURAT RESMI INSTANSI --}}
    <table class="header-table">
        <tr>
            @if($logoBase64)
                <td class="header-logo">
                    <img src="{{ $logoBase64 }}" style="max-height: 75px; max-width: 90px;" alt="Logo">
                </td>
            @endif
            <td class="header-text" @if(!$logoBase64) style="width: 100%;" @endif>
                <div class="instansi-nama">{{ $institution->name ?? 'POLITEKNIK CONTOH INDONESIA' }}</div>
                <div class="instansi-detail">
                    {{ $institution->address ?? 'Jl. Pendidikan Tinggi No. 1' }}<br>
                    Telepon: {{ $institution->phone ?? '(021) 1234567' }} | Email: {{ $institution->email ?? 'info@kampus.ac.id' }}
                    @if(!empty($institution->website)) | Laman: {{ $institution->website }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- METADATA SURAT --}}
    <table class="meta-table">
        <tr>
            <td style="width: 15%;">Nomor</td>
            <td style="width: 3%;">:</td>
            <td style="width: 47%; font-weight: bold;">{{ $surat->nomor_surat ?? '(Draf Belum Bernomor)' }}</td>
            <td style="width: 35%; text-align: right;">
                {{ $tanggalFormatted ?? ($surat->tanggal_surat ? \Carbon\Carbon::parse($surat->tanggal_surat)->locale('id')->isoFormat('D MMMM Y') : now()->locale('id')->isoFormat('D MMMM Y')) }}
            </td>
        </tr>
        <tr>
            <td>Sifat</td>
            <td>:</td>
            <td>{{ ucfirst($surat->sifat ?? 'Biasa') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>{{ !empty($surat->lampiran) ? $surat->lampiran : ($surat->attachments->count() > 0 ? $surat->attachments->count() . ' Berkas' : '-') }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $surat->perihal }}</td>
            <td></td>
        </tr>
    </table>

    {{-- TUJUAN PENERIMA --}}
    <div style="margin-top: 15px; margin-bottom: 20px;">
        Yth. <strong>{{ $surat->tujuan }}</strong><br>
        di Tempat
    </div>

    {{-- BADAN NASKAH / ISI SURAT --}}
    <div class="content-body">
        {!! $renderedContent !!}
    </div>

    {{-- AREA TANDA TANGAN (PLACEHOLDER FASE 3B) --}}
    <table class="signature-table">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div style="font-weight: 500; margin-bottom: 4px;">
                    {{ $jabatanPenandatangan ?? 'Pimpinan Instansi' }},
                </div>

                {{-- Placeholder visual tanda tangan dinas (Fase 3B) --}}
                <div class="signature-placeholder">
                    <strong>[AREA TANDA TANGAN ELEKTRONIK / BASAH]</strong><br>
                    <span style="font-size: 8pt; color: #9ca3af;">(Divalidasi pada Fase 3B: TTE / Segel Elektronik)</span>
                </div>

                <div class="signature-name">
                    {{ $surat->penandatangan->name ?? $namaPenandatangan ?? 'Nama Pimpinan' }}
                </div>
                @if(!empty($surat->penandatangan?->nip))
                    <div class="signature-nip">NIP. {{ $surat->penandatangan->nip }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- CATATAN KAKI DOKUMEN SISTEM --}}
    <div class="footer-note">
        Dokumen resmi diterbitkan melalui Sistem Persuratan Digital &bull; Dokumen sah dan terverifikasi secara internal &bull; ID: #{{ $surat->id }}
    </div>
</body>
</html>
