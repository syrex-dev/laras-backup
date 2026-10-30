<?php

namespace App\Http\Controllers\Letters;

use App\Domain\Audit\Models\ActivityLog;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Domain\SuratMasuk\Models\SuratMasukAttachment;
use App\Domain\SuratMasuk\Services\SuratMasukService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuratMasuk\CancelSuratMasukRequest;
use App\Http\Requests\SuratMasuk\StoreSuratMasukRequest;
use App\Http\Requests\SuratMasuk\UpdateSuratMasukRequest;
use App\Models\KlasifikasiSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuratMasukController extends Controller
{
    public function __construct(
        protected SuratMasukService $suratMasukService
    ) {}

    /**
     * Halaman daftar surat masuk dengan server-side pagination, filter, dan search tanpa N+1.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', SuratMasuk::class);

        $filters = $request->only(['search', 'status', 'sifat', 'unit_id', 'start_date', 'end_date']);

        $suratMasuk = SuratMasuk::query()
            ->with(['klasifikasi', 'unitTujuan', 'user', 'attachments', 'disposisi'])
            ->filter($filters)
            ->latest('tanggal_diterima')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'suratMasuk' => $suratMasuk,
                'klasifikasiList' => $klasifikasiList,
                'unitsList' => $unitsList,
            ]);
        }

        if ($request->header('X-Inertia')) {
            return Inertia::render('SuratMasuk/Index', [
                'suratMasuk' => $suratMasuk,
                'klasifikasiList' => $klasifikasiList,
                'unitsList' => $unitsList,
                'filters' => $filters,
            ]);
        }

        // Blade fallback for legacy tests
        return view('letters.surat-masuk.index', [
            'suratMasuk' => $suratMasuk,
            'klasifikasiList' => $klasifikasiList,
        ]);
    }

    /**
     * Formulir registrasi naskah surat masuk baru.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', SuratMasuk::class);

        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();

        if ($request->header('X-Inertia')) {
            return Inertia::render('SuratMasuk/Create', [
                'klasifikasiList' => $klasifikasiList,
                'unitsList' => $unitsList,
            ]);
        }

        return view('letters.surat-masuk.create', compact('klasifikasiList'));
    }

    /**
     * Simpan naskah surat masuk dengan penomoran agenda atomik dan lampiran privat.
     */
    public function store(StoreSuratMasukRequest $request)
    {
        $files = $request->file('lampiran', []);
        if (! is_array($files) && $files) {
            $files = [$files];
        }

        $suratMasuk = $this->suratMasukService->create(
            data: $request->validated(),
            files: $files,
            userId: auth()->id()
        );

        return redirect()->route('surat-masuk.show', $suratMasuk->id)
            ->with('success', "Surat masuk berhasil diregistrasi dengan nomor agenda: {$suratMasuk->nomor_agenda}!");
    }

    /**
     * Halaman detail surat masuk beserta daftar lampiran privat dan riwayat jejak audit.
     */
    public function show(SuratMasuk $suratMasuk, Request $request)
    {
        Gate::authorize('view', $suratMasuk);

        $suratMasuk->load([
            'klasifikasi',
            'unitTujuan',
            'user',
            'dibatalkanOleh',
            'attachments.uploader',
        ]);

        // Ambil riwayat audit log khusus surat masuk ini
        $auditLogs = ActivityLog::where('subject_type', SuratMasuk::class)
            ->where('subject_id', $suratMasuk->id)
            ->with('causer')
            ->latest('id')
            ->get();

        // Ambil rantai pohon disposisi berjenjang
        $disposisiChain = \App\Domain\Disposisi\Models\Disposisi::with([
            'pemberi',
            'pemberiPosition',
            'recipients.recipientUser',
            'recipients.recipientPosition',
            'recipients.delegatedUser',
            'recipients.followups.attachments',
            'recipients.followups.user',
        ])
        ->where('surat_masuk_id', $suratMasuk->id)
        ->orderBy('created_at', 'asc')
        ->get();

        if ($request->has('legacy_view')) {
            return view('letters.surat-masuk.show', compact('suratMasuk'));
        }

        return Inertia::render('SuratMasuk/Show', [
            'surat' => $suratMasuk,
            'auditLogs' => $auditLogs,
            'disposisiChain' => $disposisiChain,
        ]);
    }

    /**
     * Formulir koreksi / ubah registrasi surat masuk.
     */
    public function edit(SuratMasuk $suratMasuk, Request $request)
    {
        Gate::authorize('update', $suratMasuk);

        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();

        if ($request->header('X-Inertia')) {
            return Inertia::render('SuratMasuk/Edit', [
                'surat' => $suratMasuk->load(['klasifikasi', 'unitTujuan', 'attachments']),
                'klasifikasiList' => $klasifikasiList,
                'unitsList' => $unitsList,
            ]);
        }

        return view('letters.surat-masuk.edit', compact('suratMasuk', 'klasifikasiList'));
    }

    /**
     * Perbarui registrasi surat masuk.
     */
    public function update(UpdateSuratMasukRequest $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('update', $suratMasuk);

        $files = $request->file('lampiran', []);
        if (! is_array($files) && $files) {
            $files = [$files];
        }

        $this->suratMasukService->update(
            suratMasuk: $suratMasuk,
            data: $request->validated(),
            files: $files,
            userId: auth()->id()
        );

        return redirect()->route('surat-masuk.show', $suratMasuk->id)
            ->with('success', 'Data surat masuk berhasil diperbarui!');
    }

    /**
     * Batalkan surat masuk dengan alasan wajib (Status dibatalkan, bukan hard delete).
     */
    public function cancel(CancelSuratMasukRequest $request, SuratMasuk $suratMasuk)
    {
        Gate::authorize('cancel', $suratMasuk);

        $this->suratMasukService->cancel(
            suratMasuk: $suratMasuk,
            reason: $request->input('alasan_batal'),
            userId: auth()->id()
        );

        return back()->with('success', "Surat masuk agenda {$suratMasuk->nomor_agenda} telah dibatalkan.");
    }

    /**
     * Unduh berkas lampiran privat dengan verifikasi otorisasi.
     */
    public function downloadAttachment(SuratMasuk $suratMasuk, SuratMasukAttachment $attachment): BinaryFileResponse
    {
        Gate::authorize('view', $suratMasuk);

        if ($attachment->surat_masuk_id !== $suratMasuk->id) {
            abort(404, 'Lampiran tidak ditemukan pada surat ini.');
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Berkas fisik lampiran tidak ditemukan di penyimpanan privat.');
        }

        $absolutePath = Storage::disk('local')->path($attachment->file_path);

        return response()->download($absolutePath, $attachment->file_name);
    }

    /**
     * Pratinjau langsung berkas lampiran privat di browser (inline).
     */
    public function previewAttachment(SuratMasuk $suratMasuk, SuratMasukAttachment $attachment): BinaryFileResponse
    {
        Gate::authorize('view', $suratMasuk);

        if ($attachment->surat_masuk_id !== $suratMasuk->id) {
            abort(404, 'Lampiran tidak ditemukan pada surat ini.');
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Berkas fisik lampiran tidak ditemukan.');
        }

        $absolutePath = Storage::disk('local')->path($attachment->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    /**
     * Cetak Lembar Disposisi Ber-QR Code (PDF)
     */
    public function printLembarDisposisi(SuratMasuk $suratMasuk)
    {
        Gate::authorize('view', $suratMasuk);

        $pdf = $this->suratMasukService->renderLembarDisposisiPdf($suratMasuk);

        return $pdf->stream('Lembar-Disposisi-'.$suratMasuk->nomor_agenda.'.pdf');
    }

    /**
     * Cetak Bukti Terima Surat Masuk Ber-QR Code (PDF)
     */
    public function printBuktiTerima(SuratMasuk $suratMasuk)
    {
        Gate::authorize('view', $suratMasuk);

        $pdf = $this->suratMasukService->renderBuktiTerimaPdf($suratMasuk);

        return $pdf->stream('Bukti-Terima-'.$suratMasuk->nomor_agenda.'.pdf');
    }

    /**
     * Hapus permanen / soft delete surat masuk (Hanya Admin Instansi).
     */
    public function destroy(SuratMasuk $suratMasuk)
    {
        Gate::authorize('delete', $suratMasuk);

        $nomorAgenda = $suratMasuk->nomor_agenda;
        $suratMasuk->delete();

        return redirect()->route('surat-masuk.index')
            ->with('success', "Surat masuk agenda {$nomorAgenda} berhasil dihapus.");
    }
}
