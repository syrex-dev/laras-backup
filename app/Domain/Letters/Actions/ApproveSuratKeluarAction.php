<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Services\TemplateParserService;
use App\Domain\Letters\Support\SuratKeluarStatus;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;
use App\Services\DigitalSignatureService;
use App\Traits\Loggable;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ApproveSuratKeluarAction
{
    use Loggable;

    public function __construct(
        protected TemplateParserService $templateParser
    ) {}

    /**
     * Menjalankan approval surat keluar beserta hybrid digital signature.
     *
     * @return array{success: bool, message: string, was_signed: bool}
     */
    public function execute(SuratKeluar $suratKeluar, int $approverId): array
    {
        $tempPdfPath = null;

        try {
            // STEP 1: Generate QR Token & QR Code image
            $qrToken = bin2hex(random_bytes(16));
            $verifyUrl = route('public.verify', ['token' => $qrToken]);
            $qrCodeSvg = QrCode::format('svg')->size(200)->generate($verifyUrl);
            $qrPath = 'surat-keluar/qrcode-'.$suratKeluar->id.'.svg';
            Storage::disk('public')->put($qrPath, $qrCodeSvg);

            // STEP 2: Render layout PDF via DOMPDF → temporary file
            $suratKeluar->load(['user', 'klasifikasi', 'penyetuju.tandaTangan', 'templatSurat']);

            $profil = ProfilOrganisasi::first();
            $template = $suratKeluar->templatSurat;
            $kontenTemplate = '';

            if ($template && $template->path_file && Storage::disk('public')->exists($template->path_file)) {
                $htmlContent = Storage::disk('public')->get($template->path_file);

                $penyetujuName = optional($suratKeluar->penyetuju)->nama ?? 'Ketua HIMASE';
                $namaOrganisasi = optional($profil)->nama_instansi ?? 'HIMASE';
                $alamatOrganisasi = optional($profil)->alamat ?? 'Alamat Instansi';
                $tanggalSurat = $suratKeluar->tanggal_surat
                    ? Carbon::parse($suratKeluar->tanggal_surat)->translatedFormat('d F Y')
                    : now()->translatedFormat('d F Y');

                $kontenTemplate = $this->templateParser->parse($htmlContent, [
                    'nomor_surat' => $suratKeluar->nomor_surat ?? '-',
                    'tanggal_surat' => $tanggalSurat,
                    'tujuan' => $suratKeluar->tujuan ?? '-',
                    'tujuan_surat' => $suratKeluar->tujuan ?? '-',
                    'perihal' => $suratKeluar->perihal ?? '-',
                    'lampiran' => $suratKeluar->lampiran ?? '-',
                    'isi' => nl2br(e($suratKeluar->isi ?? '-')),
                    'penyetuju' => $penyetujuName,
                    'nama_penyetuju' => $penyetujuName,
                    'nama_organisasi' => $namaOrganisasi,
                    'alamat_organisasi' => $alamatOrganisasi,
                ]);
            }

            $domPdf = Pdf::loadView('pdf.surat-keluar', [
                'surat' => $suratKeluar,
                'kontenTemplate' => $kontenTemplate,
                'qrCode' => $qrCodeSvg,
                'profil' => $profil,
            ]);
            $domPdf->setPaper('A4', 'portrait');

            $tempFileName = 'temp_unsigned_'.$suratKeluar->id.'_'.time().'.pdf';
            $tempPdfPath = storage_path('app/'.$tempFileName);
            file_put_contents($tempPdfPath, $domPdf->output());

            // STEP 3: Hybrid Signing — TCPDF + FPDI + Sertifikat PKI
            $signedFileName = 'surat-keluar/signed-'.$suratKeluar->id.'-'.time().'.pdf';
            $signedPdfPath = storage_path('app/public/'.$signedFileName);

            $signedDir = dirname($signedPdfPath);
            if (! is_dir($signedDir)) {
                mkdir($signedDir, 0755, true);
            }

            $signatureService = new DigitalSignatureService;
            $wasSigned = $signatureService->signOrCopy($tempPdfPath, $signedPdfPath, [
                'name' => optional($suratKeluar->penyetuju)->nama ?? 'Ketua HIMASE',
                'location' => 'Sumedang, Jawa Barat',
                'reason' => 'Pengesahan Digital Simpan.in — '.$suratKeluar->nomor_surat,
                'contact' => optional($suratKeluar->penyetuju)->email ?? 'ketua@himase.ac.id',
            ]);

            // STEP 4: Update database
            $suratKeluar->update([
                'status_approval' => SuratKeluarStatus::APPROVED,
                'qr_token' => $qrToken,
                'path_qrcode' => $qrPath,
                'file_pdf' => $signedFileName,
                'penyetuju_id' => $approverId,
            ]);

            $this->logActivity('approved', 'surat_keluar', $suratKeluar->id);

            $message = $wasSigned
                ? 'Surat berhasil disetujui dengan Tanda Tangan Digital (PKI) dan QR Code telah dibuat.'
                : 'Surat berhasil disetujui dan QR Code dibuat. (Sertifikat digital belum dikonfigurasi — PDF tersimpan tanpa tanda tangan kriptografis.)';

            return [
                'success' => true,
                'message' => $message,
                'was_signed' => $wasSigned,
            ];

        } catch (\Exception $e) {
            Log::error('Approval Error: '.$e->getMessage(), [
                'surat_id' => $suratKeluar->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            if ($tempPdfPath && file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
        }
    }
}
