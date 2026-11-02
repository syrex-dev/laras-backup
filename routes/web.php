<?php

use App\Http\Controllers\Archive\KlasifikasiSuratController;
use App\Http\Controllers\Archive\TemplateSuratController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Letters\DisposisiController;
use App\Http\Controllers\Letters\SuratKeluarController;
use App\Http\Controllers\Letters\SuratMasukController;
use App\Http\Controllers\Operations\BackupController;
use App\Http\Controllers\Operations\RecycleBinController;
use App\Http\Controllers\Organization\InstitutionProfileController;
use App\Http\Controllers\Organization\OrganizationProfileController;
use App\Http\Controllers\Organization\OrgUnitController;
use App\Http\Controllers\Organization\PositionController;
use App\Http\Controllers\Organization\TandaTanganController;
use App\Http\Controllers\Organization\UserController;
use App\Http\Controllers\Setup\SetupWizardController;
use App\Domain\Disposisi\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// -----------------------------------------------------------------------
// Redirect root to login
// -----------------------------------------------------------------------
Route::get('/', function () {
    return redirect()->route('login');
});

// -----------------------------------------------------------------------
// Wizard Setup Awal (Hanya untuk instalasi yang belum disetup)
// -----------------------------------------------------------------------
Route::get('/setup', [SetupWizardController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupWizardController::class, 'store'])->name('setup.store');

// -----------------------------------------------------------------------
// Auth Routes (Login / Logout)
// -----------------------------------------------------------------------
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// -----------------------------------------------------------------------
// Protected Routes (requires authentication)
// -----------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    // Routes for Pimpinan signature management
    Route::middleware(['permission:surat-keluar.setujui'])->group(function () {
        Route::get('/profil/tanda-tangan', [TandaTanganController::class, 'index'])->name('profile.signature.index');
        Route::post('/profil/tanda-tangan', [TandaTanganController::class, 'store'])->name('profile.signature.store');
    });

    // Dashboard
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        $stats = [
            'surat_masuk' => SuratMasuk::count(),
            'surat_keluar' => SuratKeluar::count(),
            'disposisi' => Disposisi::count(),
            'klasifikasi' => KlasifikasiSurat::count(),
        ];

        $recentSuratMasuk = SuratMasuk::with('klasifikasi')
            ->latest('tanggal_diterima')
            ->latest('id')
            ->take(5)
            ->get();

        $recentSuratKeluar = SuratKeluar::with(['klasifikasi', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        $pendingDisposisi = Disposisi::with(['suratMasuk'])
            ->where('penerima_id', $user?->id)
            ->whereIn('status', ['belum_dibaca', 'terbaca'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentSuratMasuk' => $recentSuratMasuk,
            'recentSuratKeluar' => $recentSuratKeluar,
            'pendingDisposisi' => $pendingDisposisi,
        ]);
    })->name('dashboard');

    // Klasifikasi Surat (CRUD) - Petugas Arsip & Admin Instansi
    Route::middleware(['permission:arsip.kelola'])->group(function () {
        Route::resource('klasifikasi-surat', KlasifikasiSuratController::class);
    });

    // Surat Masuk (CRUD & Aksi Terkait)
    Route::post('/surat-masuk/{suratMasuk}/cancel', [SuratMasukController::class, 'cancel'])->name('surat-masuk.cancel');
    Route::get('/surat-masuk/{suratMasuk}/attachments/{attachment}/download', [SuratMasukController::class, 'downloadAttachment'])->name('surat-masuk.attachment.download');
    Route::get('/surat-masuk/{suratMasuk}/attachments/{attachment}/preview', [SuratMasukController::class, 'previewAttachment'])->name('surat-masuk.attachment.preview');
    Route::get('/surat-masuk/{suratMasuk}/print-disposisi', [SuratMasukController::class, 'printLembarDisposisi'])->name('surat-masuk.print-disposisi');
    Route::get('/surat-masuk/{suratMasuk}/print-bukti', [SuratMasukController::class, 'printBuktiTerima'])->name('surat-masuk.print-bukti');
    Route::get('/lacak-surat/{token}', [SuratMasukController::class, 'lacak'])->name('surat-masuk.lacak');
    Route::resource('surat-masuk', SuratMasukController::class);

    // Surat Keluar
    // PENTING: Route eksplisit (create, templates, preview) HARUS didaftarkan SEBELUM resource route.
    // Sebab resource()->only(['index','show']) mendaftarkan pola /surat-keluar/{suratKeluar}
    // yang akan menelan string literal 'create' atau 'templates' sebagai wildcard jika urutan terbalik.
    Route::middleware(['permission:surat-keluar.template|surat-keluar.kelola|organisasi.kelola'])->group(function () {
        Route::post('/surat-keluar/templates/preview', [\App\Http\Controllers\SuratKeluar\TemplateSuratController::class, 'preview'])->name('surat-keluar.templates.preview');
        Route::resource('surat-keluar/templates', \App\Http\Controllers\SuratKeluar\TemplateSuratController::class)->names('surat-keluar.templates');
    });

    // --- Surat Keluar Fase 3A (Alur Persetujuan Berjenjang & Penomoran Resmi) ---
    Route::get('/surat-keluar/create', [SuratKeluarController::class, 'create'])->name('surat-keluar.create');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])->name('surat-keluar.store');
    Route::get('/surat-keluar/{suratKeluar}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');
    Route::put('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');
    Route::delete('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');

    Route::post('/surat-keluar/{suratKeluar}/submit', [SuratKeluarController::class, 'submit'])->name('surat-keluar.submit');
    Route::patch('/surat-keluar/{suratKeluar}/approve', [SuratKeluarController::class, 'approve'])->name('surat-keluar.approve');
    Route::patch('/surat-keluar/{suratKeluar}/revise', [SuratKeluarController::class, 'revise'])->name('surat-keluar.revise');
    Route::patch('/surat-keluar/{suratKeluar}/reject', [SuratKeluarController::class, 'reject'])->name('surat-keluar.reject');
    Route::post('/surat-keluar/{suratKeluar}/cancel', [SuratKeluarController::class, 'cancel'])->name('surat-keluar.cancel');
    Route::get('/surat-keluar/{suratKeluar}/pdf', [SuratKeluarController::class, 'pdf'])->name('surat-keluar.pdf');
    Route::post('/surat-keluar/{suratKeluar}/resolve-disposisi', [SuratKeluarController::class, 'resolveDisposisi'])->name('surat-keluar.resolve-disposisi');
    Route::get('/surat-keluar/{suratKeluar}/draft-pdf', [SuratKeluarController::class, 'previewDraft'])->name('surat-keluar.draft-pdf');
    Route::resource('surat-keluar', SuratKeluarController::class)->only(['index', 'show']);

    // Disposisi Modern (Fase 2: Kotak Masuk, Penerusan Berjenjang, Tindak Lanjut, Unduh Bukti)
    Route::get('/disposisi', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'index'])->name('disposisi.index');
    Route::post('/disposisi', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'store'])->name('disposisi.store');
    Route::get('/disposisi/recipient/{recipient}', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'show'])->name('disposisi.recipient.show');
    Route::post('/disposisi/recipient/{recipient}/forward', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'forward'])->name('disposisi.recipient.forward');
    Route::post('/disposisi/recipient/{recipient}/followup', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'followup'])->name('disposisi.recipient.followup');
    Route::post('/disposisi/recipient/{recipient}/takeover', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'takeover'])->name('disposisi.recipient.takeover');
    Route::get('/disposisi/attachments/{attachment}/download', [\App\Http\Controllers\Disposisi\DisposisiController::class, 'downloadAttachment'])->name('disposisi.attachment.download');

    // Legacy Disposisi Routes (Kompatibilitas Prototipe & Test Lama)
    Route::get('/disposisi/create', [DisposisiController::class, 'create'])->name('disposisi.create');
    Route::get('/disposisi/{disposisi}', [DisposisiController::class, 'show'])->name('disposisi.show');
    Route::patch('/disposisi/{disposisi}/status', [DisposisiController::class, 'updateStatus'])->name('disposisi.update-status');

    // Pengaturan & Master Organisasi (Admin Instansi via permission)
    Route::middleware(['permission:organisasi.kelola'])->group(function () {
        Route::get('/pengaturan/profil', [InstitutionProfileController::class, 'edit'])->name('pengaturan.profil.edit');
        Route::put('/pengaturan/profil', [InstitutionProfileController::class, 'update'])->name('pengaturan.profil.update');
        Route::resource('users', UserController::class);
        Route::resource('unit-kerja', OrgUnitController::class);
        Route::resource('jabatan', PositionController::class);
    });

    // Admin Suite (Eksklusif: Admin Instansi via permission)
    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['permission:organisasi.kelola|sistem.pemeliharaan']], function () {
        Route::get('/logs', function () {
            $logs = \App\Models\LogAktivitas::with('user')->latest()->paginate(20);

            return Inertia::render('Operasi/Logs', [
                'logs' => $logs,
            ]);
        })->name('logs.index');

        Route::get('/profile', [OrganizationProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile', [OrganizationProfileController::class, 'update'])->name('profile.update');

        // Backup System
        Route::post('/backup/run', [BackupController::class, 'run'])->name('backup.run');

        // Recycle Bin
        Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
        Route::post('/recycle-bin/{type}/{id}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
        Route::delete('/recycle-bin/{type}/{id}/force-delete', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');
    });

    // Template Surat Management
    Route::middleware(['permission:surat-keluar.template|surat-keluar.kelola'])->group(function () {
        Route::resource('template-surat', TemplateSuratController::class);
    });

    // Global Search
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('global.search');
});

// Public Verification Route
Route::get('/verify/{token}', function ($token) {
    $surat = SuratKeluar::where('qr_token', $token)->firstOrFail();

    return view('public.verify', compact('surat'));
})->name('public.verify');
