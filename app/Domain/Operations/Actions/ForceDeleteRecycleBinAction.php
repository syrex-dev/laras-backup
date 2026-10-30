<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Disposisi\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ForceDeleteRecycleBinAction
{
    public function execute(string $type, int|string $id): Model
    {
        $modelClass = $this->resolveModelClass($type);
        $item = $modelClass::onlyTrashed()->findOrFail($id);

        if (isset($item->file_path) && $item->file_path && Storage::disk('public')->exists($item->file_path)) {
            Storage::disk('public')->delete($item->file_path);
        }

        $item->forceDelete();

        return $item;
    }

    protected function resolveModelClass(string $type): string
    {
        return match ($type) {
            'surat-masuk' => SuratMasuk::class,
            'surat-keluar' => SuratKeluar::class,
            'disposisi' => Disposisi::class,
            default => throw new \InvalidArgumentException("Tipe model tidak valid: {$type}"),
        };
    }
}
