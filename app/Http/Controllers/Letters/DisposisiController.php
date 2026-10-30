<?php

namespace App\Http\Controllers\Letters;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Letters\Actions\UpdateDisposisiStatusAction;
use App\Domain\Letters\Support\DisposisiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Letters\StoreDisposisiRequest;
use App\Http\Requests\Letters\UpdateDisposisiStatusRequest;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\DisposisiSelesai;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DisposisiController extends Controller
{
    use Loggable;

    public function index()
    {
        $query = Disposisi::query()->with(['suratMasuk', 'pengirim', 'penerima'])->latest();

        /** @var User $user */
        $user = Auth::user();

        if ($user->can('disposisi.kelola')) {
            $disposisi = $query->paginate(10);
        } elseif ($user->can('disposisi.buat')) {
            $disposisi = $query->where('pengirim_id', $user->id)->paginate(10);
        } else {
            $disposisi = $query->where('penerima_id', $user->id)->paginate(10);
        }

        return view('letters.disposisi.index', compact('disposisi'));
    }

    public function create()
    {
        $suratMasuk = SuratMasuk::all();
        $users = User::all();

        return view('letters.disposisi.create', compact('suratMasuk', 'users'));
    }

    public function store(StoreDisposisiRequest $request, CreateDisposisiAction $createAction)
    {
        /** @var User $sender */
        $sender = Auth::user();
        $validated = $request->validated();
        $suratMasuk = \App\Domain\SuratMasuk\Models\SuratMasuk::findOrFail($validated['surat_masuk_id']);

        $createAction->execute(
            sender: $sender,
            suratMasuk: $suratMasuk,
            data: [
                'instruksi' => $validated['instruksi'] ?? 'Segera tindak lanjuti',
                'catatan' => $validated['catatan'] ?? null,
                'prioritas' => $validated['prioritas'] ?? 'biasa',
                'tenggat_waktu' => $validated['tenggat_waktu'] ?? null,
                'recipients' => [
                    ['user_id' => $validated['penerima_id'] ?? null],
                ],
            ]
        );

        return redirect()->route('disposisi.index')
            ->with('success', 'Disposisi berhasil dikirim.');
    }

    public function show(Disposisi $disposisi, UpdateDisposisiStatusAction $statusAction)
    {
        /** @var User $user */
        $user = Auth::user();

        $currentUserId = (int) $user->id;
        $penerimaId = (int) $disposisi->penerima_id;
        $pengirimId = (int) $disposisi->pengirim_id;

        $isAdmin = $user->can('disposisi.kelola');
        $isRecipient = $currentUserId === $penerimaId;
        $isSender = $currentUserId === $pengirimId;

        if (! $isAdmin && ! $isRecipient && ! $isSender) {
            abort(403, 'Unauthorized');
        }

        $disposisi->load(['suratMasuk', 'pengirim', 'penerima']);

        if ($user->id === $disposisi->penerima_id && $disposisi->status === DisposisiStatus::BELUM_DIBACA) {
            $statusAction->markAsRead($disposisi);
        }

        return view('letters.disposisi.show', compact('disposisi'));
    }

    public function updateStatus(UpdateDisposisiStatusRequest $request, Disposisi $disposisi)
    {
        /** @var User $user */
        $user = Auth::user();

        if ((int) $user->id !== (int) $disposisi->penerima_id) {
            abort(403, 'Unauthorized');
        }

        try {
            $status = $request->validated()['status'];
            $data = ['status' => $status];

            if ($status === 'selesai' && ! $disposisi->selesai_pada) {
                $data['selesai_pada'] = now();

                $pimpinan = $disposisi->pengirim;
                if ($pimpinan) {
                    $pimpinan->notify(new DisposisiSelesai($disposisi));
                }
            }

            $disposisi->update($data);
            $this->logActivity('updated_status', 'disposisi', $disposisi->id);

            return back()->with('success', 'Status disposisi berhasil diperbarui dan notifikasi telah dikirim.');
        } catch (\Exception $e) {
            Log::error('Disposisi Status Error: '.$e->getMessage());

            return back()->with('error', 'Gagal memperbarui status: '.$e->getMessage());
        }
    }

    public function destroy(Disposisi $disposisi)
    {
        $disposisi->delete();

        return redirect()->route('disposisi.index')
            ->with('success', 'Disposisi berhasil dihapus.');
    }
}
