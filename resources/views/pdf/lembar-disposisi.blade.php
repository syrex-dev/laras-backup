<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi - {{ $surat->nomor_agenda }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #111;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .instansi-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .instansi-sub {
            font-size: 9pt;
            margin: 3px 0 0;
            color: #444;
        }
        .document-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 15px;
            text-transform: uppercase;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table-data td, .table-data th {
            border: 1px solid #333;
            padding: 6px 8px;
            font-size: 10pt;
            vertical-align: top;
        }
        .table-data th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .checkbox-grid {
            width: 100%;
            margin-top: 5px;
        }
        .checkbox-item {
            display: inline-block;
            width: 48%;
            margin-bottom: 6px;
            font-size: 9.5pt;
        }
        .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
            vertical-align: middle;
        }
        .catatan-box {
            min-height: 120px;
            border: 1px solid #333;
            padding: 8px;
            font-size: 9.5pt;
            margin-bottom: 15px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .footer-table td {
            vertical-align: middle;
            border: none;
        }
        .qr-section {
            text-align: center;
            width: 120px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #777;
        }
    </style>
</head>
<body>
    {{-- Kop Surat Instansi Resmi --}}
    <div class="header">
        <h1 class="instansi-title">{{ $institution->name }}</h1>
        <p class="instansi-sub">
            {{ $institution->address }}<br>
            Telepon: {{ $institution->phone }} | Email: {{ $institution->email }} | Website: {{ $institution->website }}
        </p>
    </div>

    <div class="document-title">LEMBAR DISPOSISI NASKAH DINAS</div>

    <table class="table-data">
        <tr>
            <th width="25%">Nomor Agenda</th>
            <td width="35%"><strong style="font-size: 11pt; color: #0f766e;">{{ $surat->nomor_agenda }}</strong></td>
            <th width="20%">Tingkat Sifat</th>
            <td width="20%"><span class="badge">{{ strtoupper($surat->sifat) }}</span></td>
        </tr>
        <tr>
            <th>Tanggal Terima</th>
            <td>{{ \Carbon\Carbon::parse($surat->tanggal_diterima)->translatedFormat('d F Y') }}</td>
            <th>Jenis Media</th>
            <td>{{ strtoupper($surat->jenis) }}</td>
        </tr>
        <tr>
            <th>Nomor Surat Asal</th>
            <td>{{ $surat->nomor_surat }}</td>
            <th>Tanggal Surat</th>
            <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Asal / Pengirim</th>
            <td colspan="3"><strong>{{ $surat->pengirim }}</strong></td>
        </tr>
        <tr>
            <th>Klasifikasi Arsip</th>
            <td colspan="3">{{ $surat->klasifikasi ? $surat->klasifikasi->kode . ' - ' . $surat->klasifikasi->nama : '-' }}</td>
        </tr>
        <tr>
            <th>Perihal</th>
            <td colspan="3" style="font-size: 10.5pt; font-weight: 500;">{{ $surat->perihal }}</td>
        </tr>
        <tr>
            <th>Tujuan Unit / Pejabat</th>
            <td colspan="3">{{ $surat->unitTujuan ? $surat->unitTujuan->name . ' (' . $surat->unitTujuan->code . ')' : 'Pimpinan Utama' }}</td>
        </tr>
    </table>

    <table class="table-data">
        <tr>
            <th colspan="2">PETUNJUK / INSTRUKSI DISPOSISI PIMPINAN</th>
        </tr>
        <tr>
            <td width="50%">
                <div class="checkbox-item"><span class="box"></span> Tindak Lanjuti Segera</div><br>
                <div class="checkbox-item"><span class="box"></span> Untuk Diketahui / Monitor</div><br>
                <div class="checkbox-item"><span class="box"></span> Siapkan Bahan & Telaahan</div><br>
                <div class="checkbox-item"><span class="box"></span> Tanggapi Tertulis (Konsep Surat)</div>
            </td>
            <td width="50%">
                <div class="checkbox-item"><span class="box"></span> Hadiri / Wakili Undangan</div><br>
                <div class="checkbox-item"><span class="box"></span> Koordinasikan dengan Unit Terkait</div><br>
                <div class="checkbox-item"><span class="box"></span> Simpan / Arsipkan Dokumen</div><br>
                <div class="checkbox-item"><span class="box"></span> Lainnya: ............................</div>
            </td>
        </tr>
        <tr>
            <th colspan="2">CATATAN KHUSUS / PETUNJUK TINDAK LANJUT</th>
        </tr>
        <tr>
            <td colspan="2" style="height: 100px;">
                <p style="color: #666; font-style: italic; font-size: 9pt; margin: 0;">(Tuliskan catatan arahan langsung jika diperlukan)</p>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td class="qr-section">
                <img src="{{ $qrCode }}" width="90" height="90" alt="QR Verifikasi Pelacakan">
                <div style="font-size: 7.5pt; color: #555; margin-top: 3px;">
                    Scan Pelacakan Digital
                </div>
            </td>
            <td style="padding-left: 20px;">
                <p style="font-size: 8.5pt; color: #555; margin: 0;">
                    Lembar disposisi ini merupakan naskah dinas resmi internal yang diterbitkan oleh Sistem Tata Usaha Digital <strong>{{ $institution->name }}</strong>.<br>
                    Nomor Agenda: <strong>{{ $surat->nomor_agenda }}</strong> | Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }} WIB.
                </p>
            </td>
            <td style="text-align: right; width: 200px;">
                <div style="font-size: 9.5pt; margin-bottom: 50px;">
                    Paraf Pimpinan / Pejabat:
                </div>
                <div style="border-bottom: 1px dotted #000; width: 180px; margin-left: auto;"></div>
            </td>
        </tr>
    </table>
</body>
</html>
