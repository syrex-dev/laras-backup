<?php

namespace App\Http\Controllers\Letters;

use App\Domain\Letters\Actions\CreateSuratMasukAction;
use App\Domain\Letters\Actions\UpdateSuratMasukAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Letters\StoreSuratMasukRequest;
use App\Http\Requests\Letters\UpdateSuratMasukRequest;
use App\Models\KlasifikasiSurat;
use App\Models\SuratMasuk;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SuratMasukController extends Controller
{
    use Loggable;

    public function index(Request $request)
    {
        $query = SuratMasuk::query()->with(['user', 'klasifikasi', 'disposisi'])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('pengirim', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        if ($request->filled('klasifikasi_id')) {
            $query->where('klasifikasi_id', $request->input('klasifikasi_id'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'disposisi') {
                $query->has('disposisi');
            } elseif ($request->input('status') === 'belum_disposisi') {
                $query->doesntHave('disposisi');
            }
        }

        $suratMasuk = $query->paginate(10)->withQueryString();
        $klasifikasiList = KlasifikasiSurat::orderBy('nama')->get();

        return view('letters.surat-masuk.index', compact('suratMasuk', 'klasifikasiList'));
    }

    public function create()
    {
        return view('letters.surat-masuk.create');
    }

    public function store(StoreSuratMasukRequest $request, CreateSuratMasukAction $createAction)
    {
        $createAction->execute($request->validated(), $request->file('file_surat'), Auth::id());

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil ditambahkan.');
    }

    public function show(SuratMasuk $suratMasuk)
    {
        $suratMasuk->load(['user', 'disposisi.pengirim', 'disposisi.penerima']);

        return view('letters.surat-masuk.show', compact('suratMasuk'));
    }

    public function edit(SuratMasuk $suratMasuk)
    {
        return view('letters.surat-masuk.edit', compact('suratMasuk'));
    }

    public function update(UpdateSuratMasukRequest $request, SuratMasuk $suratMasuk, UpdateSuratMasukAction $updateAction)
    {
        $updateAction->execute($suratMasuk, $request->validated(), $request->file('file_surat'));
        $this->logActivity('updated', 'surat_masuk', $suratMasuk->id);

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil diperbarui.');
    }

    public function destroy(SuratMasuk $suratMasuk)
    {
        if ($suratMasuk->file_path && Storage::disk('public')->exists($suratMasuk->file_path)) {
            Storage::disk('public')->delete($suratMasuk->file_path);
        }

        $id = $suratMasuk->id;
        $suratMasuk->delete();
        $this->logActivity('deleted', 'surat_masuk', $id);

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus.');
    }
}
