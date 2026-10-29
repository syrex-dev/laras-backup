<?php

namespace App\Domain\Archive\Actions;

use App\Models\TemplatSurat;
use App\Traits\Loggable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateTemplateSuratAction
{
    use Loggable;

    public function execute(array $data, ?UploadedFile $file): TemplatSurat
    {
        $filePath = null;

        if ($data['tipe_input'] === 'upload' && $file) {
            $fileName = time().'_'.str_replace(' ', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('templates', $fileName, 'public');
        } elseif ($data['tipe_input'] === 'textarea') {
            $fileName = time().'_'.str_replace(' ', '_', $data['nama_templat']).'.html';
            $filePath = 'templates/'.$fileName;
            Storage::disk('public')->put($filePath, $data['konten_html']);
        }

        $template = TemplatSurat::create([
            'nama_templat' => $data['nama_templat'],
            'path_file' => $filePath,
        ]);

        $this->logActivity('created', 'templat_surat', $template->id);

        return $template;
    }
}
