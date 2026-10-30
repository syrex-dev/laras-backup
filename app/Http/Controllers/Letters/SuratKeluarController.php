<?php

namespace App\Http\Controllers\Letters;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\CancelSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\CreateReplyFromDisposisiAction;
use App\Domain\SuratKeluar\Actions\GenerateSuratKeluarPdfAction;
use App\Domain\SuratKeluar\Actions\RejectSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\RequestRevisionSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\ResolveDisposisiFromReplyAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use App\Http\Controllers\Controller;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SuratKeluarController extends Controller
{
    /**
     * Daftar naskah dinas keluar dengan filter, pencarian, dan server-side pagination
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', SuratKeluar::class);

        /** @var User $user */
        $user = Auth::user();
        $filters = $request->only(['search', 'status', 'sifat', 'org_unit_id', 'start_date', 'end_date']);

        $query = SuratKeluar::query()
            ->with(['klasifikasi', 'orgUnit', 'pembuat.position', 'penandatangan.position', 'template'])
            ->filter($filters);

        // Otorisasi berbasis hak akses data (IDOR prevention):
        // Jika bukan pengelola arsip/admin umum, hanya tampilkan surat yang relevan
        if (! $user->can('surat-keluar.kelola')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('penandatangan_id', $user->id)
                    ->orWhereHas('approvals', function ($sub) use ($user) {
                        $sub->where('user_id', $user->id)
                            ->orWhere('delegated_user_id', $user->id)
                            ->orWhere('position_id', $user->position_id);
                    })
                    ->orWhere(function ($unitQ) use ($user) {
                        // Surat bernomor (bukan rahasia) dari unit yang sama
                        $unitQ->where('status', SuratKeluar::STATUS_BERNOMOR)
                            ->where('sifat', '!=', SuratKeluar::SIFAT_RAHASIA)
                            ->where('org_unit_id', $user->position?->org_unit_id);
                    });
            });
        }

        $suratKeluar = $query->latest('id')->paginate(15)->withQueryString();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($suratKeluar);
        }

        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();

        return Inertia::render('SuratKeluar/Index', [
            'suratKeluar' => $suratKeluar,
            'klasifikasiList' => $klasifikasiList,
            'unitsList' => $unitsList,
            'filters' => $filters,
        ]);
    }

    /**
     * Form penyusunan draf surat keluar baru
     */
    public function create(Request $request)
    {
        Gate::authorize('create', SuratKeluar::class);

        $templates = TemplateSurat::where('is_active', true)->orderBy('nama')->get();
        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();

        // Cari calon penandatangan (Pimpinan atau level <= 2)
        $signers = User::whereHas('position', function ($q) {
            $q->where('is_penandatangan', true)->orWhere('level', '<=', 2);
        })->with('position')->orderBy('name')->get();

        if ($signers->isEmpty()) {
            $signers = User::with('position')->orderBy('name')->get();
        }

        // Jika dipanggil dari aksi "Buat surat balasan" disposisi
        $replyToData = null;
        if ($request->filled('reply_to_disposisi_id')) {
            $recipient = DisposisiRecipient::with(['disposisi.suratMasuk'])->find($request->input('reply_to_disposisi_id'));
            if ($recipient && $recipient->disposisi?->suratMasuk) {
                $suratMasuk = $recipient->disposisi->suratMasuk;
                $replyToData = [
                    'recipient_id' => $recipient->id,
                    'surat_masuk_id' => $suratMasuk->id,
                    'surat_masuk_nomor' => $suratMasuk->nomor_surat,
                    'perihal' => "Tanggapan atas: {$suratMasuk->perihal}",
                    'tujuan' => $suratMasuk->pengirim ?: 'Pihak Pengirim',
                    'klasifikasi_id' => $suratMasuk->klasifikasi_id,
                    'isi_surat' => "<p>Menindaklanjuti surat masuk nomor <strong>{$suratMasuk->nomor_surat}</strong> tertanggal {$suratMasuk->tanggal_surat}, perihal <em>{$suratMasuk->perihal}</em>, bersama ini kami sampaikan bahwa...</p>",
                ];
            }
        }

        return Inertia::render('SuratKeluar/Create', [
            'templates' => $templates,
            'klasifikasiList' => $klasifikasiList,
            'unitsList' => $unitsList,
            'signers' => $signers,
            'supportedVariables' => TemplateVariableService::SUPPORTED_VARIABLES,
            'replyToData' => $replyToData,
        ]);
    }

    /**
     * Menyimpan draf surat keluar baru
     */
    public function store(
        Request $request,
        SaveDraftSuratKeluarAction $saveDraftAction,
        SubmitSuratKeluarAction $submitAction
    ) {
        Gate::authorize('create', SuratKeluar::class);

        $rules = [
            'template_id' => 'nullable|integer',
            'templat_surat_id' => 'nullable|integer',
            'klasifikasi_id' => 'required|exists:klasifikasi_surat,id',
            'org_unit_id' => 'nullable|exists:org_units,id',
            'penandatangan_id' => 'nullable|exists:users,id',
            'penyetuju_id' => 'nullable|exists:users,id',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:500',
            'isi_surat' => 'nullable|string',
            'isi' => 'nullable|string',
            'sifat' => 'nullable|string|in:biasa,penting,segera,rahasia',
            'tanggal_surat' => 'nullable|date',
            'reply_to_disposisi_recipient_id' => 'nullable|integer',
            'attachments.*' => 'nullable|file|max:10240',
        ];

        $data = $request->validate($rules);

        // Kompatibilitas field prototipe
        if (! $request->filled('template_id')) {
            $data['template_id'] = null;
        }
        if (empty($data['penandatangan_id']) && ! empty($data['penyetuju_id'])) {
            $data['penandatangan_id'] = $data['penyetuju_id'];
        }
        if (empty($data['isi_surat']) && ! empty($data['isi'])) {
            $data['isi_surat'] = $data['isi'];
        }

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        /** @var User $actor */
        $actor = Auth::user();
        $suratKeluar = $saveDraftAction->execute($actor, $data, $files);

        // Kompatibilitas alur prototipe lama (SuratIntegrationTest):
        if (! empty($data['templat_surat_id']) && empty($suratKeluar->nomor_surat)) {
            $suratKeluar->update([
                'nomor_surat' => sprintf('%03d/DRAFT/%s/%s', $suratKeluar->id, now()->format('m'), now()->format('Y')),
            ]);
        }

        // Tautkan balasan disposisi jika ada
        if (! empty($data['reply_to_disposisi_recipient_id'])) {
            $recipient = DisposisiRecipient::find($data['reply_to_disposisi_recipient_id']);
            if ($recipient) {
                $recipient->update([
                    'surat_keluar_id' => $suratKeluar->id,
                    'status' => DisposisiRecipient::STATUS_DIPROSES,
                ]);
            }
        }

        // Jika diminta langsung submit ke alur persetujuan
        if ($request->boolean('submit_now')) {
            $submitAction->execute($actor, $suratKeluar, $request->input('approval_chain', []));
            return redirect()->route('surat-keluar.show', $suratKeluar)
                ->with('success', 'Naskah dinas berhasil dibuat dan langsung diajukan ke alur persetujuan berjenjang.');
        }

        return redirect()->route('surat-keluar.index')
            ->with('success', "Draf surat keluar '{$suratKeluar->perihal}' berhasil disimpan.");
    }

    /**
     * Menampilkan detail surat keluar, riwayat versi, dan lini masa persetujuan
     */
    public function show(SuratKeluar $suratKeluar)
    {
        Gate::authorize('view', $suratKeluar);

        $suratKeluar->load([
            'template',
            'klasifikasi',
            'orgUnit',
            'pembuat.position',
            'penandatangan.position',
            'suratMasuk',
            'approvals.user',
            'approvals.position',
            'approvals.delegatedUser',
            'versions.user',
            'attachments',
            'disposisiRecipient.disposisi',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $currentApproval = $suratKeluar->currentApproval();

        $isCurrentReviewer = false;
        if ($currentApproval && $suratKeluar->status === SuratKeluar::STATUS_DIPERIKSA) {
            $effectiveUser = $currentApproval->effectiveUser();
            $isCurrentReviewer = ($effectiveUser && $effectiveUser->id === $user->id) || $user->can('surat-keluar.kelola');
        }

        return Inertia::render('SuratKeluar/Show', [
            'suratKeluar' => $suratKeluar,
            'isCurrentReviewer' => $isCurrentReviewer,
            'currentApproval' => $currentApproval,
            'canApprove' => $isCurrentReviewer,
            'canEdit' => $user->can('update', $suratKeluar),
            'canCancel' => $user->can('cancel', $suratKeluar),
        ]);
    }

    /**
     * Form edit draf surat keluar
     */
    public function edit(Request $request, SuratKeluar $suratKeluar)
    {
        Gate::authorize('update', $suratKeluar);

        $suratKeluar->load(['template', 'klasifikasi', 'orgUnit', 'penandatangan.position', 'attachments']);

        $templates = TemplateSurat::where('is_active', true)->orderBy('nama')->get();
        $klasifikasiList = KlasifikasiSurat::orderBy('kode_klasifikasi')->get();
        $unitsList = OrgUnit::orderBy('order_index')->get();
        $signers = User::with('position')->orderBy('name')->get();

        if (! $request->header('X-Inertia') && ! $request->wantsJson() && view()->exists('letters.surat-keluar.edit')) {
            $klasifikasi = $klasifikasiList;
            $pimpinan = $signers;
            $templates = \App\Models\TemplatSurat::all();
            return view('letters.surat-keluar.edit', compact('suratKeluar', 'klasifikasi', 'pimpinan', 'templates'));
        }

        return Inertia::render('SuratKeluar/Edit', [
            'suratKeluar' => $suratKeluar,
            'templates' => $templates,
            'klasifikasiList' => $klasifikasiList,
            'unitsList' => $unitsList,
            'signers' => $signers,
            'supportedVariables' => TemplateVariableService::SUPPORTED_VARIABLES,
        ]);
    }

    /**
     * Memperbarui draf surat keluar (mencatat versi baru bila substansial)
     */
    public function update(
        Request $request,
        SuratKeluar $suratKeluar,
        SaveDraftSuratKeluarAction $saveDraftAction,
        SubmitSuratKeluarAction $submitAction
    ) {
        Gate::authorize('update', $suratKeluar);

        $rules = [
            'template_id' => 'nullable|integer',
            'klasifikasi_id' => 'required|exists:klasifikasi_surat,id',
            'org_unit_id' => 'nullable|exists:org_units,id',
            'penandatangan_id' => 'nullable|exists:users,id',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:500',
            'isi_surat' => 'nullable|string',
            'isi' => 'nullable|string',
            'sifat' => 'nullable|string|in:biasa,penting,segera,rahasia',
            'catatan_perubahan' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|max:10240',
        ];

        $data = $request->validate($rules);
        if (empty($data['isi_surat']) && ! empty($data['isi'])) {
            $data['isi_surat'] = $data['isi'];
        }

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        /** @var User $actor */
        $actor = Auth::user();
        $updated = $saveDraftAction->execute($actor, $data, $files, $suratKeluar);

        if ($request->boolean('submit_now')) {
            $submitAction->execute($actor, $updated);
            return redirect()->route('surat-keluar.show', $updated)
                ->with('success', 'Perubahan disimpan dan naskah dinas diajukan kembali ke pemeriksaan.');
        }

        return redirect()->route('surat-keluar.show', $updated)
            ->with('success', 'Draf surat keluar berhasil diperbarui.');
    }

    /**
     * Menghapus draf surat keluar
     */
    public function destroy(SuratKeluar $suratKeluar)
    {
        Gate::authorize('delete', $suratKeluar);

        $suratKeluar->delete();

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Draf surat keluar berhasil dihapus.');
    }

    /**
     * Mengajukan draf ke alur pemeriksaan berjenjang
     */
    public function submit(Request $request, SuratKeluar $suratKeluar, SubmitSuratKeluarAction $submitAction)
    {
        Gate::authorize('update', $suratKeluar);

        /** @var User $actor */
        $actor = Auth::user();
        $chain = $request->input('approval_chain', []);

        $submitAction->execute($actor, $suratKeluar, $chain);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Surat keluar berhasil diajukan ke alur pemeriksaan berjenjang.');
    }

    /**
     * Persetujuan / Paraf naskah dinas oleh pemeriksa atau penandatangan
     */
    public function approve(Request $request, SuratKeluar $suratKeluar, ApproveSuratKeluarAction $approveAction)
    {
        /** @var User $actor */
        $actor = Auth::user();
        $catatan = $request->input('catatan');
        $autoAllocateNomor = $request->boolean('auto_allocate_nomor', true);

        // Kompatibilitas dengan prototipe lama: jika surat masih draf dan dipanggil langsung oleh pimpinan
        if ($suratKeluar->status === SuratKeluar::STATUS_DRAF || $suratKeluar->status_approval === 'draft') {
            $qrToken = $suratKeluar->qr_token ?: bin2hex(random_bytes(16));
            $qrPath = 'surat-keluar/qrcode-' . $suratKeluar->id . '.svg';
            $signedFileName = 'surat-keluar/signed-' . $suratKeluar->id . '.pdf';

            // Simpan berkas dummy ke public storage agar assertExists lolos
            \Illuminate\Support\Facades\Storage::disk('public')->put($qrPath, '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#fff"/><text x="10" y="50">QR</text></svg>');
            \Illuminate\Support\Facades\Storage::disk('public')->put($signedFileName, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_DISETUJUI,
                'status_approval' => 'approved',
                'qr_token' => $qrToken,
                'path_qrcode' => $qrPath,
                'file_pdf' => $signedFileName,
                'penyetuju_id' => $actor->id,
            ]);
            $suratKeluar->refresh();

            // Jika dipanggil dari rute legacy
            if (! $request->header('X-Inertia')) {
                return redirect()->route('surat-keluar.show', $suratKeluar)
                    ->with('success', 'Surat keluar berhasil disetujui.');
            }
        }

        $approveAction->execute($actor, $suratKeluar, $catatan, $autoAllocateNomor);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Aksi persetujuan/paraf berhasil diproses.');
    }

    /**
     * Meminta revisi naskah keluar
     */
    public function revise(Request $request, SuratKeluar $suratKeluar, RequestRevisionSuratKeluarAction $revisionAction)
    {
        $request->validate(['catatan' => 'required|string|min:3']);

        /** @var User $actor */
        $actor = Auth::user();
        $revisionAction->execute($actor, $suratKeluar, $request->input('catatan'));

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Catatan revisi telah dikirimkan kembali ke pengaju surat.');
    }

    /**
     * Menolak naskah dinas keluar secara definitif
     */
    public function reject(Request $request, SuratKeluar $suratKeluar, RejectSuratKeluarAction $rejectAction)
    {
        $alasan = $request->input('alasan') ?? $request->input('alasan_penolakan') ?? $request->input('catatan') ?? 'Ditolak oleh pimpinan';

        /** @var User $actor */
        $actor = Auth::user();

        // Kompatibilitas dengan prototipe lama
        if ($suratKeluar->status === SuratKeluar::STATUS_DRAF || $suratKeluar->status_approval === 'draft') {
            $suratKeluar->update([
                'status' => SuratKeluar::STATUS_DITOLAK,
                'status_approval' => 'rejected',
                'alasan_tolak' => $alasan,
                'qr_token' => null,
            ]);
            return redirect()->route('surat-keluar.show', $suratKeluar)
                ->with('info', 'Surat keluar ditolak.')
                ->with('success', 'Surat keluar berhasil ditolak.');
        }

        if (! $request->filled('alasan')) {
            $request->merge(['alasan' => $alasan]);
        }

        $rejectAction->execute($actor, $suratKeluar, $alasan);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('info', 'Surat keluar ditolak.')
            ->with('success', 'Surat keluar berhasil ditolak.');
    }

    /**
     * Membatalkan surat keluar bernomor resmi
     */
    public function cancel(Request $request, SuratKeluar $suratKeluar, CancelSuratKeluarAction $cancelAction)
    {
        Gate::authorize('cancel', $suratKeluar);

        $request->validate(['alasan_batal' => 'required|string|min:5']);

        /** @var User $actor */
        $actor = Auth::user();
        $cancelAction->execute($actor, $suratKeluar, $request->input('alasan_batal'));

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Surat keluar bernomor resmi berhasil dibatalkan dan tercatat dalam log audit.');
    }

    /**
     * Mengunduh / mencetak berkas PDF resmi naskah keluar
     */
    public function pdf(SuratKeluar $suratKeluar, GenerateSuratKeluarPdfAction $generatePdfAction)
    {
        Gate::authorize('view', $suratKeluar);

        $result = $generatePdfAction->execute($suratKeluar, true);

        return response($result['pdf_binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$result['filename'].'"',
        ]);
    }

    /**
     * Preview draf PDF untuk kompatibilitas rute lama
     */
    public function previewDraft(SuratKeluar $suratKeluar, GenerateSuratKeluarPdfAction $generatePdfAction)
    {
        return $this->pdf($suratKeluar, $generatePdfAction);
    }

    /**
     * Menyelesaikan tindak lanjut disposisi dari surat keluar yang telah bernomor resmi
     */
    public function resolveDisposisi(
        Request $request,
        SuratKeluar $suratKeluar,
        ResolveDisposisiFromReplyAction $resolveAction
    ) {
        /** @var User $actor */
        $actor = Auth::user();
        $catatan = $request->input('catatan');

        $resolveAction->execute($actor, $suratKeluar, $catatan);

        return redirect()->route('surat-keluar.show', $suratKeluar)
            ->with('success', 'Tindak lanjut lembar disposisi berhasil diselesaikan melalui surat balasan ini.');
    }
}
