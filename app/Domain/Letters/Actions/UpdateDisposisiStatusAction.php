<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Letters\Support\DisposisiStatus;
use App\Models\Disposisi;

class UpdateDisposisiStatusAction
{
    public function markAsRead(Disposisi $disposisi): bool
    {
        if ($disposisi->status === DisposisiStatus::BELUM_DIBACA) {
            return $disposisi->update([
                'status' => DisposisiStatus::SUDAH_DIBACA,
                'dibaca_pada' => now(),
            ]);
        }

        return false;
    }

    public function updateStatus(Disposisi $disposisi, string $status): bool
    {
        $data = ['status' => $status];

        if ($status === DisposisiStatus::SUDAH_DIBACA && ! $disposisi->dibaca_pada) {
            $data['dibaca_pada'] = now();
        }

        return $disposisi->update($data);
    }
}
