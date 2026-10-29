<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Terima - {{ $surat->nomor_agenda }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #111;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .instansi-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .instansi-sub {
            font-size: 8pt;
            margin: 2px 0 0;
            color: #555;
        }
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0 10px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-data td {
            padding: 4px 6px;
            font-size: 9pt;
            vertical-align: top;
        }
        .border-box {
            border: 1px solid #999;
            background: #fafafa;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            font-size: 8.5pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="instansi-title">{{ $institution->name }}</h1>
        <p class="instansi-sub">{{ $institution->address }} | Telp: {{ $institution->phone }} | Email: {{ $institution->email }}</p>
    </div>

    <div class="doc-title">TANDA TERIMA SURAT / DOKUMEN MASUK</div>

    <div class="border-box">
        <table class="table-data">
            <tr>
                <td width="25%"><strong>Nomor Agenda:</strong></td>
                <td width="40%"><span style="font-size: 11pt; font-weight: bold; color: #047857;">{{ $surat->nomor_agenda }}</span></td>
                <td width="15%"><strong>Sifat:</strong></td>
                <td width="20%">{{ strtoupper($surat->sifat) }}</td>
            </tr>
            <tr>
                <td><strong>Nomor Surat Asal:</strong></td>
                <td>{{ $surat->nomor_surat }}</td>
                <td><strong>Tgl Surat:</strong></td>
                <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Pengirim / Instansi:</strong></td>
                <td colspan="3"><strong>{{ $surat->pengirim }}</strong></td>
            </tr>
            <tr>
                <td><strong>Tanggal Diterima:</strong></td>
                <td colspan="3">{{ \Carbon\Carbon::parse($surat->tanggal_diterima)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Perihal:</strong></td>
                <td colspan="3">{{ $surat->perihal }}</td>
            </tr>
        </table>
    </div>

    <table class="footer-table">
        <tr>
            <td width="100px" style="text-align: center;">
                <img src="{{ $qrCode }}" width="75" height="75" alt="QR Code">
                <div style="font-size: 7pt; color: #666; margin-top: 2px;">Cek Status</div>
            </td>
            <td style="padding-left: 15px;">
                <p style="margin: 0; color: #555;">
                    Dokumen telah diterima dan dicatat dalam buku agenda digital Tata Usaha.<br>
                    Petugas Penerima: <strong>{{ $surat->user ? $surat->user->name : 'Petugas TU' }}</strong><br>
                    Waktu Cetak: {{ now()->translatedFormat('d/m/Y H:i') }} WIB
                </p>
            </td>
            <td width="180px" style="text-align: center;">
                <div>Tanda Tangan Penerima,</div>
                <div style="height: 45px;"></div>
                <div style="border-bottom: 1px dotted #333; width: 140px; margin: 0 auto;"></div>
                <div style="font-size: 8pt; margin-top: 2px;">{{ $surat->user ? $surat->user->name : 'Petugas TU' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
