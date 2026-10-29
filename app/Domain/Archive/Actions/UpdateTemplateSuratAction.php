<?php

namespace App\Domain\Archive\Actions;

use App\Models\TemplatSurat;
use App\Traits\Loggable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateTemplateSuratAction
{
    use Loggable;

    public function execute(TemplatSurat $templateSurat, array $data, ?UploadedFile $file): bool
    {
        $filePath = $templateSurat->path_file;

        if ($data['tipe_input'] === 'upload' && $file) {
            if ($templateSurat->path_file && Storage::disk('public')->exists($templateSurat->path_file)) {
                Storage::disk('public')->delete($templateSurat->path_file);
            }
            $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('templates', $fileName, 'public');
        } elseif ($data['tipe_input'] === 'textarea') {
            if ($templateSurat->path_file && Storage::disk('public')->exists($templateSurat->path_file)) {
                Storage::disk('public')->delete($templateSurat->path_file);
            }
            $fileName = time() . '_' . str_replace(' ', '_', $data['nama_templat']) . '.html';
            $filePath = 'templates/' . $fileName;
            Storage::disk('public')->put($filePath, $data['konten_html']);
        }

        $updated = $templateSurat->update([
            'nama_templat' => $data['nama_templat'],
            'path_file'    => $filePath,
        ]);

        if ($updated) {
            $this->logActivity('updated', 'templat_surat', $templateSurat->id);
        }

        return $updated;
    }
}
