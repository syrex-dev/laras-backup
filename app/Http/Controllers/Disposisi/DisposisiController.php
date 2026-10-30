<?php

namespace App\Http\Controllers\Disposisi;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Actions\FollowupDisposisiAction;
use App\Domain\Disposisi\Actions\ForwardDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiAttachment;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DisposisiController extends Controller
{
    /**
     * Kotak Masuk Disposisi (Inbox & Sent) dengan Filter dan Indikator Tenggat
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $tab = $request->input('tab', 'inbox'); // inbox | sent
        $status = $request->input('status', 'semua');
        $prioritas = $request->input('prioritas', 'semua');
        $search = $request->input('search');

        if ($tab === 'sent') {
            $query = Disposisi::query()
                ->with([
                    'suratMasuk',
                    'recipients.recipientUser',
                    'recipients.recipientPosition',
                    'recipients.delegatedUser',
                ])
                ->where('pemberi_id', $user->id)
                ->latest();

            if ($status !== 'semua') {
                $query->where('status', $status);
            }
            if ($prioritas !== 'semua') {
                $query->where('prioritas', $prioritas);
            }
            if ($search) {
                $query->whereHas('suratMasuk', function ($q) use ($search) {
                    $q->where('perihal', 'like', "%{$search}%")
                      ->orWhere('nomor_agenda', 'like', "%{$search}%")
                      ->orWhere('pengirim', 'like', "%{$search}%");
                });
            }

            $items = $query->paginate(15)->withQueryString();
        } else {
            // Kotak Masuk: Disposisi yang ditujukan ke user (definitif atau Plt)
            $query = DisposisiRecipient::query()
                ->with([
                    'disposisi.suratMasuk.attachments',
                    'disposisi.pemberi',
                    'disposisi.pemberiPosition',
                    'recipientPosition',
                    'delegatedUser',
                    'recipientUser',
                ])
                ->where(function ($q) use ($user) {
                    $q->where('recipient_user_id', $user->id)
                      ->orWhere('delegated_user_id', $user->id);
                })
                ->latest();

            if ($status === 'terlambat') {
                $query->whereNotIn('status', [
                    DisposisiRecipient::STATUS_SELESAI,
                    DisposisiRecipient::STATUS_DITOLAK,
                    DisposisiRecipient::STATUS_DIKEMBALIKAN,
                ])->whereHas('disposisi', function ($q) {
                    $q->whereNotNull('tenggat_waktu')
                      ->where('tenggat_waktu', '<', now()->toDateString());
                });
            } elseif ($status !== 'semua') {
                $query->where('status', $status);
            }

            if ($prioritas !== 'semua') {
                $query->whereHas('disposisi', fn ($q) => $q->where('prioritas', $prioritas));
            }

            if ($search) {
                $query->whereHas('disposisi.suratMasuk', function ($q) use ($search) {
                    $q->where('perihal', 'like', "%{$search}%")
                      ->orWhere('nomor_agenda', 'like', "%{$search}%")
                      ->orWhere('pengirim', 'like', "%{$search}%");
                });
            }

            $items = $query->paginate(15)->withQueryString();

            // Transformasikan atribut pembantu untuk tampilan
            $items->getCollection()->transform(function ($item) {
                $item->is_overdue = $item->isOverdue();
                $item->is_due_soon = $item->isDueSoon(1);
                $item->display_name = $item->display_name;
                $item->delegation_label = $item->delegation_label;
                return $item;
            });
        }

        // Statistik ringkas untuk tab badge
        $unreadCount = DisposisiRecipient::where(function ($q) use ($user) {
            $q->where('recipient_user_id', $user->id)->orWhere('delegated_user_id', $user->id);
        })->where('status', DisposisiRecipient::STATUS_BELUM_DIBACA)->count();

        $overdueCount = DisposisiRecipient::where(function ($q) use ($user) {
            $q->where('recipient_user_id', $user->id)->orWhere('delegated_user_id', $user->id);
        })->whereNotIn('status', [
            DisposisiRecipient::STATUS_SELESAI,
            DisposisiRecipient::STATUS_DITOLAK,
            DisposisiRecipient::STATUS_DIKEMBALIKAN,
        ])->whereHas('disposisi', function ($q) {
            $q->whereNotNull('tenggat_waktu')->where('tenggat_waktu', '<', now()->toDateString());
        })->count();

        // Data pendukung untuk Modal Disposisi Cepat Mobile (<= 3 Ketukan)
        $instructionTemplates = InstructionTemplate::active()->get();
        $subordinatePositions = Position::with(['orgUnit', 'users'])
            ->where('id', '!=', $user->position_id ?? 0)
            ->get();

        return Inertia::render('Disposisi/Index', [
            'items' => $items,
            'filters' => [
                'tab' => $tab,
                'status' => $status,
                'prioritas' => $prioritas,
                'search' => $search,
            ],
            'stats' => [
                'unread_count' => $unreadCount,
                'overdue_count' => $overdueCount,
            ],
            'instructionTemplates' => $instructionTemplates,
            'subordinatePositions' => $subordinatePositions,
        ]);
    }

    /**
     * Halaman Detail Lembar Disposisi & Tindak Lanjut
     */
    public function show(DisposisiRecipient $recipient): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $effectiveUser = $recipient->effectiveUser();
        $isRecipient = $effectiveUser && $effectiveUser->id === $user->id;
        $isSender = $recipient->disposisi->pemberi_id === $user->id;
        $isAdmin = $user->hasRole(['Admin Instansi', 'Admin']);

        if (! $isRecipient && ! $isSender && ! $isAdmin && ! $user->can('disposisi.lihat')) {
            abort(403, 'Anda tidak memiliki hak akses melihat disposisi ini.');
        }

        // Tandai dibaca jika pertama kali dibuka oleh penerima
        if ($isRecipient && $recipient->status === DisposisiRecipient::STATUS_BELUM_DIBACA) {
            $recipient->update([
                'status' => DisposisiRecipient::STATUS_DIBACA,
                'dibaca_pada' => now(),
            ]);
        }

        $recipient->load([
            'disposisi.suratMasuk.attachments',
            'disposisi.pemberi',
            'disposisi.pemberiPosition',
            'recipientPosition.orgUnit',
            'delegatedUser',
            'recipientUser',
            'followups.attachments',
            'followups.user',
            'childRecipients.disposisi.recipients',
        ]);

        // Muat pohon lini masa riwayat disposisi lengkap dari root surat
        $timelineTree = $this->getDispositionTimelineTree($recipient->disposisi->surat_masuk_id);

        $instructionTemplates = InstructionTemplate::active()->get();

        // Bawahan yang valid untuk diteruskan
        $subordinatePositions = Position::with(['orgUnit', 'users'])
            ->where('id', '!=', $user->position_id ?? 0)
            ->get();

        return Inertia::render('Disposisi/Show', [
            'recipient' => array_merge($recipient->toArray(), [
                'is_overdue' => $recipient->isOverdue(),
                'is_due_soon' => $recipient->isDueSoon(1),
                'display_name' => $recipient->display_name,
                'delegation_label' => $recipient->delegation_label,
            ]),
            'timelineTree' => $timelineTree,
            'instructionTemplates' => $instructionTemplates,
            'subordinatePositions' => $subordinatePositions,
        ]);
    }

    /**
     * Membuat disposisi awal (root)
     */
    public function store(
        Request $request,
        CreateDisposisiAction $action
    ) {
        $validated = $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuk,id',
            'instruction_template_id' => 'nullable|exists:instruction_templates,id',
            'instruksi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'prioritas' => 'nullable|in:biasa,segera,sangat_segera',
            'tenggat_waktu' => 'nullable|date',
            'recipients' => 'required|array|min:1',
            'recipients.*.position_id' => 'nullable|integer',
            'recipients.*.user_id' => 'nullable|integer',
        ]);

        $suratMasuk = SuratMasuk::findOrFail($validated['surat_masuk_id']);
        $disposisi = $action->execute(Auth::user(), $suratMasuk, $validated);

        return redirect()->back()->with('success', 'Disposisi naskah berhasil dikirim.');
    }

    /**
     * Meneruskan disposisi berjenjang ke bawahan
     */
    public function forward(
        Request $request,
        DisposisiRecipient $recipient,
        ForwardDisposisiAction $action
    ) {
        $validated = $request->validate([
            'instruction_template_id' => 'nullable|exists:instruction_templates,id',
            'instruksi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'prioritas' => 'nullable|in:biasa,segera,sangat_segera',
            'tenggat_waktu' => 'nullable|date',
            'recipients' => 'required|array|min:1',
            'recipients.*.position_id' => 'nullable|integer',
            'recipients.*.user_id' => 'nullable|integer',
        ]);

        $action->execute(Auth::user(), $recipient, $validated);

        return redirect()->back()->with('success', 'Disposisi berhasil diteruskan ke bawahan.');
    }

    /**
     * Menindaklanjuti disposisi (update status, catatan, upload bukti)
     */
    public function followup(
        Request $request,
        DisposisiRecipient $recipient,
        FollowupDisposisiAction $action
    ) {
        $validated = $request->validate([
            'status' => 'required|in:diproses,selesai,ditolak,dikembalikan',
            'catatan' => 'required|string',
            'surat_keluar_id' => 'nullable|integer',
            'lampiran' => 'nullable|file|max:20480', // max 20MB
        ]);

        $attachment = $request->file('lampiran');
        $action->execute(Auth::user(), $recipient, $validated, $attachment);

        return redirect()->back()->with('success', 'Tindak lanjut disposisi berhasil disimpan.');
    }

    /**
     * Mengunduh berkas lampiran bukti tindak lanjut berizin
     */
    public function downloadAttachment(DisposisiAttachment $attachment)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Berkas lampiran bukti tidak ditemukan.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Mengambil struktur lini masa riwayat rantai disposisi secara berjenjang
     */
    protected function getDispositionTimelineTree(int $suratMasukId): array
    {
        $disposisiList = Disposisi::with([
            'pemberi',
            'pemberiPosition',
            'recipients.recipientUser',
            'recipients.recipientPosition',
            'recipients.delegatedUser',
            'recipients.followups.attachments',
            'recipients.followups.user',
        ])
        ->where('surat_masuk_id', $suratMasukId)
        ->orderBy('created_at', 'asc')
        ->get();

        return $disposisiList->toArray();
    }
}
