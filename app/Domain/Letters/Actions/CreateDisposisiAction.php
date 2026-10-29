<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Support\DisposisiStatus;
use App\Models\Disposisi;
use App\Traits\Loggable;

class CreateDisposisiAction
{
    use Loggable;

    public function execute(array $data, int $senderId): Disposisi
    {
        $disposisi = Disposisi::create([
            'surat_masuk_id' => $data['surat_masuk_id'],
            'pengirim_id' => $senderId,
            'penerima_id' => $data['penerima_id'],
            'instruksi' => $data['instruksi'],
            'prioritas' => $data['prioritas'],
            'status' => DisposisiStatus::BELUM_DIBACA,
        ]);

        $this->logActivity('created', 'disposisi', $disposisi->id);

        return $disposisi;
    }
}
