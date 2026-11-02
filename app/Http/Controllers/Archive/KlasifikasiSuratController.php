<?php

namespace App\Http\Controllers\Archive;

use App\Domain\Archive\Actions\CreateKlasifikasiAction;
use App\Domain\Archive\Actions\UpdateKlasifikasiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Archive\StoreKlasifikasiSuratRequest;
use App\Http\Requests\Archive\UpdateKlasifikasiSuratRequest;
use App\Models\KlasifikasiSurat;
use Inertia\Inertia;

class KlasifikasiSuratController extends Controller
{
    public function index()
    {
        $klasifikasi = KlasifikasiSurat::query()->latest()->paginate(15);

        return Inertia::render('Kearsipan/KlasifikasiSurat', [
            'klasifikasi' => $klasifikasi,
        ]);
    }

    public function create()
    {
        return $this->index();
    }

    public function store(StoreKlasifikasiSuratRequest $request, CreateKlasifikasiAction $createAction)
    {
        $createAction->execute($request->validated());

        return redirect()->route('klasifikasi-surat.index')
            ->with('success', 'Klasifikasi surat berhasil ditambahkan.');
    }

    public function show(KlasifikasiSurat $klasifikasiSurat)
    {
        return $this->index();
    }

    public function edit(KlasifikasiSurat $klasifikasiSurat)
    {
        return $this->index();
    }

    public function update(UpdateKlasifikasiSuratRequest $request, KlasifikasiSurat $klasifikasiSurat, UpdateKlasifikasiAction $updateAction)
    {
        $updateAction->execute($klasifikasiSurat, $request->validated());

        return redirect()->route('klasifikasi-surat.index')
            ->with('success', 'Klasifikasi surat berhasil diperbarui.');
    }

    public function destroy(KlasifikasiSurat $klasifikasiSurat)
    {
        if ($klasifikasiSurat->suratKeluar()->exists() || $klasifikasiSurat->suratMasuk()->exists()) {
            return redirect()->route('klasifikasi-surat.index')
                ->with('error', 'Klasifikasi tidak dapat dihapus karena masih digunakan dalam surat masuk atau surat keluar.');
        }

        $klasifikasiSurat->delete();

        return redirect()->route('klasifikasi-surat.index')
            ->with('success', 'Klasifikasi surat berhasil dihapus.');
    }
}
