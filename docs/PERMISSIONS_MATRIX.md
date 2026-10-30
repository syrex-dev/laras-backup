# Matriks Hak Akses (RBAC) & Pemakaian Permission Sistem E-Office

Dokumen ini merupakan dokumentasi resmi pemetaan Hak Akses (*Permissions*) terhadap Peran (*Roles*) resmi PRD, serta penelusuran lokasi pemakaian permission (*usage references*) di dalam basis kode aplikasi (`app/` dan `routes/`).

---

## 1. Peran Resmi Sistem (PRD Canonical Roles)

Sistem E-Office beroperasi dengan model *single-tenant* persuratan institusi yang hanya mengakui **5 peran resmi PRD**:
1. **Admin Instansi**: Administrator sistem, manajemen konfigurasi institusi, unit kerja, jabatan, akun pengguna, dan pengawasan audit.
2. **Pimpinan**: Pejabat tertinggi (Direktur/Rektor/Ketua), pengambil keputusan utama, persetujuan/penandatangan naskah dinas keluar, serta disposisi utama.
3. **Kepala Unit**: Pimpinan tingkat unit kerja (Kajur/Kaprodi/Kabag/Dekan), pembuat disposisi berjenjang, pemeriksa draf surat, dan paraf.
4. **Staf TU**: Operator tata usaha, registrasi persuratan masuk, pembuat draf surat keluar, dan penerusan naskah.
5. **Petugas Arsip**: Pengelola master klasifikasi naskah dinas, jadwal retensi arsip (JRA), dan log statis kearsipan.

> **Catatan:** Seluruh otorisasi aplikasi berbasis pada pemanggilan izin (*permission-based*) seperti `$user->can('surat-masuk.lihat')` atau middleware `permission:...`. Tidak ada lagi penggunaan `hasRole()` atau pengecekan hardcoded role di level controller/policy.

---

## 2. Matriks Hak Akses: Permission × Role Resmi

*Keterangan:* `✓` = Memiliki Hak Akses; `-` = Tidak Diberikan Hak Akses.

| Permission | Modul | Admin Instansi | Pimpinan | Kepala Unit | Staf TU | Petugas Arsip | Status / Fase |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| `organisasi.kelola` | organisasi | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `organisasi.lihat` | organisasi | ✓ | ✓ | ✓ | ✓ | ✓ | Fase 3A (Aktif) |
| `surat-masuk.lihat` | surat_masuk | ✓ | ✓ | ✓ | ✓ | ✓ | Fase 3A (Aktif) |
| `surat-masuk.registrasi` | surat_masuk | ✓ | - | - | ✓ | - | Fase 3A (Aktif) |
| `surat-masuk.ubah` | surat_masuk | ✓ | - | - | ✓ | - | Fase 3A (Aktif) |
| `surat-masuk.batalkan` | surat_masuk | ✓ | - | - | ✓ | - | Fase 3A (Aktif) |
| `disposisi.lihat` | disposisi | ✓ | ✓ | ✓ | ✓ | - | Fase 3A (Aktif) |
| `disposisi.buat` | disposisi | ✓ | ✓ | ✓ | - | - | Fase 3A (Aktif) |
| `disposisi.teruskan` | disposisi | ✓ | ✓ | ✓ | - | - | Fase 3A (Aktif) |
| `disposisi.tindaklanjut`| disposisi | ✓ | ✓ | ✓ | ✓ | - | Fase 3A (Aktif) |
| `disposisi.kelola` | disposisi | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `disposisi.lihat-semua`| disposisi | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `disposisi.ambil-alih` | disposisi | ✓ | ✓ | ✓ | - | - | Fase 3A (Aktif) |
| `surat-keluar.lihat` | surat_keluar | ✓ | ✓ | ✓ | ✓ | ✓ | Fase 3A (Aktif) |
| `surat-keluar.buat` | surat_keluar | ✓ | - | ✓ | ✓ | - | Fase 3A (Aktif) |
| `surat-keluar.ubah-semua`| surat_keluar| ✓ | - | ✓ | - | - | Fase 3A (Aktif) |
| `surat-keluar.periksa` | surat_keluar | ✓ | ✓ | ✓ | - | - | Fase 3A (Aktif) |
| `surat-keluar.setujui` | surat_keluar | - | ✓ | ✓ | - | - | Fase 3A (Aktif) |
| `surat-keluar.nomori` | surat_keluar | ✓ | ✓ | - | ✓ | - | Fase 3A (Aktif) |
| `surat-keluar.batalkan`| surat_keluar | ✓ | ✓ | - | - | - | Fase 3A (Aktif) |
| `surat-keluar.template`| surat_keluar | ✓ | - | - | ✓ | - | Fase 3A (Aktif) |
| `surat-keluar.kelola` | surat_keluar | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `arsip.lihat` | arsip | ✓ | ✓ | ✓ | ✓ | ✓ | Fase 3A (Aktif) |
| `arsip.kelola` | arsip | ✓ | - | - | - | ✓ | Fase 3A (Aktif) |
| `sistem.audit` | sistem | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `sistem.pemeliharaan` | sistem | ✓ | - | - | - | - | Fase 3A (Aktif) |
| `surat-keluar.tte` | surat_keluar | - | ✓ | ✓ | - | - | **Fase 3B (Disiapkan)** |
| `surat-keluar.kirim-eksternal`| surat_keluar| ✓ | - | - | ✓ | - | **Fase 3B (Disiapkan)** |
| `arsip.penyusutan` | arsip | - | - | - | - | ✓ | **Fase 4 (Disiapkan)** |

---

## 3. Tabel Pemakaian Permission dalam Kode Sumber (`app/` dan `routes/`)

| Permission | Lokasi Penggunaan (File:Baris) | Konteks / Mekanisme Otorisasi | Keterangan / Fase |
| :--- | :--- | :--- | :--- |
| `organisasi.kelola` | `app/Http/Controllers/Letters/SuratMasukController.php:323` | Policy / Filter: Akses global surat masuk | Fase 3A (Aktif) |
| | `app/Policies/TemplateSuratPolicy.php:15, 20, 25, 30, 35` | Policy: Hak kelola template oleh admin | |
| | `app/Policies/UserPolicy.php:14, 19, 24, 29, 34` | Policy: Hak CRUD akun pengguna instansi | |
| | `routes/web.php:91` | Route Middleware: Kelola template surat | |
| | `routes/web.php:127` | Route Middleware: Master organisasi (unit, jabatan, profil, user) | |
| | `routes/web.php:137` | Route Middleware: Akses admin suite | |
| `organisasi.lihat` | *(Konfigurasi Hak Akses Terpusat)* | Hak baca struktur hierarki unit/jabatan | Fase 3A (Aktif) |
| `surat-masuk.lihat` | `app/Http/Controllers/Letters/SuratMasukController.php:322` | Filter: Membaca daftar surat masuk | Fase 3A (Aktif) |
| | `app/Policies/SuratMasukPolicy.php:14, 19` | Policy: viewAny & view surat masuk | |
| `surat-masuk.registrasi` | `app/Http/Controllers/Letters/SuratMasukController.php:323` | Filter: Hak akses pendaftaran | Fase 3A (Aktif) |
| | `app/Http/Requests/SuratMasuk/StoreSuratMasukRequest.php:11` | FormRequest: Validasi izin registrasi | |
| | `app/Policies/SuratMasukPolicy.php:24` | Policy: create surat masuk baru | |
| `surat-masuk.ubah` | `app/Http/Requests/SuratMasuk/UpdateSuratMasukRequest.php:11` | FormRequest: Validasi izin ubah naskah | Fase 3A (Aktif) |
| | `app/Policies/SuratMasukPolicy.php:29` | Policy: update surat masuk | |
| `surat-masuk.batalkan` | `app/Http/Requests/SuratMasuk/CancelSuratMasukRequest.php:11` | FormRequest: Validasi izin pembatalan | Fase 3A (Aktif) |
| | `app/Policies/SuratMasukPolicy.php:34, 39` | Policy: cancel & delete surat masuk | |
| `disposisi.lihat` | `app/Policies/DisposisiPolicy.php:20, 34` | Policy: viewAny & view disposisi | Fase 3A (Aktif) |
| `disposisi.buat` | `app/Http/Controllers/Letters/DisposisiController.php:32` | Controller: Filter pembuatan disposisi awal | Fase 3A (Aktif) |
| | `app/Policies/DisposisiPolicy.php:89` | Policy: create disposisi awal | |
| `disposisi.teruskan` | `app/Policies/DisposisiPolicy.php:101` | Policy: forward disposisi berjenjang | Fase 3A (Aktif) |
| `disposisi.tindaklanjut` | `app/Policies/DisposisiPolicy.php:121` | Policy: followup laporan tindak lanjut | Fase 3A (Aktif) |
| `disposisi.kelola` | `app/Http/Controllers/Disposisi/DisposisiController.php:239` | Controller: Hak supervisi administrasi | Fase 3A (Aktif) |
| | `app/Http/Controllers/Letters/DisposisiController.php:30, 83` | Controller: Akses pengawasan lembar disposisi | |
| | `app/Http/Controllers/Letters/SuratMasukController.php:323` | Controller: Akses audit/timeline surat masuk | |
| | `app/Policies/DisposisiPolicy.php:20, 30, 89, 97, 117, 138` | Policy: Override supervisi disposisi | |
| `disposisi.lihat-semua` | *(Konfigurasi Hak Akses Terpusat)* | Hak supervisi log seluruh disposisi | Fase 3A (Aktif) |
| `disposisi.ambil-alih` | *(Policy takeOver / Plt Mandat)* | Izin pengambilalihan instruksi tertunda | Fase 3A (Aktif) |
| `surat-keluar.lihat` | `app/Policies/SuratKeluarPolicy.php:15` | Policy: viewAny daftar surat keluar | Fase 3A (Aktif) |
| `surat-keluar.buat` | `app/Policies/SuratKeluarPolicy.php:16, 62` | Policy: create draf surat keluar baru | Fase 3A (Aktif) |
| | `app/Policies/TemplateSuratPolicy.php:15, 20` | Policy: Izin membaca template saat buat draf | |
| `surat-keluar.ubah-semua` | `app/Policies/SuratKeluarPolicy.php:18, 25, 83, 112` | Policy: update/delete draf rekan satu unit | Fase 3A (Aktif) |
| `surat-keluar.periksa` | `app/Policies/SuratKeluarPolicy.php:17` | Policy: viewAny untuk reviewer/pemeriksa | Fase 3A (Aktif) |
| `surat-keluar.setujui` | `routes/web.php:52` | Route Middleware: Persetujuan akhir pimpinan | Fase 3A (Aktif) |
| `surat-keluar.nomori` | *(Action Penomoran Otomatis)* | Izin trigger alokasi nomor resmi surat | Fase 3A (Aktif) |
| `surat-keluar.batalkan` | `app/Policies/SuratKeluarPolicy.php:125` | Policy: Pembatalan surat bernomor resmi | Fase 3A (Aktif) |
| `surat-keluar.template` | `app/Policies/TemplateSuratPolicy.php:15, 20, 25, 30, 35` | Policy: Master template naskah dinas | Fase 3A (Aktif) |
| | `routes/web.php:91, 155` | Route Middleware: CRUD template surat | |
| `surat-keluar.kelola` | `app/Domain/SuratKeluar/Actions/ApproveSuratKeluarAction.php:56` | Action: Otorisasi intervensi approval | Fase 3A (Aktif) |
| | `app/Domain/SuratKeluar/Actions/RejectSuratKeluarAction.php:62` | Action: Otorisasi intervensi reject | |
| | `app/Domain/SuratKeluar/Actions/RequestRevisionSuratKeluarAction.php:62`| Action: Otorisasi permintaan revisi | |
| | `app/Http/Controllers/Letters/SuratKeluarController.php:50, 240` | Controller: Supervisi alur surat keluar | |
| | `app/Policies/SuratKeluarPolicy.php:19, 25, 63, 78, 107, 125` | Policy: Global override surat keluar | |
| | `routes/web.php:91, 155` | Route Middleware: Template & kelola surat | |
| `arsip.lihat` | *(Konfigurasi Hak Akses Terpusat)* | Hak baca kode klasifikasi & arsip | Fase 3A (Aktif) |
| `arsip.kelola` | `routes/web.php:74` | Route Middleware: Master klasifikasi arsip | Fase 3A (Aktif) |
| `sistem.audit` | *(Konfigurasi Hak Akses Terpusat)* | Hak pembacaan log audit append-only | Fase 3A (Aktif) |
| `sistem.pemeliharaan` | `routes/web.php:137` | Route Middleware: Pencadangan & recycle bin | Fase 3A (Aktif) |
| `surat-keluar.tte` | *(Disiapkan untuk Fase 3B)* | Integrasi sertifikat digital BSrE/Peruri | **Fase 3B** |
| `surat-keluar.kirim-eksternal` | *(Disiapkan untuk Fase 3B)* | Ekspedisi keluar via SMTP/Mail API resmi | **Fase 3B** |
| `arsip.penyusutan` | *(Disiapkan untuk Fase 4)* | Eksekusi JRA pemusnahan berkas inaktif | **Fase 4** |

---

## 4. Perintah Artisan Verifikasi

Untuk memeriksa matriks dan tabel pemakaian secara dinamis kapan saja di terminal, jalankan:
```bash
php artisan eoffice:permissions-matrix
```
Perintah ini membaca konfigurasi kanonik `config/permissions.php` dan memindai direktori `app/` serta `routes/` secara otomatis.
