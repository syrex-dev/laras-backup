<?php

namespace App\Http\Controllers\Archive;

use App\Domain\Archive\Actions\CreateTemplateSuratAction;
use App\Domain\Archive\Actions\UpdateTemplateSuratAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Archive\StoreTemplateSuratRequest;
use App\Http\Requests\Archive\UpdateTemplateSuratRequest;
use App\Models\TemplatSurat;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Storage;

class TemplateSuratController extends Controller
{
    use Loggable;

    public function index()
    {
        $templates = TemplatSurat::query()->latest()->paginate(10);
        return view('archive.template-surat.index', compact('templates'));
    }

    public function create()
    {
        return view('archive.template-surat.create');
    }

    public function store(StoreTemplateSuratRequest $request, CreateTemplateSuratAction $createAction)
    {
        $createAction->execute($request->validated(), $request->file('file_template'));

        return redirect()->route('template-surat.index')
            ->with('success', 'Template surat berhasil ditambahkan.');
    }

    public function show(TemplatSurat $templateSurat)
    {
        $htmlContent = '';
        if ($templateSurat->path_file && Storage::disk('public')->exists($templateSurat->path_file)) {
            $htmlContent = Storage::disk('public')->get($templateSurat->path_file);
        }
        return view('archive.template-surat.show', compact('templateSurat', 'htmlContent'));
    }

    public function edit(TemplatSurat $templateSurat)
    {
        $htmlContent = '';
        if ($templateSurat->path_file && Storage::disk('public')->exists($templateSurat->path_file)) {
            $htmlContent = Storage::disk('public')->get($templateSurat->path_file);
        }
        return view('archive.template-surat.edit', compact('templateSurat', 'htmlContent'));
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
