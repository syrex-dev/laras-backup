<?php

namespace App\Domain\Organization\Actions;

use App\Models\TandaTangan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SaveTandaTanganAction
{
    public function execute(int $userId, UploadedFile $file): TandaTangan
    {
        $existing = TandaTangan::where('user_id', $userId)->first();

        if ($existing && $existing->path_tanda_tangan && Storage::disk('public')->exists($existing->path_tanda_tangan)) {
            Storage::disk('public')->delete($existing->path_tanda_tangan);
        }

        $path = $file->store('signatures', 'public');

        return TandaTangan::updateOrCreate(
            ['user_id' => $userId],
            ['path_tanda_tangan' => $path]
        );
    }
}
