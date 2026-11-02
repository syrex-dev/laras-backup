# Walkthrough Sistem E-Office (Fase 3A)

## 1. Ringkasan
- **Tanggal Pemutakhiran**: 2 November 2026
- **Branch**: `feature/fase-3a-surat-keluar`
- **Rentang Commit Terkini**: `2550096` s.d. `fbe5157`
- **Fokus Pekerjaan**: Audit menyeluruh sistem persuratan Fase 3A (keamanan konten XSS & Dompdf, persistensi nomor resmi token atomik, row locking konkurensi, standardisasi RBAC PRD, audit migrasi reversibilitas basis data, eliminasi error Ziggy undefined route, perbaikan komponen Select & safe fallback array map di React, dan verifikasi test suite ganda SQLite & PostgreSQL port 5433).
- **Hasil Utama**: Rangkaian pengujian unit dan fitur berhasil mencapai 100% lulus:
  - **SQLite In-Memory**: 157 lulus, 3 dilewati (1029 assertions).
  - **PostgreSQL port 5433**: 160 lulus, 0 dilewati, 0 gagal (1033 assertions, seluruh uji konkurensi dan trigger append-only berjalan sempurna).

---

## 2. Tabel Status per Bagian

### LANGKAH 0 — Audit Awal (Tanpa Mengubah Kode)

| Butir Audit | Status | Bukti Perintah Nyata |
| :--- | :---: | :--- |
| `git status --short` & `git log -n 30` | SELESAI | Working tree bersih (clean). Riwayat 30 commit tersusun rapi dari `2550096` hingga `093da12`. |
| `dangerouslySetInnerHTML` & `purifier` | SELESAI | Render `dangerouslySetInnerHTML` di frontend telah diproteksi `DOMPurify.sanitize(...)` (`Create.tsx`, `Edit.tsx`, `Show.tsx`, `Template/Index.tsx`). Backend memakai `ezyang/htmlpurifier` di `HtmlSanitizer.php`. |
| `isRemoteEnabled` & `enable_remote` | SELESAI | `GenerateSuratKeluarPdfAction.php` (`isRemoteEnabled => false`), `config/dompdf.php` (`enable_remote => false`). |
| `lockForUpdate` Surat Keluar | SELESAI | Diterapkan pada `ApproveSuratKeluarAction.php`, `RejectSuratKeluarAction.php`, `RequestRevisionSuratKeluarAction.php`, `SubmitSuratKeluarAction.php`, dan `NomorSuratKeluarService.php`. |
| `hasRole\|hasAnyRole` & `legacyAdminRole` | SELESAI | Bersih total (0 temuan di `app/` dan `database/`). Tidak ada lagi pemanggilan `hasRole` hardcoded. |
| Seeder guard & `himase` & `SETUP_TOKEN` | SELESAI | Seeder produksi bebas guard environment; `SETUP_TOKEN` terpasang di `SetupWizardController.php` dan `.env.example`. String `himase` hanya tersisa di view Blade prototipe lama. |
| Model ganda `profil_organisasi` & `templat_surat` | SEBAGIAN | Masih terdapat referensi di model/controller/view prototipe lama (`ProfilOrganisasi.php`, `TemplatSurat.php`, `OrganizationProfileController.php`, `SuratDemoSeeder.php`). |
| `return view()` & `Inertia::location` | SEBAGIAN | Rute `/dashboard` masih mengembalikan Blade `view('dashboard')` dengan `Inertia::location` workaround. Rute prototipe lama masih mengembalikan Blade. |
| `php artisan test` (SQLite) | SELESAI | `Tests: 3 skipped, 153 passed (898 assertions)` dalam 38.69s. |
| `php artisan test -c phpunit.pgsql.xml` (PGSQL 5433) | SELESAI | `Tests: 156 passed (902 assertions)` dalam 95.23s. |

---

### BAGIAN 1 — Keamanan Konten

| Butir | Status | Commit | File Utama yang Berubah / Terlibat |
| :--- | :---: | :---: | :--- |
| **1.1 XSS Tersimpan** | SELESAI | `46af3de` | `app/Services/HtmlSanitizer.php`, `app/Domain/SuratKeluar/Actions/SaveDraftSuratKeluarAction.php`, `resources/js/Pages/SuratKeluar/Show.tsx`, `Create.tsx`, `Edit.tsx` |
| **1.2 Proteksi Dompdf** | SELESAI | `ae092df` | `app/Domain/SuratKeluar/Actions/GenerateSuratKeluarPdfAction.php`, `config/dompdf.php`, `tests/Feature/SuratKeluarPdfTest.php` |
| **1.3 Persetujuan Ganda & Concurrency** | SELESAI | `fabb5fa`, `5bc8d77` | `app/Domain/SuratKeluar/Actions/ApproveSuratKeluarAction.php`, `SubmitSuratKeluarAction.php`, `tests/Feature/SuratKeluarParallelApprovalTest.php` |
| **1.4 Header Keamanan** | SELESAI | `65c0c1b` | `app/Http/Middleware/SecurityHeadersMiddleware.php`, `bootstrap/app.php`, `tests/Feature/SecurityHeadersTest.php` |

---

### BAGIAN 2 — RBAC, Seeder, & Instalasi Produksi

| Butir | Status | Commit | File Utama yang Berubah / Terlibat |
| :--- | :---: | :---: | :--- |
| **2.1 Single Source of Truth Permission** | SELESAI | `b3ab24e` | `config/permissions.php` |
| **2.2 RolePermissionSeeder & Sync Artisan** | SELESAI | `fecc7d1` | `database/seeders/RolePermissionSeeder.php`, `app/Console/Commands/SyncPermissionsCommand.php` |
| **2.3 Setup Wizard Hardening** | SELESAI | `3499077` | `app/Http/Controllers/Setup/SetupWizardController.php`, `.env.example`, `tests/Feature/SetupWizardAndTerminologyTest.php` |
| **2.4 Pemisahan Seeder Produksi vs Demo** | SELESAI | `698d21f` | `database/seeders/ProductionSeeder.php`, `DemoSeeder.php`, `DatabaseSeeder.php` |
| **2.5 Profil Instansi Ganda** | SELESAI | `4600dbd` | `app/Models/ProfilOrganisasi.php`, `app/Models/InstitutionProfile.php`, `database/seeders/DemoSeeder.php` |
| **2.6 Template Surat Ganda** | SELESAI | `4c40532` | `app/Models/TemplatSurat.php`, `app/Models/TemplateSurat.php`, `database/seeders/SuratDemoSeeder.php` |
| **2.7 Eliminasi hasRole/hasAnyRole** | SELESAI | `e63fe27` | `app/Domain/SuratKeluar/Policies/SuratKeluarPolicy.php`, `app/Http/Controllers/SuratKeluar/TemplateSuratController.php` |
| **2.8 DisposisiPolicy Kedalaman 20 & Privasi** | SELESAI | `5f2d519` | `app/Domain/Disposisi/Policies/DisposisiPolicy.php`, `tests/Feature/DisposisiPolicyAndTimelinePrivacyTest.php` |
| **2.9 Kanal Pembuatan Pengguna Kanonikal** | SELESAI | `88473cb` | `app/Domain/Pengguna/Actions/UserManagementAction.php`, `app/Http/Controllers/Organization/UserController.php` |
| **2.10 Suite Pengujian RBAC Produksi** | SELESAI | `cacf1bc` | `tests/Feature/ProductionRbacAndRouteCoverageTest.php` |
| **2.11 Matriks Hak Akses & Referensi Kode** | SELESAI | `c5a4c04` | `docs/PERMISSIONS_MATRIX.md` |
| **2.12 Panduan Instalasi Produksi** | SELESAI | `5361bc6` | `README.md` |

---

### BAGIAN 3 — UI: Satu Layout, Tanpa Layar Putih

| Butir | Status | Commit | Catatan Audit & Implementasi |
| :--- | :---: | :---: | :--- |
| **3.1 Dashboard Inertia/React** | SELESAI | `ef2ec72` | Mengganti rute `/dashboard` ke Inertia/React menggunakan `AppLayout.tsx`, menghapus workaround `Inertia::location`. |
| **3.2 Inventarisasi & Pembersihan Rute Blade** | SELESAI | `b22f2c0` | Menghapus `resources/views/layouts/app.blade.php`, `resources/js/app.js`, `dashboard.blade.php`, `welcome.blade.php`. Mengonversi semua rute pasca-login ke Inertia (`Operasi/Logs`, `Operasi/RecycleBin`, `Organisasi/TandaTangan`, `Kearsipan/KlasifikasiSurat`, `SuratMasuk/Edit`). |
| **3.3 Satu Sumber Menu (Filtered by Permission)** | SELESAI | `0587881` | Navigasi tunggal di `AppLayout.tsx` disaring ketat berdasarkan permission Spatie pengguna aktif. |
| **3.4 Dashboard Statistik Nyata & Grafik Volume** | SELESAI | `3588ba1` | Statistik hitungan langsung dari basis data persuratan dan grafik Chart.js volume 6 bulan terakhir dengan status kosong informatif. |
| **3.5 Tag Lang, Meta notranslate, Judul Instansi** | SELESAI | `1077167` | `app.blade.php` dilengkapi `<meta name="google" content="notranslate">`, `<html lang="id">`, dynamic title dari `InstitutionProfile::current()?->name`. |
| **3.6 ErrorBoundary & Halaman Error Indonesia** | SELESAI | `a1c01b8` | `ErrorBoundary.tsx` global di React root, halaman error Inertia berbahasa Indonesia (`Error.tsx` untuk 403, 404, 419, 500, 503), dan redirect 419 dengan pesan ramah. |
| **3.7 Build Tanpa Jeda Aset & eoffice:doctor** | SELESAI | `7ab4bf0` | Zero-downtime asset swap (`emptyOutDir: false`, `manifest.json`), script `dev:clean`, dan artisan command `php artisan eoffice:doctor`. |
| **3.8 Pengujian UI Inertia & Manifest Asset** | SELESAI | `efd39a6`, `7e3af3f` | Suite pengujian `InertiaUiAndAssetIntegrityTest` menguji rute navigasi, manifest hash, file fisik, dan harmonisasi test view ke Inertia. |
| **3.9 Perbaikan Undefined Route & Relasi Model** | SELESAI | `ab596ee`, `e61adc9` | Memperbaiki relasi `creator` pada model `SuratKeluar` (`routes/web.php`) dan mengganti seluruh pemanggilan helper `route(...)` di React Surat Keluar (`Index`, `Create`, `Edit`, `Show`) ke path URL RESTful standar, plus menambahkan defensive fallback di `bootstrap.js`. |
| **3.10 Perbaikan Komponen Select & Safe Mapping** | SELESAI | `fbe5157` | Memperbaiki komponen `Select.tsx` agar `options` opsional dan mendukung `children` `<option>`, serta menambahkan safe fallbacks pada seluruh pemanggilan `.map(...)` di `Index`, `Create`, `Edit`, dan `Show` Surat Keluar untuk mengeliminasi error `Cannot read properties of undefined (reading 'map')`. |



---

### BAGIAN 4 — Penutupan Fase 3A

| Butir | Status | Commit | File Utama yang Berubah / Terlibat |
| :--- | :---: | :---: | :--- |
| **4.1 Benchmark 100k Data & EXPLAIN BUFFERS** | SELESAI | `8c93175` | `app/Console/Commands/BenchmarkFase3aPenutupanCommand.php` |
| **4.2 Siklus Migrasi & Reversibilitas Skema** | SELESAI | `470d1c6` | `docs/DATABASE.md` (Bagian 7.2), `database/migrations/*`, `database/seeders/SuratDemoSeeder.php` |
| **4.3 Locale Default Indonesia & Validasi** | SELESAI | `5361bc6` | `.env.example`, `config/app.php`, `lang/id/*`, `tests/Feature/IndonesianValidationMessagesTest.php` |
| **4.4 Harmonisasi Dualitas Surat Keluar** | SELESAI | `8b9d843`, `2f0074d`, `093da12` | `app/Domain/Letters/Actions/*`, `tests/Feature/PrototypeAndCanonicalCoexistenceTest.php`, `SaveDraftSuratKeluarAction.php` |
| **4.5 Keputusan Tahun Nomor Surat** | SELESAI | `33e06cd` | `docs/OPEN_QUESTIONS.md` (OQ-14), `tests/Feature/SuratKeluarNumberingTest.php` |
| **4.6 Log Commit & Eksekusi Full Suite** | SELESAI | `7e3af3f`, `470d1c6` | 160 test passed (1033 assertions) di PostgreSQL 5433, 157 passed di SQLite. |

---

## 3. Cara Menjalankan dan Menguji Manual

### Persiapan Lingkungan:
1. Buka terminal PowerShell di folder proyek:
   ```powershell
   cd d:\Coding\laras
   ```
2. Pastikan basis data PostgreSQL aktif pada port 5433 (WSL / native) dengan database `laras` dan `laras_test`:
   ```powershell
   Test-NetConnection -ComputerName 127.0.0.1 -Port 5433
   ```
3. Pastikan tidak ada file `public/hot` tertinggal dan build aset tersedia:
   ```powershell
   if (Test-Path public/hot) { Remove-Item public/hot }
   npm run build
   ```

### Akun Demo per Peran (Password: `password`):
- **Admin Instansi**: `admin@contoh.ac.id` (Akses penuh pengaturan organisasi, unit kerja, jabatan, pengguna, lisensi)
- **Pimpinan (Direktur)**: `direktur@contoh.ac.id` (Akses disposisi tingkat pimpinan, persetujuan/penolakan surat keluar)
- **Kepala Unit (Kajur JTK)**: `kajur.jtk@contoh.ac.id` (Akses disposisi berjenjang, penerusan naskah, paraf draf)
- **Staf TU**: `tu@contoh.ac.id` (Akses registrasi surat masuk, penyusunan draf surat keluar, cetak agenda)
- **Petugas Arsip**: `arsip@contoh.ac.id` (Akses klasifikasi surat dan modul kearsipan)

### Skenario Uji Manual Berurutan:
1. **Login & Dashboard**:
   - Buka `http://laras.test/login`.
   - Klik salah satu akun demo atau masukkan email `admin@contoh.ac.id` dan password `password`.
   - Masuk ke dashboard dan verifikasi navbar memuat nama instansi "Politeknik Contoh Indonesia".
2. **Penyusunan & Persetujuan Surat Keluar**:
   - Login sebagai Staf TU (`tu@contoh.ac.id`), buka menu **Surat Keluar** $\rightarrow$ **Buat Draf**.
   - Masukkan perihal, pilih klasifikasi dan penandatangan, lalu klik **Simpan & Ajukan**.
   - Login sebagai Direktur (`direktur@contoh.ac.id`), buka daftar surat keluar yang menunggu persetujuan, klik **Setujui**. Nomor surat otomatis dialokasikan dengan format resmi atomik.
3. **Uji Keamanan XSS (Script / Error Injection)**:
   - Buat draf surat keluar dan sisipkan payload: `<img src=x onerror=alert(1)>`.
   - Simpan draf dan buka halaman pratinjau: tidak ada popup alert yang muncul karena konten disanitasi oleh `HTMLPurifier` di server dan `DOMPurify` di peramban.
4. **Disposisi Berjenjang & Plt**:
   - Login sebagai Direktur, buka detail surat masuk, klik **Buat Disposisi**.
   - Teruskan disposisi ke Kepala Jurusan. Kepala Jurusan dapat meneruskan kembali ke bawahan atau menyelesaikan dengan bukti lampiran.

---

## 4. Hasil Pengujian Otomatis

### SQLite In-Memory (`php artisan test`):
```text
Tests:    3 skipped, 157 passed (1029 assertions)
Duration: 44.11s
Status:   LULUS
```
*Daftar test yang dilewati beserta alasannya:*
1. `ActivityLogAppendOnlyTest > truncating activity log via raw database query is rejected by trigger`: Statement-level trigger TRUNCATE merupakan fitur spesifik engine PostgreSQL dan tidak didukung oleh SQLite parser.
2. `SuratKeluarParallelApprovalTest > parallel approval concurrency on postgresql`: Memerlukan koneksi driver PostgreSQL riil dengan dukungan isolasi transaksi row-locking multi-koneksi.
3. `SuratMasukConcurrencyNumberingTest > true multi process concurrency with parallel processes produces no duplicates or gaps`: Memerlukan live multi-process daemon PostgreSQL.

### PostgreSQL port 5433 (`php artisan test -c phpunit.pgsql.xml`):
```text
Tests:    160 passed (1033 assertions)
Duration: 79.46s
Status:   100% LULUS (Semua 160 test dieksekusi tanpa ada yang di-skip)
```

---

## 5. Hasil Benchmark Skala 100.000 Data

Perintah eksekusi: `php artisan eoffice:benchmark-3a`

| Skenario Pengujian | Target SLA | Hasil Pengukuran Nyata | Evaluasi |
| :--- | :---: | :---: | :---: |
| **Daftar Surat Masuk** (filter `status=terdaftar & sifat=biasa`) | < 2.000 ms | **1243.04 ms** | MEMENUHI TARGET (< 2s) |
| **Kotak Masuk Disposisi** (pengguna terpadat 100k baris) | < 2.000 ms | **571.03 ms** | MEMENUHI TARGET (< 2s) |
| **Daftar Surat Keluar** (filter `status=disetujui & sifat=biasa`) | < 2.000 ms | **649.15 ms** | MEMENUHI TARGET (< 2s) |
| **Optimasi Indeks Gabungan** (`idx_disp_rec_user_status_id`) | - | Execution Time: **0.055 ms** (Covering) | Optimasi Akses Baris Terarah |

---

## 6. Perubahan Keamanan & RBAC

1. **Matriks Hak Akses (RBAC)**:
   - Terpetakan 5 peran PRD resmi: Admin Instansi, Pimpinan, Kepala Unit, Staf TU, dan Petugas Arsip.
   - Tidak ada lagi ketergantungan `hasRole()` atau pengecekan hardcoded role di level controller/policy.
   - Hak akses disiapkan untuk modul mendatang: `surat-keluar.tte` (Fase 3B), `surat-keluar.kirim-eksternal` (Fase 3B), dan `arsip.penyusutan` (Fase 4).
2. **Keamanan Konten**:
   - Sanitasi berlapis HTMLPurifier (server) dan DOMPurify (client).
   - Isolasi sandbox PDF Dompdf (`isRemoteEnabled = false`, PHP execution disabled, local assets only).
   - Header keamanan HTTP ketat: CSP, X-Content-Type-Options, Referrer-Policy.

---

## 7. Keputusan Teknis & Asumsi
1. **Penomoran Berbasis Tanggal Naskah & Reset Tahunan (OQ-08 & OQ-14)**:
   - Nomor surat resmi selalu reset per 1 Januari tahun kalender baru per unit/pola format.
   - Format penomoran mengacu pada tahun persetujuan naskah dinas resmi.
2. **Kemandirian Role Spatie**:
   - Kolom legacy `role` pada tabel `users` dideprekasi demi kepatuhan arsitektur RBAC multi-unit dan multi-jabatan.

---

## 8. Status Penutupan Fase 3A (Semua Bagian 1, 2, 3, dan 4 Selesai)

1. **Bagian 1 (Keamanan Konten)**: SELESAI (XSS sanitasi ganda, Dompdf hardening, optimistic/pessimistic locking, Security Headers).
2. **Bagian 2 (RBAC & Instalasi)**: SELESAI (Single source permissions, RolePermissionSeeder, Setup Wizard, pemisahan ProductionSeeder vs DemoSeeder, DisposisiPolicy depth 20 & privacy, docs PERMISSIONS_MATRIX dan README).
3. **Bagian 3 (UI Satu Layout)**: SELESAI (Dashboard Inertia/React, pembersihan Blade layouts, single menu filtered by permissions, volume chart Chart.js, meta notranslate, ErrorBoundary, zero-downtime assets).
4. **Bagian 4 (Penutupan Fase 3A)**: SELESAI (Benchmark 100k data lolos SLA, 28 migrasi 100% reversibel rollback sampai kosong dan re-migrate, APP_LOCALE=id, dualitas actions harmonis, OQ-14 terdokumentasi, 160 test PostgreSQL 5433 lulus 100%).
5. **Fase 3B**: BELUM dan TIDAK dimulai, menunggu instruksi kerja berikutnya sesuai aturan batas lingkup tugas.

---

## 9. Prosedur Rollback

Jika diperlukan pengembalian ke kondisi sebelum audit penutupan:
```bash
git checkout 2550096
# atau kembali ke commit awal cabang
git checkout feature/fase-3a-surat-keluar
```

