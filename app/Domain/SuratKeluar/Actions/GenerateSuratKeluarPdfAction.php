<?php

namespace App\Domain\SuratKeluar\Actions;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class GenerateSuratKeluarPdfAction
{
    public function __construct(
        protected ?TemplateVariableService $variableService = null
    ) {
        $this->variableService = $variableService ?? app(TemplateVariableService::class);
    }

    /**
     * Menghasilkan file PDF resmi surat keluar dari template, kop instansi, dan nomor surat.
     * Area tanda tangan disiapkan sebagai placeholder untuk Fase 3B (TTE/Tanda Tangan Elektronik).
     */
    public function execute(SuratKeluar $suratKeluar, bool $saveToStorage = true): array
    {
        $suratKeluar->loadMissing([
            'template',
            'orgUnit',
            'penandatangan.position',
            'klasifikasi',
            'attachments',
        ]);

        $institution = InstitutionProfile::first() ?? InstitutionProfile::current();

        $jabatanPenandatangan = $suratKeluar->penandatangan?->position?->name ?? 'Pimpinan';
        $tanggalSurat = $suratKeluar->tanggal_surat
            ? Carbon::parse($suratKeluar->tanggal_surat)
            : now();

        $context = [
            'nomor_surat' => $suratKeluar->nomor_surat ?: '(Draf Tanpa Nomor)',
            'tanggal_surat' => $tanggalSurat->toDateString(),
            'perihal' => $suratKeluar->perihal,
            'tujuan' => $suratKeluar->tujuan,
            'nama_penandatangan' => $suratKeluar->penandatangan?->name ?? 'Pejabat Penandatangan',
            'jabatan_penandatangan' => $jabatanPenandatangan,
            'unit_pembuat' => $suratKeluar->orgUnit?->name ?? 'Unit Pengusul',
        ];

        // Ambil isi surat atau fallback ke template default
        $rawContent = ! empty($suratKeluar->isi_surat)
            ? $suratKeluar->isi_surat
            : ($suratKeluar->template?->konten_default ?? '<p>Naskah dinas.</p>');

        $renderedContent = $this->variableService->render($rawContent, $context);

        $data = [
            'surat' => $suratKeluar,
            'institution' => $institution,
            'renderedContent' => $renderedContent,
            'jabatanPenandatangan' => $context['jabatan_penandatangan'],
            'namaPenandatangan' => $context['nama_penandatangan'],
            'tanggalFormatted' => $tanggalSurat->locale('id')->isoFormat('D MMMM Y'),
        ];

        $pdf = Pdf::loadView('pdf.surat-keluar', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'chroot' => [
                    base_path('public'),
                    storage_path('app/public'),
                ],
                'defaultFont' => 'sans-serif',
            ]);

        $pdfBinary = $pdf->output();
        $storedPath = null;

        if ($saveToStorage) {
            $filename = 'surat_keluar_' . $suratKeluar->id . '_' . date('Ymd_His') . '.pdf';
            $relativeDir = 'surat-keluar/pdf';
            $fullPath = $relativeDir . '/' . $filename;

            Storage::disk('public')->put($fullPath, $pdfBinary);
            $suratKeluar->update(['file_pdf' => $fullPath]);
            $storedPath = $fullPath;
        }

        return [
            'pdf_binary' => $pdfBinary,
            'file_path' => $storedPath,
            'filename' => 'Surat_Keluar_' . ($suratKeluar->nomor_surat ? str_replace(['/', '\\', ' '], '_', $suratKeluar->nomor_surat) : $suratKeluar->id) . '.pdf',
        ];
    }
}
