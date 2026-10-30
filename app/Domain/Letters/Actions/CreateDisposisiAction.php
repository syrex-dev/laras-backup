<?php

namespace App\Domain\Letters\Actions;

use App\Domain\Disposisi\Actions\CreateDisposisiAction as DomainCreateDisposisiAction;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\Disposisi;
use App\Models\User;

class CreateDisposisiAction
{
    /**
     * Backward-compatible wrapper delegating to modern Domain Action
     */
    public function execute(array $data, int $senderId): Disposisi
    {
        $sender = User::findOrFail($senderId);
        $suratMasuk = SuratMasuk::findOrFail($data['surat_masuk_id']);

        $modernData = [
            'instruksi' => $data['instruksi'] ?? 'Segera tindak lanjuti',
            'catatan' => $data['catatan'] ?? null,
            'prioritas' => $data['prioritas'] ?? Disposisi::PRIORITAS_BIASA,
            'tenggat_waktu' => $data['tenggat_waktu'] ?? null,
            'recipients' => isset($data['recipients']) && is_array($data['recipients'])
                ? $data['recipients']
                : [
                    ['user_id' => $data['penerima_id'] ?? null],
                ],
        ];

        return (new DomainCreateDisposisiAction())->execute($sender, $suratMasuk, $modernData);
    }
}
