# Roadmap Pengembangan E-Office (Single-Tenant Berlangganan)

Dokumen ini memetakan tahapan pengembangan aplikasi E-Office secara terstruktur, urutan modul yang dikerjakan, dan kriteria penyelesaian (*Definition of Done*) untuk setiap fase.

---

## Ringkasan Fase Pengembangan

| Fase | Fokus Utama | Target Luaran |
|---|---|---|
| **Fase 0** | **Fondasi & Inisialisasi Stack** | Wizard setup awal, profil instansi & kamus istilah, RBAC, lisensi stub & mode read-only, audit trail, layout React/Inertia responsif, seeder demo |
| **Fase 1** | **Surat Masuk** | Registrasi surat, nomor agenda otomatis, unggah & pratinjau lampiran, lembar disposisi ber-QR, pembatalan ter-audit, daftar surat cepat |
| **Fase 2** | **Disposisi Berjenjang** | Multi-disposisi, instruksi cepat, disposisi berjenjang antar-pejabat, tindak lanjut & unggah bukti, notifikasi queue, UI mobile pimpinan ($\le$ 3 ketukan) |
| **Fase 3** | **Surat Keluar & Approval** | Template surat + variabel otomatis, alur paraf/pemeriksaan/revisi, penomoran atomik anti-ganda (row locking), TTE/tanda tangan basah, verifikasi QR publik |
| **Fase 4** | **Kearsipan & Laporan** | Klasifikasi arsip dinamis, Jadwal Retensi Arsip (JRA), otomatisasi arsip, buku agenda surat (ekspor PDF/Excel), pencarian full-text |
| **Fase 5** | **Operasional & Paket Langganan** | Backup/restore otomatis, skrip update 1-klik dengan auto-rollback, penegakan kuota & entitlement paket (Dasar/Standar/Lanjutan), telemetri aman |

---

## Rincian Pekerjaan & Definisi Selesai Tiap Fase

### FASE 0 — Fondasi Sistem & Inisialisasi Stack (Sedang Berjalan)

#### Item Pekerjaan:
1. **Stack & Tooling Setup:**
   - Inisialisasi stack: Laravel stabil + Inertia.js + React + Tailwind CSS + TypeScript.
   - Konfigurasi code quality: Laravel Pint, ESLint, Prettier, TypeScript strict check.
2. **Komponen UI & Layout Responsif:**
   - AppLayout (Sidebar desktop, drawer mobile, topbar dengan notifikasi dan profil).
   - UI Atoms: Button, Table dengan sorting/pagination, Modal, Toast Notification, Status Badge, FormInput.
3. **Wizard Setup Awal (Pemasangan Baru):**
   - Halaman khusus bila sistem belum disetup (`institution_profiles` kosong / status `uninitialized`).
   - Langkah wizard: (1) Data instansi & logo/kop, (2) Zona waktu & format penomoran default, (3) Pembuatan akun Admin Instansi pertama.
4. **Profil Instansi & Kamus Istilah Dinamis:**
   - Halaman pengaturan profil instansi (branding, logo, alamat, email).
   - Kamus istilah terpusat (`terminology_dict`): konfigurasi label jabatan/unit kerja (misal: "Kajur" $\leftrightarrow$ "Kabid") yang otomatis terintegrasi ke seluruh UI via custom hook `useTerminology`.
5. **Struktur Organisasi & RBAC:**
   - CRUD Unit Kerja (`org_units`) hierarkis dan Jabatan (`positions`).
   - RBAC granular dengan Spatie Permission: Peran bawaan (Admin Instansi, Pimpinan, Pejabat/Kepala Unit, Staf/Tata Usaha, Petugas Arsip).
   - Form penugasan user ke jabatan dan unit kerja.
6. **Audit Log Append-Only:**
   - Helper/Service `AuditLogger::log(...)` yang merekam setiap aksi penting.
   - Proteksi model Eloquent agar record `activity_logs` tidak dapat di-update atau di-delete.
7. **Modul Lisensi (Stub) & Mode Read-Only:**
   - Skema `license_states` (status, paket, batas kuota, tanggal kedaluwarsa).
   - `LicenseService` stub (antarmuka verifikasi online/offline).
   - Middleware `EnforceReadOnlyMode`: jika lisensi habis, blokir seluruh operasi mutasi (POST/PUT/DELETE) dan alihkan ke mode **Baca-Saja + Ekspor Penuh**.
8. **Demo Seeder:**
   - Seeder instansi politeknik lengkap dengan struktur jurusan, prodi, dan akun contoh per peran.

#### Definisi Selesai (DoD) Fase 0:
- [ ] Instalasi dapat dijalankan dari nol dengan `composer install`, `npm install`, `php artisan migrate --seed`, dan `npm run dev`.
- [ ] Wizard setup muncul otomatis saat database bersih dan berhasil menginisialisasi sistem.
- [ ] Pergantian istilah jabatan di pengaturan langsung merubah teks di seluruh halaman UI tanpa sentuh kode.
- [ ] Pengujian unit/feature lolos untuk otorisasi RBAC, audit log append-only, dan middleware read-only saat lisensi expired.

---

### FASE 1 — Surat Masuk

#### Item Pekerjaan:
1. Registrasi surat masuk (surat fisik dan email) dengan nomor agenda otomatis per tahun/unit.
2. Pengunggahan berkas pindaian (PDF/gambar) dengan penampil (previewer) langsung di browser.
3. Pencetakan lembar disposisi dan bukti terima ber-QR code verifikasi.
4. Fitur pembatalan/koreksi registrasi: wajib menginput alasan pembatalan dan otomatis tercatat di audit log.
5. Halaman daftar surat masuk dengan filter status, tanggal, sifat surat, pencarian cepat, dan pagination server-side.

#### Definisi Selesai (DoD) Fase 1:
- [ ] Nomor agenda terbentuk otomatis berurutan tanpa jeda atau ganda.
- [ ] Surat masuk dapat diunggah lampirannya dan dibuka langsung di dalam aplikasi.
- [ ] Lembar disposisi terformat rapi siap cetak A4 dengan QR code valid.
- [ ] Surat bernomor agenda tidak dapat di-hard delete, hanya dibatalkan dengan riwayat lengkap.

---

### FASE 2 — Disposisi Berjenjang

#### Item Pekerjaan:
1. Formulir disposisi pimpinan: pemilihan multi-penerima, pilihan instruksi cepat (template), catatan khusus, skala prioritas, dan tanggal tenggat.
2. Disposisi berjenjang: pejabat penerima disposisi dapat meneruskan instruksi ke unit/bawahan di bawahnya dengan hierarki yang jelas.
3. Modul tindak lanjut: penerima dapat memperbarui progress, mengunggah file bukti penyelesaian, atau menautkan ke draf surat balasan.
4. Sistem notifikasi in-app dan email transaksional via antrean (queue).
5. Desain mobile responsif untuk pimpinan: kartu surat ringkas dan aksi disposisi dalam $\le$ 3 ketukan.

#### Definisi Selesai (DoD) Fase 2:
- [ ] Pimpinan dapat mendisposisikan surat ke beberapa staf sekaligus dari smartphone.
- [ ] Alur hierarki penerusan disposisi dari pimpinan puncak ke staf pelaksana terlihat transparan di timeline surat.
- [ ] Status surat otomatis berubah menjadi `diproses` saat disposisi mulai dikerjakan dan `selesai` saat semua penerima menuntaskan tugas.

---

### FASE 3 — Surat Keluar & Persetujuan (Approval)

#### Item Pekerjaan:
1. Master template surat naskah dinas (surat tugas, nota dinas, edaran, SK) dengan variabel otomatis `{nama}`, `{nip}`, `{nomor_surat}`, dan kop surat resmi.
2. Editor draf surat (rich text) dengan riwayat revisi draf (`surat_keluar_versions`).
3. Alur persetujuan berjenjang: aksi Setujui, Paraf, Minta Revisi (dengan catatan perbaikan), dan Tolak.
4. Penomoran otomatis berbasis token (`{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}`) dengan penguncian baris transaksi database (`lockForUpdate`).
5. Penandatanganan hybrid: tanda tangan basah (unggah scan) dan adapter TTE tersertifikasi.
6. Halaman publik verifikasi keaslian surat berbasis QR code token.
7. Distribusi tembusan otomatis dan pencatatan resi pengiriman.

#### Definisi Selesai (DoD) Fase 3:
- [ ] Penomoran surat bebas bentrok (*race-condition free*) saat diuji dengan request konkurensi simultan.
- [ ] Draf surat yang diminta revisi kembali ke penyusun dengan catatan jelas dan versi sebelumnya tersimpan.
- [ ] QR code pada dokumen hasil ekspor PDF berhasil diverifikasi keasliannya pada halaman publik `/verify/{token}`.

---

### FASE 4 — Kearsipan Dinamis & Laporan

#### Item Pekerjaan:
1. Master kode klasifikasi arsip dinamis dan konfigurasi Jadwal Retensi Arsip (JRA - aktif/inaktif).
2. Otomasi arsip: surat masuk yang selesai dan surat keluar yang terbit otomatis tercatat di modul arsip.
3. Buku agenda surat masuk dan surat keluar berformat standar kearsipan dengan fitur ekspor ke PDF dan Excel.
4. Pencarian full-text berkecepatan tinggi pada metadata surat dan teks hasil OCR.

#### Definisi Selesai (DoD) Fase 4:
- [ ] Buku agenda per semester/tahun dapat diunduh dalam format Excel dan PDF siap cetak.
- [ ] Dokumen yang melewati masa retensi aktif otomatis ditandai untuk penyusutan/pemindahan status arsip.
- [ ] Pencarian kata kunci pada 100.000 data menghasilkan respons di bawah 2 detik.

---

### FASE 5 — Operasional & Paket Langganan

#### Item Pekerjaan:
1. Otomasi pencadangan data (backup database & berkas terkompresi) serta prosedur pemulihan (restore).
2. Skrip pembaruan versi satu-perintah/satu-klik dengan mekanisme rollback otomatis jika migrasi gagal.
3. Penegakan hak akses per paket (Dasar, Standar, Lanjutan) melalui entitlement: pembatasan fitur (OCR, TTE, SSO, WhatsApp) dan kuota storage/user.
4. Dashboard kesehatan sistem dan telemetri aman (hanya metrik operasional, tanpa data berkas atau dokumen pribadi).

#### Definisi Selesai (DoD) Fase 5:
- [ ] Melebihi kuota user atau storage tidak mengunci data yang ada, hanya memblokir penambahan baru.
- [ ] Simulasi pembaruan sistem yang gagal otomatis mengembalikan versi dan data ke kondisi semula tanpa kehilangan data.
