<?php

namespace App\Domain\Letters\Actions;

use App\Models\SuratMasuk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateSuratMasukAction
{
    public function execute(SuratMasuk $suratMasuk, array $data, ?UploadedFile $file): bool
    {
        $updateData = [
            'nomor_surat' => $data['nomor_surat'],
            'tanggal_surat' => $data['tanggal_surat'],
            'pengirim' => $data['pengirim'],
            'perihal' => $data['perihal'],
            'ringkasan' => $data['ringkasan'] ?? null,
        ];

        if ($file) {
            if ($suratMasuk->file_path && Storage::disk('public')->exists($suratMasuk->file_path)) {
                Storage::disk('public')->delete($suratMasuk->file_path);
            }
            $fileName = time().'_'.$file->getClientOriginalName();
            $updateData['file_path'] = $file->storeAs('surat-masuk', $fileName, 'public');
        }

        return $suratMasuk->update($updateData);
    }
}
