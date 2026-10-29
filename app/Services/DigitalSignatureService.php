<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

// TCPDF must be explicitly required BEFORE FPDI loads, because FPDI\Tcpdf\Fpdi extends \TCPDF.
// PHP's autoloader won't resolve the parent class in time when FPDI is loaded first.
if (!class_exists('TCPDF')) {
    require_once base_path('vendor/tecnickcom/tcpdf/tcpdf.php');
}

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * DigitalSignatureService
 *
 * Pipeline:
 *   1. Terima path ke PDF yang sudah dirender oleh DOMPDF (temporary file).
 *   2. Import PDF tersebut ke TCPDF via FPDI bridge.
 *   3. Terapkan sertifikat kriptografi (PKCS#12 / PEM) menggunakan setSignature().
 *   4. Tulis output ke $outputPath (Signed PDF).
 *   5. Hapus temporary file.
 *
 * Standar: ISO 32000 / ETSI PAdES (basic)
 */
class DigitalSignatureService
{
    /**
     * Direktori penyimpanan sertifikat, relatif ke storage_path('app').
     */
    protected string $certsDir;

    public function __construct()
    {
        $this->certsDir = storage_path('app/certs');
    }

    /**
     * Terapkan tanda tangan digital ke PDF yang sudah dirender.
     *
     * @param  string  $sourcePdfPath   Absolute path ke PDF sumber (output DOMPDF, temp file)
     * @param  string  $outputPdfPath   Absolute path tujuan file PDF yang sudah di-sign
     * @param  array   $signerInfo      Metadata penanda tangan: name, location, reason, contact
     * @return bool                     true jika berhasil
     *
     * @throws \RuntimeException jika sertifikat tidak ditemukan atau gagal di-apply
     */
    public function signPdf(string $sourcePdfPath, string $outputPdfPath, array $signerInfo = []): bool
    {
        $certFile = $this->certsDir . DIRECTORY_SEPARATOR . 'simpanin_cert.crt';
        $keyFile  = $this->certsDir . DIRECTORY_SEPARATOR . 'simpanin_key.pem';

        // ------------------------------------------------------------------
        // Validasi keberadaan file sertifikat
        // ------------------------------------------------------------------
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            throw new \RuntimeException(
                "Sertifikat digital tidak ditemukan di: {$this->certsDir}. " .
                "Jalankan perintah OpenSSL untuk membuat sertifikat QA terlebih dahulu."
            );
        }

        // ------------------------------------------------------------------
        // Baca konten sertifikat dan private key
        // ------------------------------------------------------------------
        $certContent = file_get_contents($certFile);
        $keyContent  = file_get_contents($keyFile);

        if ($certContent === false || $keyContent === false) {
            throw new \RuntimeException("Gagal membaca file sertifikat atau private key.");
        }

        // ------------------------------------------------------------------
        // Metadata tanda tangan
        // ------------------------------------------------------------------
        $signerName     = $signerInfo['name']     ?? 'Ketua HIMASE';
        $signerLocation = $signerInfo['location']  ?? 'Sumedang, Jawa Barat';
        $signerReason   = $signerInfo['reason']    ?? 'Pengesahan Digital Simpan.in';
        $signerContact  = $signerInfo['contact']   ?? 'ketua@himase.ac.id';

        // ------------------------------------------------------------------
        // Inisiasi TCPDF + FPDI
        // ------------------------------------------------------------------
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Import semua halaman dari PDF sumber
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);

            // Sesuaikan orientasi halaman
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
        }

        // ------------------------------------------------------------------
        // Terapkan tanda tangan kriptografi (ISO 32000 / PAdES-B-B)
        // TCPDF setSignature() format:
        //   cert      → konten sertifikat (string PEM)
        //   privkey   → konten private key (string PEM)
        //   password  → passphrase private key
        //   extracerts→ extra certs (kosong untuk self-signed)
        //   certtype  → 2 = Mudah diverifikasi, tidak perlu data eksternal
        //   info      → array metadata
        // ------------------------------------------------------------------
        $pdf->setSignature(
            'file://' . $certFile,   // certificate
            'file://' . $keyFile,    // private key
            'simpanin2026',          // passphrase (sesuai saat generate openssl)
            '',                      // extra certs chain (kosong = self-signed)
            2,                       // certtype: 2 = cert embedded in signature
            [
                'Name'        => $signerName,
                'Location'    => $signerLocation,
                'Reason'      => $signerReason,
                'ContactInfo' => $signerContact,
            ]
        );

        // ------------------------------------------------------------------
        // Tambahkan signature appearance widget (invisible field)
        // Ini menciptakan form field tersembunyi yang membawa signature bytes
        // ------------------------------------------------------------------
        $pdf->setSignatureAppearance(0, 0, 0, 0);

        // ------------------------------------------------------------------
        // Tulis output ke file tujuan
        // TCPDF Output mode 'F' = simpan ke file
        // ------------------------------------------------------------------
        $pdf->Output($outputPdfPath, 'F');

        return true;
    }

    /**
     * Wrapper aman: terapkan tanda tangan jika sertifikat tersedia.
     * Jika tidak tersedia, salin PDF sumber ke tujuan tanpa signing (graceful degradation).
     *
     * @return bool  true = signed berhasil, false = degraded (disalin tanpa signing)
     */
    public function signOrCopy(string $sourcePdfPath, string $outputPdfPath, array $signerInfo = []): bool
    {
        try {
            $this->signPdf($sourcePdfPath, $outputPdfPath, $signerInfo);
            Log::info('[DigitalSignature] PDF berhasil di-sign secara kriptografis.', [
                'output' => $outputPdfPath,
            ]);
            return true;
        } catch (\RuntimeException $e) {
            // Sertifikat tidak ditemukan → graceful degradation
            Log::warning('[DigitalSignature] ' . $e->getMessage() . ' — PDF disimpan tanpa tanda tangan digital.');
            copy($sourcePdfPath, $outputPdfPath);
            return false;
        } catch (\Exception $e) {
            Log::error('[DigitalSignature] Gagal sign PDF: ' . $e->getMessage());
            // Tetap salin PDF agar dokumen tidak hilang
            copy($sourcePdfPath, $outputPdfPath);
            return false;
        }
    }
}
