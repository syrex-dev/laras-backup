<?php

namespace App\Domain\Operations\Actions;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Database\Eloquent\Model;

class RestoreRecycleBinAction
{
    public function execute(string $type, int|string $id): Model
    {
        $modelClass = $this->resolveModelClass($type);
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

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
