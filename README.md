# E-Office (Tata Usaha & Persuratan Digital Instansi)

Aplikasi persuratan digital, tata usaha naskah dinas, disposisi berjenjang, dan kearsipan dinamis berbasis web modern (Laravel 11 + React Inertia + Tailwind CSS + PostgreSQL).

---

## 🚀 Panduan Memulai Cepat (Quick Start)

### 1. Prasyarat Sistem
- **PHP**: 8.2 atau 8.3+ (ekstensi `pdo`, `pdo_sqlite` untuk unit test lokal, dan `pdo_pgsql` untuk PostgreSQL).
- **Node.js**: v20+ dan npm.
- **Composer**: v2+.
- **Database**: PostgreSQL 15+ (target produksi) atau SQLite (pengembangan lokal).
- **Ekstensi PostgreSQL (`pg_trgm`)**:
  Untuk mendukung pencarian teks berkecepatan tinggi berbasis trigram GIN pada naskah persuratan, aktifkan ekstensi `pg_trgm` pada basis data target:
  ```sql
  CREATE EXTENSION IF NOT EXISTS pg_trgm;
  ```
- **Hak Akses Basis Data (Database Privileges)**:
  Pengguna database yang digunakan oleh aplikasi E-Office memerlukan hak: `CREATE`, `CONNECT`, pembuatan tabel, pembuatan indeks (termasuk GIN index), dan pembuatan trigger bahasa PL/pgSQL (untuk perlindungan append-only audit trail).
- **Kompatibilitas SQLite**:
  Seluruh migrasi yang memanfaatkan fitur spesifik PostgreSQL (seperti `pg_trgm`, GIN index, dan trigger append-only) telah diproteksi menggunakan klausul kondisional `if (DB::getDriverName() === 'pgsql')`. Dengan demikian, migrasi dan pengujian lokal via SQLite in-memory maupun file berjalan aman tanpa kendala.


### 2. Instalasi Dependensi
```bash
# Salin konfigurasi environment
cp .env.example .env

# Instal paket backend
composer install

# Instal paket frontend
npm install

# Buat application key
php artisan key:generate
```

### 3. Migrasi Database & Seeding

#### A. Lingkungan Produksi (Production Install)
Instalasi produksi **TIDAK** memakai seeder demo. Pada server produksi (`APP_ENV=production`), inisialisasi basis data dilakukan dengan:
```bash
# Jalankan migrasi tabel
php artisan migrate --force

# Jalankan seeder produksi (Role PRD, Permission, Klasifikasi Arsip, Master Templat)
php artisan db:seed --class=ProductionSeeder --force
```
> [!IMPORTANT]
> `ProductionSeeder` bersifat idempoten dan aman produksi (tanpa akun pengguna, tanpa surat demo, dan tanpa menandai instansi terkonfigurasi). Setelah menjalankan seeder produksi, akses `/setup` di browser untuk menjalankan **Setup Wizard** guna mendaftarkan Administrator Instansi pertama dan konfigurasi profil organisasi awal.

#### B. Lingkungan Pengembangan Lokal (Local / Testing QA)
Untuk pengujian dan pengembangan lokal dengan contoh data instansi fiktif (*Politeknik Contoh Indonesia*):
```bash
# Jalankan migrasi dan isi seluruh data acuan + dataset demo
php artisan migrate:fresh --seed
```
> [!NOTE]
> `DemoSeeder` dan seluruh seeder demo di dalamnya dilindungi pengaman sistem ketat (`app()->environment(['local', 'testing'])`) dan akan **ditolak/dilewati otomatis** apabila dijalankan di luar environment `local` atau `testing`.

### 4. Menjalankan Server Pengembangan
```bash
# Jalankan server frontend Vite (Hot Module Replacement)
npm run dev

# Di terminal terpisah, jalankan server Laravel
php artisan serve
```
Akses aplikasi melalui browser di: `http://127.0.0.1:8000`

---

## 🧪 Panduan Menjalankan Pengujian (Testing Suite)

### 1. Pengujian Otomatis Default (SQLite In-Memory)
Test suite default dikonfigurasi untuk berjalan cepat tanpa konfigurasi eksternal menggunakan SQLite in-memory:
```bash
php artisan test
```

### 2. Pengujian Terhadap Database PostgreSQL
Tersedia konfigurasi pengujian khusus PostgreSQL yang memverifikasi constraint relational dan trigger database append-only (`activity_logs`):
```bash
# Pastikan PostgreSQL telah aktif dan database eoffice_testing telah dibuat
# CREATE DATABASE eoffice_testing;
php artisan test -c phpunit.pgsql.xml
```

### 3. Pemeriksaan Statis Kode (Lint & Typecheck)
```bash
# Pemeriksaan tipe TypeScript frontend
npm run typecheck

# Pemeriksaan ESLint frontend
npm run lint

# Pemeriksaan standar kode PHP Pint
vendor/bin/pint --test
```

---

## 👥 Daftar Akun Percontohan Demo (Politeknik Contoh)

Semua akun demo di bawah ini menggunakan kata sandi standar: `password`

| Peran (Role) | Nama Pegawai | Alamat Email | Jabatan Struktural / Unit | Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Instansi** | Administrator Instansi | `admin@contoh.ac.id` | Bagian IT & Administrasi Sistem | Kelola unit kerja, jabatan, master pengguna, kuota lisensi |
| **Pimpinan** | Dr. Ir. Budi Santoso, M.T. | `direktur@contoh.ac.id` | Direktur (Level 1) | Tanda tangan elektronik (TTE), verifikasi surat, disposisi |
| **Pimpinan** | Prof. Dr. Ir. Haryanto, M.Eng. | `wadir1@contoh.ac.id` | Wakil Direktur Bidang Akademik (Level 2) | Disposisi, verifikasi naskah, penerima delegasi/Plt |
| **Kepala Unit** | Drs. Hendra Permana, M.Kom. | `kajur.jtk@contoh.ac.id` | Ketua Jurusan Teknik Komputer & Informatika | Menerima & meneruskan disposisi, verifikasi surat keluar |
| **Staf TU** | Siti Rahayu, A.Md. | `tu@contoh.ac.id` | Staf Tata Usaha Persuratan | Registrasi surat masuk/keluar, penomoran agenda, pembatalan |
| **Petugas Arsip** | Agus Budiman, S.Sos. | `arsip@contoh.ac.id` | Petugas Arsip Dinamis | Klasifikasi arsip, pemberkasan, retensi, penyusutan |

---

## 🛠️ Pengembangan Lokal & Deploy

### 1. Mode Pengembangan Lokal (Hot Module Replacement)
Saat sedang aktif mengembangkan fitur baru atau memodifikasi antarmuka frontend (React/TypeScript):
```bash
npm run dev
```
Perintah ini menyalakan server Vite HMR dan secara otomatis membuat penanda `public/hot`.

### 2. Build Produksi & Deploy
Sebelum melakukan rilis/deploy ke server produksi atau setelah memperbarui kode aplikasi tanpa menjalankan dev server:
```bash
npm run build
```
> [!IMPORTANT]
> `npm run build` wajib dijalankan sebelum deploy atau rilis agar seluruh komponen React terbaru terkompilasi ke dalam folder `public/build/` dengan manifest yang valid. Folder `public/build` dan `public/hot` sudah dikecualikan di `.gitignore` sehingga tidak perlu di-push ke remote repository.

### 3. Pemecahan Masalah: Mengatasi Layar Putih (Blank Screen)
Jika halaman browser (misalnya `/login` atau halaman lainnya) menampilkan layar putih kosong (*blank screen*), hal tersebut biasanya disebabkan oleh:
1. **File `public/hot` tertinggal**:
   Jika server `npm run dev` dihentikan secara paksa, file `public/hot` terkadang masih tertinggal di folder `public/`. Laravel akan terus mencoba memuat aset dari server dev Vite lokal yang sudah mati.
   **Solusi**: Hapus file `public/hot` jika dev server tidak aktif:
   - Linux/macOS: `rm -f public/hot`
   - Windows PowerShell: `Remove-Item public/hot -Force -ErrorAction SilentlyContinue`
2. **Aset `public/build` kedaluwarsa atau belum dikompilasi**:
   Halaman yang baru ditambahkan belum terdaftar di `public/build/manifest.json`.
   **Solusi**: Jalankan kompilasi ulang aset produksi:
   ```bash
   npm run build
   ```
3. **Cache browser lokal**:
   Lakukan *hard refresh* di browser dengan menekan **`Ctrl + Shift + R`** atau **`Ctrl + F5`** (atau `Cmd + Shift + R` di macOS).

### 4. Ekstensi PHP Wajib & Penyelarasan PHP CLI vs Web (Herd)
Aplikasi membutuhkan PHP 8.3+ (pada lingkungan pengembangan lokal ini menggunakan **PHP 8.5.12**).
- **Ekstensi PHP yang WAJIB Aktif:**
  - `pdo_pgsql` & `pgsql` : Driver utama PostgreSQL (wajib untuk trigger PL/pgSQL & FTS)
  - `pdo_sqlite` & `sqlite3` : Pengujian suite lokal
  - `pdo_mysql` : Driver fallback MySQL (bila diperlukan)
  - `redis` : Driver antrean & cache Redis
  - `gd` : Rendering kop surat, stempel, dan lampiran gambar
  - `intl`, `zip`, `bcmath`, `curl`, `mbstring`, `fileinfo` : Manipulasi berkas & tata naskah
- **Lokasi `php.ini` Herd Web & CLI:**
  - File konfigurasi Herd: `C:\Users\<user>\.config\herd\bin\php85\php.ini`
  - Jika di terminal CLI Windows `php -v` masih memanggil PHP lain (misal XAMPP), selaraskan sesi terminal dengan memprioritaskan Herd bin di PATH:
    ```powershell
    $env:PATH = "C:\Users\$env:USERNAME\.config\herd\bin;" + $env:PATH
    ```
    Konfirmasi versi dan file `php.ini` aktif:
    ```powershell
    php -v
    php --ini
    ```

---

### 5. Konfigurasi Variabel Lingkungan Pengujian PostgreSQL
File `phpunit.pgsql.xml` mendukung penyesuaian kredensial database dinamis melalui Environment Variables dengan nilai default lokal (`force="false"`):
- `DB_HOST` (default: `127.0.0.1`)
- `DB_PORT` (default: `5433`)
- `DB_DATABASE` (default: `laras_test`)
- `DB_USERNAME` (default: `postgres`)
- `DB_PASSWORD` (default: `secret`)

Untuk pipeline CI/CD atau pengujian di port/host lain, tetapkan variabel lingkungan terlebih dahulu:
```bash
DB_PORT=5432 DB_DATABASE=ci_testing php artisan test -c phpunit.pgsql.xml
```

---

## ⚙️ Worker Antrean & Scheduler (Tugas Terjadwal)

Aplikasi E-Office mengandalkan pemrosesan latar belakang (*background job queue*) dan penjadwalan berkala (*task scheduler*) untuk menjaga performa HTTP tetap responsif saat menangani operasi berat seperti pengiriman email notifikasi, pengingat tenggat waktu, dan eskalasi naskah.

### 1. Konfigurasi Environment (`.env` & `.env.example`)
Pastikan variabel antrean dan surat elektronik telah dikonfigurasi dengan tepat:
```ini
# Koneksi Antrean (Queue)
QUEUE_CONNECTION=database

# Konfigurasi Pengiriman Email
MAIL_MAILER=log               # Gunakan 'log' untuk dev lokal, 'smtp' untuk produksi
MAIL_HOST=smtp.mailgun.org    # Atau server SMTP instansi
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="persuratan@contoh.ac.id"
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Menjalankan Worker Antrean (`queue:work`)
Worker antrean bertugas mengeksekusi job yang masuk ke tabel `jobs` (notifikasi email persuratan, rendering PDF asinkron, pembaruan indeks):
```bash
# Menjalankan worker di terminal:
php artisan queue:work --tries=3 --timeout=90
```
- **Di Lingkungan Linux / Server Produksi:**
  Gunakan **Supervisor** atau **Systemd** service agar worker terus berjalan sebagai daemon dan otomatis me-restart bila terjadi crash.
- **Di Lingkungan Windows & Laravel Herd:**
  - **Opsi Cepat Terminal**: Buka tab terminal baru di Herd dan jalankan `php artisan queue:work`.
  - **Opsi Otomatis (Herd Pro)**: Buka jendela pengaturan Herd, tambahkan service latar belakang (*Background Services*) yang menjalankan `php artisan queue:work`.
  - **Opsi Windows Service**: Pasang [NSSM (Non-Sucking Service Manager)](https://nssm.cc/) untuk mendaftarkan `php.bat artisan queue:work` sebagai Windows Service permanen.

### 3. Menjalankan Task Scheduler (`schedule:run`)
Scheduler mengotomatisasi tugas-tugas administratif berkala, seperti evaluasi tenggat waktu disposisi dan pemantauan eskalasi:
```bash
# Eksekusi scheduler satu putaran:
php artisan schedule:run
```
- **Di Lingkungan Linux (Cron):**
  Tambahkan satu baris entri pada crontab server (`crontab -e`):
  ```cron
  * * * * * cd /path/to/laras && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Di Lingkungan Windows & Laravel Herd:**
  - **Mode Pengembangan Lokal**: Jalankan perintah scheduler bawaan Laravel yang melakukan simulasi eksekusi cron setiap 1 menit:
    ```powershell
    php artisan schedule:work
    ```
  - **Windows Task Scheduler (Produksi Windows Server)**:
    1. Buka *Task Scheduler* (`taskschd.msc`).
    2. Buat *Basic Task* baru dengan nama `Laravel E-Office Scheduler`.
    3. Trigger: *Daily*, ulangi (*Repeat task every*) **1 minute** tanpa batas waktu (*indefinitely*).
    4. Action: *Start a program*, Program: `C:\Users\<user>\.config\herd\bin\php.bat` (atau `php.exe`), Arguments: `artisan schedule:run`, Start in: `d:\Coding\laras`.

### 4. ⚠️ Dampak Kritis Bila Worker & Scheduler Tidak Aktif
| Komponen | Status | Konsekuensi & Dampak Sistem |
| :--- | :--- | :--- |
| **Queue Worker** | ❌ **Mati** | • Notifikasi email penerimaan disposisi baru tidak terkirim ke pegawai.<br>• Notifikasi giliran paraf/persetujuan draf surat keluar tertahan di database (`jobs`).<br>• Beban antrean menumpuk dan menyebabkan lonjakan saat worker baru dinyalakan. |
| **Scheduler** | ❌ **Mati** | • Pengingat otomatis H-1 tenggat waktu disposisi (`eoffice:remind-disposisi`) tidak pernah dieksekusi.<br>• Penandaan otomatis status *Overdue* / Terlambat pada disposisi yang melewati batas tanggal tidak berjalan.<br>• Pembersihan berkas draf kedaluwarsa dan sinkronisasi nomor urut tahunan tidak terpicu. |

---

## 🛡️ Keamanan & Kepatuhan
1. **Audit Log Append-Only & Hardening Database**:
   - Tabel `activity_logs` dilindungi dua lapis trigger PL/pgSQL database:
     - `trg_activity_logs_no_update_delete` (`BEFORE UPDATE OR DELETE ON activity_logs FOR EACH ROW`)
     - `trg_activity_logs_no_truncate` (`BEFORE TRUNCATE ON activity_logs FOR EACH STATEMENT`)
   - **Panduan Pengerasan Lingkungan Produksi (Least Privilege Role):**
     Di lingkungan produksi, koneksi aplikasi Laravel **TIDAK BOLEH** menggunakan akun `superuser` (`postgres`) atau pemilik tabel (*table owner*). Buat peran database terbatas khusus aplikasi:
     ```sql
     CREATE ROLE eoffice_app WITH LOGIN PASSWORD 'strong_app_password';
     GRANT CONNECT ON DATABASE laras TO eoffice_app;
     GRANT USAGE ON SCHEMA public TO eoffice_app;
     GRANT SELECT, INSERT ON activity_logs TO eoffice_app;
     -- PENTING: JANGAN PERNAH berikan hak UPDATE, DELETE, TRUNCATE, atau ALTER TABLE pada activity_logs
     ```
   - **Catatan Batasan Keamanan (*Known Limitation*):**
     Trigger database secara efektif mencegah manipulasi dari aplikasi web atau injeksi SQL. Namun, akun dengan peran `superuser` atau `table owner` di PostgreSQL secara teknis memiliki otoritas untuk menjalankan `ALTER TABLE activity_logs DISABLE TRIGGER ALL` atau memanipulasi baris secara langsung via terminal database. Oleh karena itu, proteksi tingkat infrastruktur (rotasi password superuser, audit psql, pembatasan akses SSH ke host database) tetap menjadi keharusan dalam operasional produksi.
2. **Entitlement Lisensi & Mode Baca-Saja**: Jika masa lisensi berakhir, sistem otomatis beralih ke mode baca-saja (*read-only*), memblokir mutasi baru sementara tetap mengizinkan login, logout, pencarian, dan ekspor data secara penuh.
3. **RBAC Granular**: Setiap mutasi dilindungi perpaduan spatie permissions (`surat-masuk.registrasi`, `surat-masuk.batalkan`, dsb.) dan Laravel Policies.
