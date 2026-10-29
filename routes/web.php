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
use App\Http\Controllers\Organization\OrganizationProfileController;
use App\Http\Controllers\Organization\TandaTanganController;
use App\Http\Controllers\Organization\UserController;
use App\Models\Disposisi;
use App\Models\KlasifikasiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Redirect root to login
// -----------------------------------------------------------------------
Route::get('/', function () {
    return redirect()->route('login');
});

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
    Route::middleware(['role:Pimpinan'])->group(function () {
        Route::get('/profil/tanda-tangan', [TandaTanganController::class, 'index'])->name('profile.signature.index');
        Route::post('/profil/tanda-tangan', [TandaTanganController::class, 'store'])->name('profile.signature.store');
    });

    // Dashboard
    Route::get('/dashboard', function () {
        $stats = [
            'surat_masuk' => SuratMasuk::count(),
            'surat_keluar' => SuratKeluar::count(),
            'disposisi' => Disposisi::count(),
            'klasifikasi' => KlasifikasiSurat::count(),
        ];

        return view('dashboard', compact('stats'));
    })->name('dashboard');

    // Klasifikasi Surat (CRUD) - hanya Admin dan Sekretaris
    Route::middleware(['role:Admin|Sekretaris'])->group(function () {
        Route::resource('klasifikasi-surat', KlasifikasiSuratController::class);
    });

    // Surat Masuk (CRUD)
    Route::resource('surat-masuk', SuratMasukController::class);

    // Surat Keluar
    // PENTING: Route eksplisit (create, preview) HARUS didaftarkan SEBELUM resource route.
    // Sebab resource()->only(['index','show']) mendaftarkan pola /surat-keluar/{suratKeluar}
    // yang akan menelan string literal 'create' sebagai wildcard jika urutan terbalik.

    // --- Sekretaris: buat & kelola surat keluar ---
    Route::middleware(['role:Sekretaris'])->group(function () {
        Route::post('/surat-keluar/preview', [SuratKeluarController::class, 'preview'])->name('surat-keluar.preview');
        Route::get('/surat-keluar/create', [SuratKeluarController::class, 'create'])->name('surat-keluar.create');
        Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])->name('surat-keluar.store');
        Route::get('/surat-keluar/{suratKeluar}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');
        Route::put('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');
        Route::delete('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');
    });

    // --- Pimpinan: approve & reject ---
    Route::middleware(['role:Pimpinan'])->group(function () {
        Route::patch('/surat-keluar/{suratKeluar}/approve', [SuratKeluarController::class, 'approve'])->name('surat-keluar.approve');
        Route::patch('/surat-keluar/{suratKeluar}/reject', [SuratKeluarController::class, 'reject'])->name('surat-keluar.reject');
    });

    // --- Resource (index + show) & draft-pdf: didaftarkan TERAKHIR ---
    Route::get('/surat-keluar/{suratKeluar}/draft-pdf', [SuratKeluarController::class, 'previewDraft'])->name('surat-keluar.draft-pdf');
    Route::resource('surat-keluar', SuratKeluarController::class)->only(['index', 'show']);

    // Disposisi (buat hanya Pimpinan, lihat untuk semua login)
    Route::middleware(['role:Pimpinan'])->group(function () {
        Route::get('/disposisi/create', [DisposisiController::class, 'create'])->name('disposisi.create');
        Route::post('/disposisi', [DisposisiController::class, 'store'])->name('disposisi.store');
    });
    Route::resource('disposisi', DisposisiController::class)->only(['index', 'show']);
    Route::patch('/disposisi/{disposisi}/status', [DisposisiController::class, 'updateStatus'])->name('disposisi.update-status');

    // Admin Suite (Eksklusif: Admin)
    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['role:Admin']], function () {
        Route::get('/logs', function () {
            return view('operations.logs.index');
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

    // User Management
    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('users', UserController::class);
    });

    // Template Surat Management
    Route::middleware(['role:Admin|Sekretaris'])->group(function () {
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
