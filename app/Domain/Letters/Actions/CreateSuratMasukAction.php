<?php

namespace App\Domain\Letters\Actions;

use App\Models\SuratMasuk;
use App\Traits\Loggable;
use Illuminate\Http\UploadedFile;

class CreateSuratMasukAction
{
    use Loggable;

    public function execute(array $data, ?UploadedFile $file, int $userId): SuratMasuk
    {
        $filePath = null;
        if ($file) {
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('surat-masuk', $fileName, 'public');
        }

        $suratMasuk = SuratMasuk::create([
            'nomor_surat' => $data['nomor_surat'],
            'tanggal_surat' => $data['tanggal_surat'],
            'pengirim' => $data['pengirim'],
            'perihal' => $data['perihal'],
            'ringkasan' => $data['ringkasan'] ?? null,
            'file_path' => $filePath,
            'status_ekstraksi_ai' => 'pending',
            'user_id' => $userId,
        ]);

        $this->logActivity('created', 'surat_masuk', $suratMasuk->id);

        return $suratMasuk;
    }
}
