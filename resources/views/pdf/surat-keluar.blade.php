<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Keluar - {{ $surat->nomor_surat }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12pt; line-height: 1.5; color: #333; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; text-transform: uppercase; }
        .header p { margin: 0; font-size: 10pt; }
        .content { margin-top: 20px; }
        .info { margin-bottom: 20px; }
        .footer { margin-top: 50px; }
        .signature { float: right; text-align: center; width: 200px; }
        .qr-code { margin-top: 10px; }
        .clear { clear: both; }
    </style>
</head>
<body>
    @php
        $profil = $profil ?? \App\Models\ProfilOrganisasi::first();
        $isPreview = $isPreview ?? false;

        /**
         * Konversi path gambar yang tersimpan di DB menjadi Data URI Base64.
         *
         * Strategi resolusi (dicoba berurutan):
         *   1. storage_path('app/public/' . $cleanPath)  ← path dari Storage::disk('public')->store()
         *   2. public_path('storage/' . $cleanPath)      ← via symlink storage:link
         *   3. Storage::disk('public')->path($cleanPath) ← Laravel Storage facade
         *
         * Format path yang didukung:
         *   - 'organization/file.png'         (dari controller)
         *   - 'storage/organization/file.png' (legacy dengan prefix)
         *   - '/storage/organization/file.png' (dengan leading slash)
         */
        $toBase64DataUri = function ($path, $defaultMime = 'image/png') {
            if (empty($path)) {
                return null;
            }

            // Normalisasi: hapus leading slash dan prefix 'storage/' agar path bersih
            $cleanPath = ltrim($path, '/');
            $cleanPath = preg_replace('#^storage/#i', '', $cleanPath);

            // Kandidat path fisik yang akan dicoba
            $candidates = [
                storage_path('app/public/' . $cleanPath),   // ← utama
                public_path('storage/' . $cleanPath),        // ← via symlink
                \Illuminate\Support\Facades\Storage::disk('public')->path($cleanPath), // ← facade
            ];

            $resolved = null;
            foreach ($candidates as $candidate) {
                if (file_exists($candidate) && is_file($candidate)) {
                    $resolved = $candidate;
                    break;
                }
            }

            if (!$resolved) {
                return null;
            }

            $mime = mime_content_type($resolved) ?: $defaultMime;
            $content = file_get_contents($resolved);
            if ($content === false) {
                return null;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        };

        $logoDataUri      = $toBase64DataUri(optional($profil)->path_logo ?? null);
        $stempelDataUri   = $toBase64DataUri(optional($profil)->path_stempel ?? null);
        $tandaTanganDataUri = null;
        $tandaTanganPath  = optional(optional($surat->penyetuju)->tandaTangan)->path_tanda_tangan ?? null;
        if (!empty($tandaTanganPath)) {
            $tandaTanganDataUri = $toBase64DataUri($tandaTanganPath);
        }
    @endphp
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 20%; text-align: left;">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" style="height: 80px;">
                    @else
                        <h2 style="margin: 0; padding: 20px; border: 1px solid #000;">LOGO</h2>
                    @endif
                </td>
                <td style="width: 80%; text-align: center;">
                    <h1>{{ $profil->nama_instansi ?? 'HIMASE' }}</h1>
                    <p>{{ $profil->alamat ?? 'Alamat Instansi, Gedung Organisasi Mahasiswa, Kampus Politeknik' }}</p>
                    <p style="margin: 0; font-size: 10pt;">
                        Email: {{ $profil->email ?? 'himase@mardira.ac.id' }} | 
                        Kontak: {{ $profil->telepon ?? '(022) 1234567' }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        {!! $kontenTemplate !!}
    </div>

    <table style="width: 100%; margin-top: 50px; border: none;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center; vertical-align: top;">
                <p style="margin: 0; margin-bottom: 2px;">Jatiwangi, {{ $surat->tanggal_surat ? \Carbon\Carbon::parse($surat->tanggal_surat)->format('d F Y') : now()->format('d F Y') }}</p>
                <p style="margin: 0; margin-bottom: 5px;">Mengetahui,</p>
                <p style="margin: 0;"><strong>Ketua Umum HIMASE</strong></p>

                <div style="margin: 15px 0; min-height: 80px;">
                    @if(isset($qrCode) && !empty($qrCode))
                        <img src="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}" width="90">
                    @elseif($tandaTanganDataUri)
                        <img src="{{ $tandaTanganDataUri }}" style="height: 80px;">
                    @else
                        <br><br><br><span style="color: gray; font-size: 11px;">[Menunggu Pengesahan]</span><br><br>
                    @endif

                    @if($stempelDataUri)
                        <img src="{{ $stempelDataUri }}" style="height: 60px; margin-top: 10px;">
                    @endif
                </div>

                <p style="margin: 0;"><strong><u>{{ $surat->penyetuju->nama ?? 'Nama Pimpinan' }}</u></strong></p>
            </td>
        </tr>
    </table>
</body>
</html>


