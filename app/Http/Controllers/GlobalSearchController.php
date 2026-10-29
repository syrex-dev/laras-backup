<?php

namespace App\Http\Controllers;

use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (! $query) {
            return response()->json([]);
        }

        $suratMasuk = SuratMasuk::where('nomor_surat', 'like', "%$query%")
            ->orWhere('perihal', 'like', "%$query%")
            ->orWhere('pengirim', 'like', "%$query%")
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'type' => 'Surat Masuk',
                'title' => $item->perihal,
                'subtitle' => $item->nomor_surat.' • '.$item->pengirim,
                'url' => route('surat-masuk.show', $item),
                'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ]);

        $suratKeluar = SuratKeluar::where('nomor_surat', 'like', "%$query%")
            ->orWhere('perihal', 'like', "%$query%")
            ->orWhere('tujuan', 'like', "%$query%")
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'type' => 'Surat Keluar',
                'title' => $item->perihal,
                'subtitle' => ($item->nomor_surat ?? 'Draft').' • Ke: '.$item->tujuan,
                'url' => route('surat-keluar.show', $item),
                'icon' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            ]);

        $klasifikasi = KlasifikasiSurat::where('nama', 'like', "%$query%")
            ->orWhere('kode_klasifikasi', 'like', "%$query%")
            ->limit(3)
            ->get()
            ->map(fn ($item) => [
                'type' => 'Klasifikasi',
                'title' => $item->nama,
                'subtitle' => 'Kode: '.$item->kode_klasifikasi,
                'url' => route('klasifikasi-surat.edit', $item),
                'icon' => 'M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01',
            ]);

        return response()->json($suratMasuk->concat($suratKeluar)->concat($klasifikasi));
    }
}
