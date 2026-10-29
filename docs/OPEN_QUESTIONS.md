# Pertanyaan Terbuka & Asumsi Implementasi E-Office

Dokumen ini mencatat hal-hal yang ambigu, keputusan arsitektur turunan, dan asumsi teraman yang diambil dalam pengembangan E-Office berdasarkan PRD (`e-office.md`).

---

## 1. Daftar Catatan & Asumsi Teknis

| ID | Topik | Situasi / Ambiguitas | Asumsi Teraman yang Diambil |
|---|---|---|---|
| **OQ-01** | **Transisi Kode Blade/Livewire ke React+Inertia** | Repositori saat ini memiliki jejak prototipe berbasis Blade dan Livewire. Permintaan teknis saat ini mewajibkan React + Inertia + TypeScript. | Kita akan menginisialisasi stack React + Inertia + TypeScript secara bersih di `resources/js`, menginstal adapter Laravel Breeze/Inertia React, dan merefaktor route serta controller secara bertahap per domain. File blade lama akan digantikan. |
| **OQ-02** | **Dialek Database (PostgreSQL vs MySQL/SQLite)** | PRD merekomendasikan PostgreSQL, namun development lokal mungkin memakai MySQL atau SQLite. | Seluruh migration ditulis menggunakan *Schema Builder* standar Laravel yang agnostik basis data. Untuk fitur lanjutan seperti Full-Text Search, kita buat abstraksi service (GIN index untuk PostgreSQL dan FULLTEXT untuk MySQL). |
| **OQ-03** | **Sistem Lisensi (Klien vs Server Billing)** | Server billing langganan berada di luar lingkup aplikasi E-Office. Aplikasi hanya butuh titik temu lisensi. | Dibuat `LicenseService` berbasis interface dengan implementasi lokal/stub yang memvalidasi payload lisensi (JWT/HMAC hash). Mode default instalasi baru adalah paket "Standar" dengan lisensi aktif, disertai fitur input kunci lisensi manual atau verifikasi API mock. |
| **OQ-04** | **Penyedia TTE (Tanda Tangan Elektronik)** | Belum ada akun penyedia TTE eksternal (seperti BSrE, Privy, Peruri) yang dikontrak di awal. | Arsitektur penandatanganan menggunakan pola *Adapter Pattern* (`SignatureProviderInterface`). Di MVP/Fase awal, disediakan adapter: (1) `WetSignatureAdapter` (unggah pindaian tanda tangan basah), dan (2) `InternalQrVerifyAdapter` (stempel hash kriptografis lokal dengan QR token untuk verifikasi halaman publik). Adapter pihak ketiga dapat dipasang kapan saja tanpa merombak modul surat keluar. |
| **OQ-05** | **Perilaku Lisensi Kedaluwarsa** | Apakah saat kedaluwarsa pengguna boleh mencetak atau mengunduh arsip? | Ya, sesuai prinsip "Data Tidak Boleh Disandera" (PRD Bagian 8.9), saat kedaluwarsa sistem beralih ke mode **Baca-Saja (Read-Only) + Ekspor Penuh**. Seluruh request mutasi (POST/PUT/DELETE) ditolak oleh middleware, namun pencarian, pembacaan, dan unduh/ekspor berkas PDF/Excel tetap aktif 100%. |
| **OQ-06** | **Kamus Istilah Dinamis (Terminology)** | Berapa tingkat hierarki dan istilah yang perlu dikonfigurasi? | Disiapkan kamus JSON fleksibel di `institution_profiles.terminology_dict` yang mencakup: `unit_level_1` (mis. Jurusan/Bagian), `unit_level_2` (mis. Prodi/Seksi), `lead_level_1` (mis. Direktur/Rektor/Ketua), `lead_level_2` (mis. Kajur/Kabid), dan istilah persuratan lainnya. UI memanggil helper `useTerminology()` sehingga tidak ada teks jabatan yang di-hardcode. |
