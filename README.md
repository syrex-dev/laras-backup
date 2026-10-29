# E-Office (Tata Usaha & Persuratan Digital Instansi)

Aplikasi persuratan digital, tata usaha naskah dinas, disposisi berjenjang, dan kearsipan dinamis berbasis web modern (Laravel 11 + React Inertia + Tailwind CSS + PostgreSQL).

---

## 🚀 Panduan Memulai Cepat (Quick Start)

### 1. Prasyarat Sistem
- **PHP**: 8.2 atau 8.3+ (ekstensi `pdo`, `pdo_sqlite` untuk unit test lokal, dan `pdo_pgsql` untuk PostgreSQL).
- **Node.js**: v20+ dan npm.
- **Composer**: v2+.
- **Database**: PostgreSQL 15+ (target produksi) atau SQLite (pengembangan lokal).

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

### 3. Migrasi & Data Uji Percontohan (Demo Seeder)
```bash
# Jalankan migrasi dan isi data demo Politeknik Contoh Indonesia
php artisan migrate:fresh --seed
```
> [!NOTE]
> `PoliteknikDemoSeeder` dilindungi pengaman sistem dan **HANYA** dapat dijalankan di environment `local` atau `testing`.

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

## 🛡️ Keamanan & Kepatuhan
1. **Audit Log Append-Only**: Tabel `activity_logs` dilindungi trigger level database (`BEFORE UPDATE OR DELETE`) yang menolak segala bentuk modifikasi atau manipulasi riwayat jejak audit secara langsung via SQL.
2. **Entitlement Lisensi & Mode Baca-Saja**: Jika masa lisensi berakhir, sistem otomatis beralih ke mode baca-saja (*read-only*), memblokir mutasi baru sementara tetap mengizinkan login, logout, pencarian, dan ekspor data secara penuh.
3. **RBAC Granular**: Setiap mutasi dilindungi perpaduan spatie permissions (`surat-masuk.registrasi`, `surat-masuk.batalkan`, dsb.) dan Laravel Policies.
