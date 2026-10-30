<?php

namespace App\Http\Controllers\SuratKeluar;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TemplateSuratController extends Controller
{
    public function __construct(
        protected TemplateVariableService $variableService,
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Menampilkan daftar master template surat
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TemplateSurat::class);

        $jenis = $request->input('jenis');
        $search = $request->input('search');

        $query = TemplateSurat::with('createdBy')->latest('id');

        if ($jenis && $jenis !== 'semua') {
            $query->where('jenis', $jenis);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(10)->withQueryString();

        return Inertia::render('SuratKeluar/Template/Index', [
            'templates' => $templates,
            'filters' => [
                'jenis' => $jenis ?? 'semua',
                'search' => $search ?? '',
            ],
            'supportedVariables' => TemplateVariableService::SUPPORTED_VARIABLES,
            'institution' => InstitutionProfile::first(),
        ]);
    }

    /**
     * Menyimpan template surat baru
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', TemplateSurat::class);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'jenis' => 'required|string|in:nota_dinas,surat_tugas,edaran,sk,undangan,biasa',
            'kode' => 'nullable|string|max:50|unique:template_surat,kode',
            'format_nomor' => 'nullable|string|max:255',
            'konten_default' => 'required|string',
            'use_institution_header' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Validasi sintaks variabel
        $unsupported = $this->variableService->validateVariables($validated['konten_default']);
        if (! empty($unsupported)) {
            throw ValidationException::withMessages([
                'konten_default' => 'Terdapat variabel yang tidak didukung: ' . implode(', ', $unsupported),
            ]);
        }

        $sanitizer = app(\App\Services\HtmlSanitizer::class);
        $validated['konten_default'] = $sanitizer->sanitize($validated['konten_default']);

        $usedVars = $this->variableService->extractVariables($validated['konten_default']);

        $template = TemplateSurat::create([
            ...$validated,
            'variabel' => array_values($usedVars),
            'created_by' => Auth::id(),
        ]);

        $this->auditLogger->log(
            'template_surat.create',
            "Membuat template surat: {$template->nama} ({$template->jenis})",
            $template
        );

        return redirect()->back()->with('success', "Template '{$template->nama}' berhasil dibuat.");
    }

    /**
     * Memperbarui template surat yang ada
     */
    public function update(Request $request, TemplateSurat $template): RedirectResponse
    {
        Gate::authorize('update', $template);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'jenis' => 'required|string|in:nota_dinas,surat_tugas,edaran,sk,undangan,biasa',
            'kode' => "nullable|string|max:50|unique:template_surat,kode,{$template->id}",
            'format_nomor' => 'nullable|string|max:255',
            'konten_default' => 'required|string',
            'use_institution_header' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $unsupported = $this->variableService->validateVariables($validated['konten_default']);
        if (! empty($unsupported)) {
            throw ValidationException::withMessages([
                'konten_default' => 'Terdapat variabel yang tidak didukung: ' . implode(', ', $unsupported),
            ]);
        }

        $sanitizer = app(\App\Services\HtmlSanitizer::class);
        $validated['konten_default'] = $sanitizer->sanitize($validated['konten_default']);

        $usedVars = $this->variableService->extractVariables($validated['konten_default']);

        $template->update([
            ...$validated,
            'variabel' => array_values($usedVars),
        ]);

        $this->auditLogger->log(
            'template_surat.update',
            "Memperbarui template surat: {$template->nama}",
            $template
        );

        return redirect()->back()->with('success', "Template '{$template->nama}' berhasil diperbarui.");
    }

    /**
     * Menghapus template surat
     */
    public function destroy(TemplateSurat $template): RedirectResponse
    {
        Gate::authorize('delete', $template);

        if ($template->suratKeluar()->exists()) {
            throw ValidationException::withMessages([
                'template' => 'Template tidak dapat dihapus karena telah digunakan pada surat keluar.',
            ]);
        }

        $templateName = $template->nama;
        $template->delete();

        $this->auditLogger->log(
            'template_surat.delete',
            "Menghapus template surat: {$templateName}",
            $template
        );

        return redirect()->back()->with('success', "Template '{$templateName}' berhasil dihapus.");
    }

    /**
     * Merender pratinjau instan konten template dengan variabel terisi
     */
    public function preview(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', TemplateSurat::class);

        $content = $request->input('konten_default');
        if (empty($content) && $request->filled('template_id')) {
            $template = TemplateSurat::findOrFail($request->input('template_id'));
            $content = $template->konten_default;
        }

        $sampleContext = [
            'nomor_surat' => '0123/PL.7/KP.01.02/X/2026',
            'tanggal_surat' => now()->toDateString(),
            'perihal' => 'Undangan Rapat Koordinasi Naskah Dinas',
            'tujuan' => 'Seluruh Ketua Jurusan dan Kepala Bagian',
            'nama_penandatangan' => 'Dr. Ir. Budi Santoso, M.T.',
            'jabatan_penandatangan' => 'Direktur',
            'unit_pembuat' => 'Bagian Administrasi Akademik & Umum',
        ];

        $rendered = $this->variableService->render($content ?: '', $sampleContext);
        $rendered = app(\App\Services\HtmlSanitizer::class)->sanitize($rendered);

        return response()->json([
            'rendered' => $rendered,
            'variables' => $this->variableService->extractVariables($content ?: ''),
            'sample_context' => $sampleContext,
        ]);
    }
}
