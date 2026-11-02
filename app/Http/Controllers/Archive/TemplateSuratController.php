<?php

namespace App\Http\Controllers\Archive;

use App\Domain\Archive\Actions\CreateTemplateSuratAction;
use App\Domain\Archive\Actions\UpdateTemplateSuratAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Archive\StoreTemplateSuratRequest;
use App\Http\Requests\Archive\UpdateTemplateSuratRequest;
use App\Models\TemplatSurat;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateSuratController extends Controller
{
    use Loggable;

    public function index(Request $request)
    {
        return app(\App\Http\Controllers\SuratKeluar\TemplateSuratController::class)->index($request);
    }

    public function create(Request $request)
    {
        return $this->index($request);
    }

    public function store(StoreTemplateSuratRequest $request, CreateTemplateSuratAction $createAction)
    {
        $createAction->execute($request->validated(), $request->file('file_template'));

        return redirect()->route('template-surat.index')
            ->with('success', 'Template surat berhasil ditambahkan.');
    }

    public function show(TemplatSurat $templateSurat, Request $request)
    {
        return $this->index($request);
    }

    public function edit(TemplatSurat $templateSurat, Request $request)
    {
        return $this->index($request);
    }

    public function update(UpdateTemplateSuratRequest $request, TemplatSurat $templateSurat, UpdateTemplateSuratAction $updateAction)
    {
        $updateAction->execute($templateSurat, $request->validated(), $request->file('file_template'));

        return redirect()->route('template-surat.index')
            ->with('success', 'Template surat berhasil diperbarui.');
    }

    public function destroy(TemplatSurat $templateSurat)
    {
        if ($templateSurat->path_file && Storage::disk('public')->exists($templateSurat->path_file)) {
            Storage::disk('public')->delete($templateSurat->path_file);
        }

        $id = $templateSurat->id;
        $templateSurat->delete();
        $this->logActivity('deleted', 'templat_surat', $id);

        return redirect()->route('template-surat.index')
            ->with('success', 'Template surat berhasil dihapus.');
    }
}
