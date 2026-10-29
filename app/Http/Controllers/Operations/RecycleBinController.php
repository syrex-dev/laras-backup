<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Operations\Actions\ForceDeleteRecycleBinAction;
use App\Domain\Operations\Actions\RestoreRecycleBinAction;
use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Support\Facades\Log;

class RecycleBinController extends Controller
{
    public function index()
    {
        $trashedSuratMasuk = SuratMasuk::onlyTrashed()->get()->map(function ($item) {
            $item->type = 'surat-masuk';
            $item->display_type = 'Surat Masuk';

            return $item;
        });

        $trashedSuratKeluar = SuratKeluar::onlyTrashed()->get()->map(function ($item) {
            $item->type = 'surat-keluar';
            $item->display_type = 'Surat Keluar';

            return $item;
        });

        $trashedItems = $trashedSuratMasuk->concat($trashedSuratKeluar);

        return view('operations.recycle-bin.index', compact('trashedItems'));
    }

    public function restore($type, $id, RestoreRecycleBinAction $restoreAction)
    {
        try {
            $restoreAction->execute($type, $id);

            return redirect()->route('admin.recycle-bin.index')->with('success', "{$type} berhasil dikembalikan.");
        } catch (\Exception $e) {
            Log::error("Restore Error ({$type}): ".$e->getMessage());

            return redirect()->back()->with('error', "Gagal mengembalikan {$type}: ".$e->getMessage());
        }
    }

    public function forceDelete($type, $id, ForceDeleteRecycleBinAction $forceDeleteAction)
    {
        try {
            $forceDeleteAction->execute($type, $id);

            return redirect()->route('admin.recycle-bin.index')->with('success', "{$type} berhasil dihapus permanen.");
        } catch (\Exception $e) {
            Log::error("Force Delete Error ({$type}): ".$e->getMessage());

            return redirect()->back()->with('error', "Gagal menghapus permanen {$type}: ".$e->getMessage());
        }
    }
}
