<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Services\NomorSuratGenerator;
use App\Domain\Letters\Services\TemplateParserService;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;
use App\Models\TemplatSurat;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PreviewSuratKeluarAction
{
    public function __construct(
        protected NomorSuratGenerator $nomorSuratGenerator,
        protected TemplateParserService $templateParser
    ) {}

    public function execute(array $data): Response
    {
        $templatSuratId = $data['templat_surat_id'] ?? null;
        if (! $templatSuratId) {
            throw new \Exception('Template Surat belum dipilih.');
        }

        $template = TemplatSurat::find($templatSuratId);
        if (! $template || ! $template->path_file || ! Storage::disk('public')->exists($template->path_file)) {
            throw new \Exception('Berkas template surat tidak ditemukan.');
        }

        $htmlContent = Storage::disk('public')->get($template->path_file);
        $profil = ProfilOrganisasi::first();

        $nomorSuratMock = $this->nomorSuratGenerator->generateMock($data['klasifikasi_id'] ?? null);

        $penyetujuName = 'Nama Penyetuju';
        if (! empty($data['penyetuju_id'])) {
            $penyetujuUser = User::find($data['penyetuju_id']);
            if ($penyetujuUser) {
                $penyetujuName = $penyetujuUser->nama;
            }
        }

        $tanggalSurat = ! empty($data['tanggal_surat'])
            ? Carbon::parse($data['tanggal_surat'])->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        $kontenTemplate = $this->templateParser->parse($htmlContent, [
            'nomor_surat' => $nomorSuratMock,
            'tanggal_surat' => $tanggalSurat,
            'tujuan' => $data['tujuan'] ?? '-',
            'tujuan_surat' => $data['tujuan'] ?? '-',
            'perihal' => $data['perihal'] ?? '-',
            'lampiran' => $data['lampiran'] ?? '-',
            'isi' => nl2br(e($data['isi'] ?? '-')),
            'penyetuju' => $penyetujuName,
            'nama_penyetuju' => $penyetujuName,
            'nama_organisasi' => optional($profil)->nama_instansi ?? 'HIMASE',
            'alamat_organisasi' => optional($profil)->alamat ?? 'Alamat Instansi',
        ]);

        $mockSurat = new SuratKeluar([
            'nomor_surat' => $nomorSuratMock,
            'tanggal_surat' => $data['tanggal_surat'] ?? now()->format('Y-m-d'),
            'tujuan' => $data['tujuan'] ?? '-',
            'perihal' => $data['perihal'] ?? '-',
            'isi' => $data['isi'] ?? '-',
            'lampiran' => $data['lampiran'] ?? '-',
            'penyetuju_id' => $data['penyetuju_id'] ?? null,
            'templat_surat_id' => $templatSuratId,
        ]);

        $verifyUrl = route('dashboard');
        $qrCodeSvg = QrCode::format('svg')->size(200)->generate($verifyUrl);

        $domPdf = Pdf::loadView('pdf.surat-keluar', [
            'surat' => $mockSurat,
            'kontenTemplate' => $kontenTemplate,
            'qrCode' => $qrCodeSvg,
            'profil' => $profil,
        ]);
        $domPdf->setPaper('A4', 'portrait');

        return response($domPdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }
}
