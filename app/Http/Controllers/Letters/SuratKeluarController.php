<?php

namespace App\Http\Controllers\Letters;

use App\Domain\Letters\Actions\ApproveSuratKeluarAction;
use App\Domain\Letters\Actions\CreateSuratKeluarAction;
use App\Domain\Letters\Actions\PreviewSuratKeluarAction;
use App\Domain\Letters\Actions\RejectSuratKeluarAction;
use App\Domain\Letters\Actions\UpdateSuratKeluarAction;
use App\Domain\Letters\Services\TemplateParserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Letters\StoreSuratKeluarRequest;
use App\Http\Requests\Letters\UpdateSuratKeluarRequest;
use App\Models\KlasifikasiSurat;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;
use App\Models\TemplatSurat;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SuratKeluarController extends Controller
{
    public function index()
    {
        $suratKeluar = SuratKeluar::query()->with(['user', 'klasifikasi'])->latest()->paginate(10);
        return view('letters.surat-keluar.index', compact('suratKeluar'));
    }

    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->hasRole('Sekretaris')) {
            abort(403, 'Akses Ditolak: Hanya Sekretaris yang dapat membuat draft surat keluar.');
        }

        $klasifikasi = KlasifikasiSurat::all();
        $pimpinan    = User::role('Pimpinan')->get();
        $templates   = TemplatSurat::all();

        return view('letters.surat-keluar.create', compact('klasifikasi', 'pimpinan', 'templates'));
    }

    public function store(StoreSuratKeluarRequest $request, CreateSuratKeluarAction $createAction)
    {
        $surat = $createAction->execute($request->validated(), Auth::id());

        return redirect()->route('surat-keluar.index')
            ->with('success', "Surat keluar berhasil dibuat sebagai draft dengan nomor: {$surat->nomor_surat}");
    }

    public function show(SuratKeluar $suratKeluar)
    {
        $suratKeluar->load(['user', 'klasifikasi', 'penyetuju']);
        return view('letters.surat-keluar.show', compact('suratKeluar'));
    }

    public function edit(SuratKeluar $suratKeluar)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->hasRole('Sekretaris')) {
            abort(403, 'Akses Ditolak: Hanya Sekretaris yang berhak mengedit surat keluar.');
        }

        $klasifikasi = KlasifikasiSurat::all();
        $pimpinan    = User::role('Pimpinan')->get();
        $templates   = TemplatSurat::all();

        return view('letters.surat-keluar.edit', compact('suratKeluar', 'klasifikasi', 'pimpinan', 'templates'));
    }

    public function update(UpdateSuratKeluarRequest $request, SuratKeluar $suratKeluar, UpdateSuratKeluarAction $updateAction)
    {
        $updateAction->execute($suratKeluar, $request->validated());

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil diperbarui.');
    }

    public function destroy(SuratKeluar $suratKeluar)
    {
        $suratKeluar->delete();

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dihapus.');
    }

    public function approve(SuratKeluar $suratKeluar, ApproveSuratKeluarAction $approveAction)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->hasRole('Pimpinan')) {
            abort(403, 'Akses Ditolak: Aksi pengesahan secara eksklusif hanya dapat dilakukan oleh Pimpinan.');
        }

        try {
            $result = $approveAction->execute($suratKeluar, $user->id);

            return redirect()->route('surat-keluar.show', $suratKeluar)
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui surat: ' . $e->getMessage());
        }
    }

    public function reject(SuratKeluar $suratKeluar, RejectSuratKeluarAction $rejectAction)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->hasRole('Pimpinan')) {
            abort(403, 'Akses Ditolak: Aksi penolakan secara eksklusif hanya dapat dilakukan oleh Pimpinan.');
        }

        $rejectAction->execute($suratKeluar);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Surat telah ditolak.');
    }

    public function preview(Request $request, PreviewSuratKeluarAction $previewAction)
    {
        try {
            return $previewAction->execute($request->all());
        } catch (\Exception $e) {
            Log::error('Preview Error: ' . $e->getMessage());
            return response(
                '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:24px;color:#b91c1c;background:#fef2f2;">' .
                '<h3>Gagal Menampilkan Pratinjau</h3><p>' . e($e->getMessage()) . '</p></body></html>',
                422,
                ['Content-Type' => 'text/html']
            );
        }
    }

    public function previewDraft(SuratKeluar $suratKeluar, TemplateParserService $templateParser)
    {
        try {
            $template = $suratKeluar->templatSurat;
            if (!$template || !$template->path_file || !Storage::disk('public')->exists($template->path_file)) {
                throw new \Exception('Berkas template surat tidak ditemukan.');
            }

            $htmlContent = Storage::disk('public')->get($template->path_file);
            $profil = ProfilOrganisasi::first();

            $nomorSurat = $suratKeluar->nomor_surat ?? '[NOMOR SURAT]';
            $tanggalSurat = $suratKeluar->tanggal_surat
                ? Carbon::parse($suratKeluar->tanggal_surat)->translatedFormat('d F Y')
                : now()->translatedFormat('d F Y');

            $tujuan = $suratKeluar->tujuan ?: '[TUJUAN SURAT]';
            $perihal = $suratKeluar->perihal ?: '[PERIHAL SURAT]';
            $isi = nl2br(e($suratKeluar->isi ?: '[TULISKAN ISI SURAT DI SINI]'));

            $penyetujuName = $suratKeluar->penyetuju->nama ?? '[NAMA PENYETUJU]';
            $namaOrganisasi = $profil->nama_instansi ?? 'HIMASE';
            $alamatOrganisasi = $profil->alamat ?? 'Alamat Instansi';

            $kontenTemplate = $templateParser->parse($htmlContent, [
                'nomor_surat'       => $nomorSurat,
                'tanggal_surat'     => $tanggalSurat,
                'tujuan'            => $tujuan,
                'tujuan_surat'      => $tujuan,
                'perihal'           => $perihal,
                'lampiran'          => $suratKeluar->lampiran ?? '-',
                'isi'               => $isi,
                'penyetuju'         => $penyetujuName,
                'nama_penyetuju'    => $penyetujuName,
                'nama_organisasi'   => $namaOrganisasi,
                'alamat_organisasi' => $alamatOrganisasi,
            ]);

            $qrCodeRaw = null;
            if ($suratKeluar->status_approval === 'approved' && $suratKeluar->path_qrcode) {
                $qrPath = storage_path('app/public/' . $suratKeluar->path_qrcode);
                if (file_exists($qrPath)) {
                    $qrCodeRaw = file_get_contents($qrPath);
                }
            }

            $pdf = Pdf::loadView('pdf.surat-keluar', [
                'surat'          => $suratKeluar,
                'kontenTemplate' => $kontenTemplate,
                'qrCode'         => $qrCodeRaw,
                'profil'         => $profil,
            ]);
            $pdf->setPaper('A4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($suratKeluar->status_approval === 'approved' ? 'surat-keluar' : 'preview_draft') . '.pdf"',
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Preview Draft Exception: ' . $e->getMessage());
            return response('Error memproses pratinjau PDF: ' . $e->getMessage(), 500);
        }
    }
}
