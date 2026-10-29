# PRD — Sistem E-Office (Single-Tenant, Berlangganan)

| | |
|---|---|
| **Nama Produk** | E-Office (nama kerja; dapat diganti) |
| **Versi Dokumen** | 1.2 (Draft) — 1.1: single-tenant berlangganan; 1.2: Mode A ditetapkan dan paket harga 3 tingkat ditambahkan |
| **Tanggal** | 21 Oktober 2026 |
| **Dokumen Acuan** | SOP Pengelolaan Surat Masuk dan Surat Keluar Lingkungan Politeknik |
| **Stack** | Laravel + React JS + Inertia.js |
| **Model Bisnis** | Satu aplikasi, satu instalasi per instansi. Kode sumber dan dukungan dipegang vendor; pelanggan membayar langganan. Penyajian: dihosting vendor (Mode A). Tiga paket: Dasar, Standar (Rp750.000/bulan, paket jangkar), dan Lanjutan; harga masih hipotesis awal dan perlu divalidasi (Lampiran C) |

---

## 1. Ringkasan Produk

E-Office adalah aplikasi tata usaha digital untuk mengelola persuratan, disposisi, kearsipan, dan layanan administrasi internal. Produk dibuat pertama untuk **Politeknik**, lalu dijual ke kampus lain dan instansi lain dengan **satu basis kode** yang dapat dikonfigurasi lewat *profil instansi*.

Setiap pelanggan mendapat **satu instalasi sendiri (single-tenant)**: aplikasi, database, dan berkas terpisah dari pelanggan lain. Tidak ada data dua instansi dalam satu database.

**Modul inti:** Surat Masuk, Disposisi, Surat Keluar (alur persetujuan, penomoran, tanda tangan), Kearsipan.
**Modul pendukung (fase lanjut):** Layanan permohonan, Surat tugas/SPPD, Agenda dan rapat, Kerja sama (MoU/PKS), Workflow builder.

> **Catatan lingkup:** sistem langganan (penagihan, pembayaran, pengelolaan pelanggan) **dibuat terpisah** dan berada di luar PRD ini. PRD ini hanya mendefinisikan titik temunya: mekanisme lisensi di dalam aplikasi E-Office (Bagian 8.9).

## 2. Latar Belakang dan Masalah

- Surat fisik berpindah antar-meja tanpa jejak, sehingga posisi surat sulit dilacak.
- Disposisi manual lambat dan mudah hilang; tenggat tindak lanjut tidak terpantau.
- Penomoran surat manual rawan ganda atau salah kode klasifikasi.
- Pengarsipan tidak terstruktur; pencarian surat lama memakan waktu.
- Pimpinan sering di luar kantor sehingga persetujuan dan tanda tangan tertunda.
- Tiap instansi punya struktur, istilah jabatan, kode klasifikasi, dan format nomor sendiri, sehingga aplikasi "kaku" sulit dijual ke banyak pihak.

## 3. Tujuan dan Metrik Keberhasilan

### 3.1 Tujuan
1. Mendigitalkan alur surat masuk dan keluar sesuai SOP, dengan jejak audit lengkap.
2. Mempercepat siklus disposisi dan persetujuan surat.
3. Menyediakan produk yang dapat disesuaikan per instansi **tanpa perubahan kode**.
4. Membuat pemasangan, pembaruan, dan dukungan per pelanggan **murah dan terotomasi**, karena margin langganan tipis (lihat Lampiran C).
5. Memenuhi kebutuhan kepatuhan dasar (TTE, kearsipan, pelindungan data).

### 3.2 Metrik (target awal, divalidasi saat pilot)
| Metrik | Target |
|---|---|
| Waktu rata-rata registrasi surat masuk sampai disposisi | turun ≥ 50% dibanding manual |
| Waktu rata-rata surat keluar dari draf sampai ditandatangani | turun ≥ 40% |
| Surat dengan nomor ganda/salah | 0 |
| Surat yang dapat dilacak posisinya | 100% |
| Adopsi pengguna aktif pada instansi pilot (bulan ke-3) | ≥ 80% pegawai yang ditarget |
| Waktu pemasangan instansi baru sampai siap pakai | ≤ 1 hari kerja |
| Waktu rata-rata pembaruan versi per instalasi | ≤ 15 menit, tanpa hilang data |
| Jam dukungan rata-rata per pelanggan per bulan | ≤ target yang ditetapkan di Lampiran C |

## 4. Segmen Pasar dan Target Pengguna

### 4.1 Segmen
| Segmen | Status | Catatan |
|---|---|---|
| Politeknik dan kampus lain (terutama swasta) | **Prioritas 1** | Titik awal; SOP acuan dari sini |
| BUMN/BUMD, swasta, yayasan, rumah sakit, sekolah | **Prioritas 2** | Perlu preset istilah dan alur approval berbeda |
| Kementerian, lembaga, pemerintah daerah | **Perlu kajian sebelum dijadikan target** | Pemerintah telah menetapkan aplikasi umum nasional di bidang kearsipan dinamis (SRIKANDI, Kepmen PANRB No. 679 Tahun 2020), dengan kewajiban penggunaan bagi instansi pusat dan pemda; aplikasi sejenis hanya boleh dengan syarat tertentu. Status kewajiban untuk kampus negeri dan BUMN perlu dikonfirmasi ke ANRI/Kementerian PANRB atau calon pelanggan |

### 4.2 Persona
| Persona | Kebutuhan Utama |
|---|---|
| **Admin Tata Usaha (TU)** | Registrasi surat, penomoran, distribusi, pengiriman, pelaporan |
| **Pimpinan (Direktur/Wadir/Kajur atau padanannya)** | Membaca dan mendisposisikan surat, menyetujui dan menandatangani, dari HP |
| **Kepala Unit/Kaprodi** | Menerima disposisi, memeriksa draf, menindaklanjuti |
| **Staf/Pelaksana** | Menyusun draf surat, melaksanakan tindak lanjut |
| **Petugas Arsip** | Mengelola arsip aktif/inaktif, retensi, peminjaman |
| **Admin Instansi** | Mengatur organisasi, jabatan, hak akses, template, klasifikasi, lisensi |
| **Tim Support Vendor** | Memantau kesehatan sistem dan membantu pelanggan lewat akses terbatas yang disetujui admin instansi |

## 5. Ruang Lingkup

### 5.1 Dalam Lingkup
- Persuratan (masuk, keluar), disposisi, pelacakan, pencarian, laporan.
- Kearsipan digital dan retensi.
- Profil instansi dan konfigurasi (organisasi, istilah, template, nomor, klasifikasi).
- Wizard pemasangan awal, mekanisme lisensi, pembaruan versi, backup/restore.
- Integrasi dasar: email, notifikasi, TTE, SSO.
- Aplikasi web responsif (mobile-friendly).

### 5.2 Di Luar Lingkup (v1)
- **Sistem langganan dan penagihan** (dibuat terpisah).
- Multi-tenant dalam satu instalasi.
- Aplikasi native iOS/Android (memakai web responsif/PWA dulu).
- Pembuatan TTE/sertifikat sendiri (memakai penyedia TTE pihak ketiga).
- Modul keuangan, kepegawaian penuh, akademik (hanya integrasi).
- Integrasi dengan SRIKANDI (dikaji bila segmen pemerintah dibuka).

## 6. Peran dan Hak Akses

Hak akses berbasis **permission**, sehingga tiap instansi dapat membuat peran sendiri. Peran bawaan mengikuti *profil instansi* yang dipilih saat setup (contoh di bawah: profil Politeknik).

| Peran | Contoh Permission Utama |
|---|---|
| Admin Instansi | kelola pengguna, struktur organisasi, template, klasifikasi, pengaturan, lisensi |
| Staf TU | registrasi surat masuk, penomoran, distribusi, pengiriman, laporan |
| Pimpinan | disposisi, setujui, tandatangani, lihat dashboard |
| Kepala Unit | periksa/paraf draf, terima dan teruskan disposisi |
| Staf/Pelaksana | buat draf, tindak lanjut disposisi |
| Petugas Arsip | kelola arsip, retensi, peminjaman |
| Akses Support Vendor | hanya aktif sementara dan atas persetujuan Admin Instansi; terekam di audit log |

Aturan tambahan: surat berklasifikasi **Rahasia** hanya dapat dilihat oleh pembuat, penerima disposisi, dan pihak yang diberi akses eksplisit. **Tim support vendor tidak dapat membaca isi surat** kecuali admin instansi memberi akses terbatas waktu.

## 7. Alur Bisnis

### 7.1 Surat Masuk (mengacu SOP bagian 2)

```
Penerimaan → Registrasi → Disposisi → Distribusi → Tindak Lanjut → Pengarsipan
```

| Tahap | Pelaksana | Perilaku Sistem |
|---|---|---|
| Penerimaan | Front Office/Admin | Input cepat surat fisik/email; bukti terima dapat dicetak |
| Registrasi | TU | Nomor agenda otomatis, upload scan, metadata, OCR, cetak lembar disposisi (QR) |
| Disposisi | Pimpinan | Disposisi digital berjenjang, instruksi cepat, tenggat |
| Distribusi | TU/Staf Agenda | Notifikasi otomatis ke penerima; surat fisik dicatat serah terimanya |
| Tindak Lanjut | Unit Pelaksana | Update status, lampiran bukti, balasan (terhubung ke Surat Keluar) |
| Pengarsipan | Arsip/Unit | Arsip otomatis ke kategori dan retensi sesuai klasifikasi |

**Status surat masuk:** `Draft Registrasi → Terdaftar → Menunggu Disposisi → Didisposisi → Diproses → Selesai → Diarsipkan` (+ `Dibatalkan`).

### 7.2 Surat Keluar (mengacu SOP bagian 3, ditambah alur revisi)

```
Draf → Pemeriksaan (paraf) → [Revisi?] → Penomoran → Tanda Tangan → Cap → Pengiriman → Arsip
```

| Tahap | Pelaksana | Perilaku Sistem |
|---|---|---|
| Draf | Unit pengusul | Pilih template resmi, isi variabel, lampiran, daftar tujuan dan tembusan |
| Pemeriksaan | Kepala Unit/Kajur | Setujui/paraf, **minta revisi** (dengan catatan), atau tolak; dapat berjenjang |
| Penomoran | TU Pusat | Nomor otomatis berdasarkan format dan kode klasifikasi; dikunci setelah terbit |
| Tanda tangan | Pejabat berwenang | TTE tersertifikasi atau tanda tangan basah (hybrid); QR verifikasi di dokumen |
| Cap | TU | Stempel fisik dicatat, atau stempel elektronik (opsional) |
| Pengiriman | Admin/Kurir | Catat kanal (email/kurir/pos/integrasi), nomor resi, bukti terima |
| Arsip | Unit & TU | Salinan/tembusan diarsipkan otomatis |

**Status surat keluar:** `Draf → Diajukan → Dalam Pemeriksaan → Perlu Revisi → Disetujui → Bernomor → Menunggu TTD → Ditandatangani → Terkirim → Diarsipkan` (+ `Ditolak`, `Dibatalkan`).

### 7.3 Hubungan Antar Surat
Surat keluar dapat dibuat sebagai **balasan** dari surat masuk; sistem menautkan keduanya sehingga tindak lanjut disposisi otomatis dapat ditutup ketika balasan terkirim.

### 7.4 Pemasangan Awal Instansi Baru (Setup)
```
Instalasi → Wizard setup → Pilih profil instansi → Data instansi & kop → Admin pertama → Import struktur & pegawai → Format nomor & klasifikasi → Uji → Go-live
```
Seluruh langkah dilakukan lewat wizard di aplikasi; tidak memerlukan perubahan kode atau penyuntingan berkas konfigurasi manual.

## 8. Kebutuhan Fungsional

Prioritas: **M** = Must (MVP), **S** = Should, **C** = Could.

### 8.1 Autentikasi dan Pengguna
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-AUTH-01 | Login email/username + password, reset password via email | M |
| FR-AUTH-02 | Manajemen pengguna oleh Admin Instansi (CRUD, aktif/nonaktif, import Excel) | M |
| FR-AUTH-03 | 2FA (TOTP), wajib opsional untuk penandatangan | S |
| FR-AUTH-04 | SSO (Google, Microsoft, LDAP) | C |
| FR-AUTH-05 | Manajemen sesi dan pembatasan percobaan login | M |

### 8.2 Organisasi dan Hak Akses
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-ORG-01 | Struktur organisasi hierarkis (instansi → jurusan/bagian → prodi/unit), CRUD | M |
| FR-ORG-02 | Jabatan dan penugasan pengguna ke unit/jabatan (satu pengguna bisa multi-jabatan) | M |
| FR-ORG-03 | Role dan permission dapat dikonfigurasi admin instansi | M |
| FR-ORG-04 | Delegasi dan Plt saat pejabat cuti/dinas luar (rentang tanggal) | S |

### 8.3 Profil Instansi dan Konfigurasi
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-CFG-01 | **Profil instansi (preset)**: paket bawaan istilah jabatan, jenis surat, alur persetujuan, peran, dan klasifikasi; awal: profil Politeknik/Kampus, lalu profil lain | M |
| FR-CFG-02 | Istilah/label dapat diganti (mis. "Kajur" menjadi "Kabid") tanpa kode | S |
| FR-CFG-03 | Format nomor surat berbasis token (Lampiran A) per jenis surat | M |
| FR-CFG-04 | Kode klasifikasi arsip dan klasifikasi keamanan dapat diimpor/disesuaikan | M |
| FR-CFG-05 | Sifat dan jenis surat dapat ditambah/diubah (Lampiran B) | M |
| FR-CFG-06 | Branding: logo, warna, kop surat, nama instansi | M |
| FR-CFG-07 | Zona waktu, bahasa, dan format tanggal | S |

### 8.4 Surat Masuk
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-IN-01 | Registrasi surat (fisik/email): pengirim, nomor surat asal, tanggal, perihal, sifat, jenis, lampiran | M |
| FR-IN-02 | Nomor agenda otomatis per tahun/unit | M |
| FR-IN-03 | Upload scan (PDF/gambar), pratinjau dalam aplikasi | M |
| FR-IN-04 | OCR agar isi surat dapat dicari | S |
| FR-IN-05 | Cetak lembar disposisi dan bukti terima (dengan QR) | M |
| FR-IN-06 | Pemindaian QR untuk menemukan surat fisik di sistem | C |
| FR-IN-07 | Penerimaan surat dari email resmi (inbound email ke tiket surat) | C |
| FR-IN-08 | Pembatalan/koreksi registrasi dengan alasan dan jejak audit | M |

### 8.5 Disposisi
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-DSP-01 | Pimpinan membuat disposisi: penerima (satu/banyak), instruksi, catatan, tenggat, prioritas | M |
| FR-DSP-02 | Template instruksi cepat yang dapat dikonfigurasi | M |
| FR-DSP-03 | Disposisi berjenjang (penerima dapat meneruskan ke bawahannya) | M |
| FR-DSP-04 | Penerima memperbarui status tindak lanjut dan melampirkan bukti | M |
| FR-DSP-05 | Pengingat otomatis mendekati tenggat dan eskalasi bila terlambat | S |
| FR-DSP-06 | Catatan suara pada disposisi | C |

### 8.6 Surat Keluar
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-OUT-01 | Template surat per kategori (biasa, tugas, SK, nota dinas, undangan, keterangan) dengan kop dan variabel | M |
| FR-OUT-02 | Editor draf (rich text) atau upload dokumen jadi; lampiran | M |
| FR-OUT-03 | Alur persetujuan berjenjang yang dikonfigurasi per jenis surat | M |
| FR-OUT-04 | Aksi pemeriksa: setujui, paraf, minta revisi (dengan catatan), tolak; riwayat versi draf | M |
| FR-OUT-05 | Penomoran otomatis sesuai format dan kode klasifikasi; anti-duplikasi; dikunci setelah terbit | M |
| FR-OUT-06 | Tanda tangan: basah (unggah hasil pindai/cetak) dan TTE via integrasi penyedia | S (basah: M) |
| FR-OUT-07 | QR verifikasi keaslian di dokumen dan halaman verifikasi publik | S |
| FR-OUT-08 | Tembusan otomatis ke unit/pejabat terkait | M |
| FR-OUT-09 | Pencatatan pengiriman (kanal, resi, bukti terima); kirim via email resmi dari sistem | M |
| FR-OUT-10 | Surat keluar sebagai balasan dari surat masuk (penautan) | M |

### 8.7 Pelacakan, Pencarian, Laporan
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-TRK-01 | Riwayat/timeline setiap surat (siapa, kapan, aksi apa) | M |
| FR-TRK-02 | Pencarian dan filter (nomor, perihal, pengirim, tanggal, status, sifat, unit) | M |
| FR-TRK-03 | Pencarian full-text termasuk isi hasil OCR | S |
| FR-TRK-04 | Buku agenda surat masuk/keluar per periode; ekspor PDF/Excel | M |
| FR-TRK-05 | Dashboard (surat baru, disposisi tertunda, waktu proses per unit) | S |

### 8.8 Kearsipan dan Notifikasi
| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-ARC-01 | Kode klasifikasi arsip (import/konfigurasi) | M |
| FR-ARC-02 | Arsip otomatis dari surat selesai; arsip manual untuk dokumen lain | M |
| FR-ARC-03 | Jadwal Retensi Arsip (JRA): status aktif/inaktif, tanggal retensi | S |
| FR-ARC-04 | Alur penyusutan dan pemusnahan dengan berita acara | C |
| FR-ARC-05 | Peminjaman arsip fisik | C |
| FR-NTF-01 | Notifikasi dalam aplikasi (real-time/polling) | M |
| FR-NTF-02 | Notifikasi email | M |
| FR-NTF-03 | Notifikasi WhatsApp via gateway (biaya gateway ditanggung pelanggan atau add-on) | S |
| FR-NTF-04 | Preferensi notifikasi per pengguna | C |

### 8.9 Lisensi, Pemasangan, dan Operasional (pengganti modul multi-tenant)

Bagian ini penentu apakah model langganan single-tenant bisa dijalankan dengan biaya dukungan yang wajar.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-LIC-01 | **Kunci lisensi** per instalasi: berisi identitas instansi, masa berlaku, batas pengguna/penyimpanan, dan modul yang aktif (*entitlement*) | M |
| FR-LIC-02 | Verifikasi lisensi berkala secara online ke sistem langganan (terpisah) lewat API sederhana; jadwal, toleransi gagal koneksi, dan masa tenggang dapat diatur | M |
| FR-LIC-03 | **Berkas lisensi offline** untuk instalasi tanpa akses internet keluar | S |
| FR-LIC-04 | **Perilaku saat lisensi kedaluwarsa:** setelah masa tenggang aplikasi masuk mode **baca-saja + ekspor data**; data tidak dihapus dan tidak ditahan. Peringatan bertahap ke Admin Instansi sebelum kedaluwarsa | M |
| FR-LIC-05 | Halaman "Lisensi & Sistem" untuk Admin Instansi: status lisensi, versi, kuota terpakai, status kesehatan | M |
| FR-LIC-06 | **Pembatasan fitur per paket** lewat entitlement (OCR, TTE, WhatsApp, SSO, API, integrasi SIAKAD/SIMPEG, modul tambahan aktif sesuai paket di Lampiran C), tanpa kode terpisah; fitur yang tidak termasuk paket tampil terkunci dengan keterangan cara meningkatkan paket | S |
| FR-LIC-07 | **Kuota per paket** (pengguna aktif, penyimpanan): peringatan ke Admin Instansi pada 80% dan 100%; setelah melewati masa tenggang, hanya penambahan pengguna dan unggahan baru yang dibatasi, **akses ke data yang ada tidak pernah dikunci** | M |
| FR-LIC-08 | Pindah paket (naik/turun) tanpa instalasi ulang dan tanpa kehilangan data; perubahan berlaku lewat pembaruan entitlement dari sistem langganan | S |
| FR-OPS-01 | **Wizard setup awal** (Bagian 7.4) | M |
| FR-OPS-02 | **Pembaruan versi** satu perintah/satu klik: unduh image berversi, migrasi database otomatis, pemeriksaan sebelum-sesudah, **rollback** bila gagal | M |
| FR-OPS-03 | **Backup otomatis** (database + berkas) terjadwal, retensi dapat diatur, dan **restore** teruji | M |
| FR-OPS-04 | Endpoint *health check* dan metrik dasar (antrian, disk, error) yang dapat dipantau vendor | M |
| FR-OPS-05 | **Telemetri minimal, transparan, dan sesuai kontrak**: hanya versi, status lisensi, jumlah pengguna aktif, kesehatan sistem. **Tidak pernah mengirim isi surat, lampiran, atau data pribadi** | M |
| FR-OPS-06 | **Akses support terbatas waktu:** Admin Instansi mengaktifkan akses (token sementara), seluruh aktivitas support tercatat di audit log, akses otomatis berakhir | S |
| FR-OPS-07 | **Ekspor data penuh** (surat, lampiran, metadata, log) dalam format terbuka kapan saja oleh Admin Instansi | M |
| FR-OPS-08 | Log aplikasi dan *error reporting* yang dapat dikirim ke vendor atas izin pelanggan untuk mempercepat dukungan | S |
| FR-OPS-09 | Dokumentasi pemasangan, persyaratan sistem minimum, dan panduan operasional | M |

### 8.10 Modul Fase Lanjut (ringkas)
Layanan permohonan (surat keterangan, izin), surat tugas dan SPPD, agenda/rapat/notulen, kerja sama MoU/PKS dengan pengingat masa berlaku, workflow builder generik, integrasi SIAKAD/SIMPEG, REST API publik. Modul-modul ini dapat dijual sebagai *add-on* melalui entitlement lisensi.

## 9. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| **Kinerja** | Halaman daftar < 2 detik pada 100k surat; pencarian < 3 detik; upload hingga 20 MB/berkas; berjalan baik pada server spesifikasi sedang |
| **Efisiensi sumber daya** | Footprint per instalasi dijaga kecil (mengingat satu instalasi per pelanggan); target spesifikasi minimum dipublikasikan |
| **Ketersediaan** | Target 99,5% untuk instalasi yang dihosting vendor; backup harian, retensi minimal 30 hari, uji restore berkala |
| **Skalabilitas** | Antrian (queue) untuk OCR, email, notifikasi, pembuatan PDF; penyimpanan berkas di object storage atau disk terkelola |
| **Keamanan** | HTTPS, enkripsi at-rest untuk berkas sensitif, hash password modern, proteksi CSRF/XSS/SQLi, rate limiting, validasi tipe berkas dan pemindaian malware |
| **Audit** | Log aktivitas tidak dapat diubah (append-only) untuk aksi penting, termasuk aktivitas support |
| **Privasi** | Sesuai UU Pelindungan Data Pribadi: minimisasi data, hak akses, retensi; vendor sebagai pihak yang diatur kontrak (perjanjian pemrosesan data) |
| **Kegunaan** | Responsif (HP/tablet/desktop), bahasa Indonesia, aksesibilitas dasar |
| **Lokalisasi** | Zona waktu (WIB/WITA/WIT), format tanggal Indonesia; i18n disiapkan |
| **Kemudahan pembaruan** | Versi semantik, catatan rilis, kompatibilitas mundur migrasi, dukungan lompat versi dalam batas yang ditentukan |
| **Observabilitas** | Log terpusat opsional, pelacakan error, health check, metrik antrian |

## 10. Arsitektur Teknis

### 10.1 Stack
| Lapisan | Pilihan |
|---|---|
| Backend | Laravel (versi stabil terbaru saat proyek dimulai) |
| Frontend | React + Inertia.js, Vite |
| UI | Tailwind CSS + pustaka komponen (mis. shadcn/ui atau Radix) |
| Basis data | PostgreSQL (disarankan) atau MySQL/MariaDB |
| Cache/Queue/Session | Redis; Laravel Queue (Horizon bila diperlukan) |
| Penyimpanan berkas | Disk terkelola atau S3-compatible (MinIO) |
| Pencarian | Database full-text pada MVP; Meilisearch bila perlu |
| PDF | Pembuat PDF dari template (dompdf/browsershot/Gotenberg) |
| OCR | Tesseract (self-hosted) via queue, atau layanan eksternal |
| Pengujian | Pest/PHPUnit, Vitest/Testing Library, Playwright untuk E2E |
| CI/CD | Pipeline yang membangun **image Docker berversi** dan menerbitkan ke registry privat |

Paket yang umum dipakai (dievaluasi saat implementasi): `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-medialibrary`, `laravel/sanctum`, `maatwebsite/excel`. **Paket multi-tenancy tidak diperlukan.**

### 10.2 Model Single-Tenant
- Satu instalasi = satu instansi = satu database. **Tidak ada kolom `tenant_id`** dan tidak ada lapisan scope tenant; ini menyederhanakan kode dan menghilangkan risiko kebocoran antar-pelanggan.
- Struktur multi-unit (jurusan, prodi, bagian) tetap ada **di dalam** satu instansi lewat `org_units`.
- Konsekuensi yang disadari: bila kelak dibutuhkan satu platform melayani banyak instansi (SaaS multi-tenant), diperlukan pekerjaan tambahan yang signifikan. Keputusan ini diambil sadar untuk kesederhanaan dan isolasi data.

### 10.3 Arsitektur Penyajian (Deployment)
**Keputusan: penyajian awal memakai Mode A.** Mode B tetap kompatibel (paket Docker yang sama) tetapi baru ditawarkan bila ada pelanggan yang benar-benar mensyaratkannya. Satu paket yang sama dapat disajikan dengan dua cara:

| Mode | Deskripsi | Cocok untuk |
|---|---|---|
| **A. Dihosting vendor (DITETAPKAN untuk awal)** | Vendor menjalankan satu instance (container dan database terpisah) per pelanggan di server vendor, mis. `namainstansi.domain-vendor.com` atau domain pelanggan | Mayoritas pelanggan; dukungan dan pembaruan paling mudah |
| **B. On-premise di server pelanggan (ditunda)** | Paket Docker Compose dipasang di server pelanggan; kode sumber **tidak diserahkan** (image terdistribusi, dengan perlindungan kode dan lisensi) | Instansi yang mensyaratkan data di server sendiri |

Komponen paket (Docker Compose): `app` (PHP-FPM/Octane), `web` (reverse proxy), `queue worker`, `scheduler`, `db`, `redis`, opsional `minio`, opsional `ocr`. Konfigurasi lewat `.env` dan tabel pengaturan; satu perintah untuk start, update, backup.

Catatan perlindungan kode untuk Mode B: aplikasi PHP pada dasarnya berbasis source. Opsi mitigasi (kontrak lisensi, *license check*, image privat, obfuscation/encoder seperti ionCube) perlu dievaluasi biaya dan kompatibilitasnya dengan Laravel dan Docker. Tidak ada mitigasi yang sepenuhnya mencegah pembajakan; kontrak dan hubungan komersial tetap menjadi pelindung utama. Karena Mode A ditetapkan, evaluasi perlindungan kode untuk Mode B ditunda sampai ada permintaan nyata dari pelanggan.

### 10.4 Struktur Aplikasi (modular)
```
app/
  Domain/
    Letters/        (surat masuk, keluar, disposisi)
    Archive/
    Organization/
    Licensing/      (kunci lisensi, entitlement, mode baca-saja)
    Operations/     (health, backup, update, support access)
    Notifications/
  Http/Controllers/ (tipis; memanggil Action/Service)
resources/js/
  Pages/            (halaman Inertia per modul)
  Components/
  Layouts/
```
- Logika bisnis di **Action/Service class**, bukan di controller.
- **State machine** untuk status surat agar transisi terkontrol dan tercatat.
- **Policy** Laravel untuk otorisasi per objek, ditambah permission per peran.
- Event dan listener untuk notifikasi, audit, dan penautan antar-modul.
- Lapisan **Licensing** diperiksa melalui middleware/guard sentral, bukan tersebar di tiap modul.

### 10.5 Integrasi
| Integrasi | Tujuan | Fase |
|---|---|---|
| SMTP/email transaksional | Notifikasi, pengiriman surat | MVP |
| **Sistem langganan vendor (terpisah)** | Verifikasi lisensi, status langganan, entitlement | MVP |
| Penyedia TTE tersertifikasi | Tanda tangan elektronik | Fase 2 |
| WhatsApp gateway | Notifikasi | Fase 2 |
| SSO (OAuth/SAML/LDAP) | Login terpusat | Fase 3 |
| SIAKAD/SIMPEG | Sinkronisasi pegawai/mahasiswa | Fase 3 |
| REST API publik (token) | Integrasi pihak ketiga | Fase 4 |

**Kontrak API lisensi (antarmuka ke sistem langganan terpisah):** `POST /license/verify` (kirim ID instalasi + versi; terima status, masa berlaku, batas, entitlement, tanda tangan digital). Respons ditandatangani agar tidak mudah dipalsukan. Detail rancangan di dokumen tersendiri.

## 11. Model Data (entitas utama)

> Ringkas; ERD rinci disusun pada fase desain.

| Entitas | Atribut Kunci | Relasi |
|---|---|---|
| `institution_profile` | nama, alamat, logo, kop, zona waktu, profil terpilih | satu baris per instalasi |
| `settings` | kunci, nilai (JSON) | konfigurasi umum |
| `license_state` | kunci lisensi, status, masa berlaku, batas, entitlement, terakhir diverifikasi | satu baris |
| `users` | nama, email, password, status, 2FA | banyak ke banyak `positions` |
| `org_units` | nama, kode, tipe, parent_id | hierarki; punya `positions` |
| `positions` | nama jabatan, org_unit_id, level, is_penandatangan | dipegang `users` (dengan rentang waktu, Plt) |
| `roles`, `permissions` | nama | banyak ke banyak dengan `users` |
| `classification_codes` | kode, nama, parent_id, retensi_aktif, retensi_inaktif, nasib_akhir | dipakai surat dan arsip |
| `incoming_letters` | nomor_agenda, nomor_asal, pengirim, tanggal_surat, tanggal_terima, perihal, sifat, jenis, classification_id, status, confidentiality | punya `attachments`, `dispositions` |
| `dispositions` | letter_id, dari, parent_disposition_id, instruksi, catatan, tenggat, prioritas | punya `disposition_recipients` |
| `disposition_recipients` | disposition_id, penerima, status, tanggal_baca, tanggal_selesai, catatan | |
| `outgoing_letters` | nomor_surat, perihal, template_id, konten, status, classification_id, pembuat, penandatangan, reply_to_incoming_id | punya `approvals`, `recipients`, `versions`, `dispatches` |
| `letter_templates` | nama, kategori, konten, variabel | |
| `approval_flows`, `approval_steps` | jenis surat, urutan, jabatan/peran, tipe aksi | |
| `approvals` | letter_id, step, pelaku, aksi, catatan, waktu | |
| `numbering_sequences` | unit, tahun, format, counter | dikunci transaksi |
| `signatures` | letter_id, tipe (basah/TTE), penyedia, sertifikat, waktu, hash dokumen | |
| `dispatches` | letter_id, kanal, resi, tanggal, bukti | |
| `archives` | sumber (surat/dokumen), classification_id, lokasi fisik, status retensi, tanggal | |
| `attachments` | polimorfik, path, mime, ukuran, checksum, ocr_text | |
| `notifications` | penerima, tipe, payload, dibaca | |
| `support_sessions` | dibuka_oleh, mulai, berakhir, ruang lingkup, status | audit akses support |
| `activity_logs` | pelaku, aksi, subjek, perubahan, IP, waktu | append-only |

Catatan penting:
- Penomoran memakai **transaksi dan penguncian baris** pada `numbering_sequences` untuk mencegah nomor ganda.
- Gunakan **soft delete** dan jejak audit; surat bernomor tidak boleh dihapus, hanya dibatalkan.
- Tidak ada tabel `tenants` dan tidak ada `tenant_id`.

## 12. Struktur Halaman (Inertia)

| Area | Halaman |
|---|---|
| Setup | Wizard instalasi awal (hanya sebelum setup selesai) |
| Umum | Login, lupa password, dashboard, notifikasi, profil dan keamanan |
| Surat Masuk | Daftar, registrasi baru, detail (timeline, lampiran, disposisi), cetak lembar disposisi |
| Disposisi | Kotak masuk disposisi, detail dan aksi, disposisi saya/yang saya buat |
| Surat Keluar | Daftar, draf baru (pilih template), detail (versi, persetujuan), antrean persetujuan, antrean TTD, pengiriman |
| Pencarian | Pencarian lanjutan |
| Arsip | Daftar arsip, retensi, peminjaman |
| Laporan | Buku agenda, rekap per periode/unit, ekspor |
| Pengaturan Instansi | Profil instansi, pengguna, organisasi, jabatan, role/permission, template, kop surat, klasifikasi, format nomor, alur persetujuan, branding, integrasi |
| **Lisensi & Sistem** | Status lisensi, versi, kuota, kesehatan, backup, akses support, ekspor data |
| Publik | Verifikasi keaslian surat (QR) |

**Prinsip UI:** mobile-first untuk aksi pimpinan (baca, disposisi, setujui, tanda tangan dalam ≤ 3 ketuk); tabel dengan filter tersimpan; indikator tenggat berwarna; konfirmasi untuk aksi tidak dapat dibatalkan.

## 13. Keamanan dan Kepatuhan

- Otorisasi berlapis: permission dan policy per objek.
- Surat rahasia: pembatasan akses dan pencatatan setiap pembukaan.
- Berkas disajikan lewat tautan bertanda tangan berwaktu terbatas (signed URL), bukan path publik.
- Validasi dan pembatasan jenis/ukuran berkas; pemindaian malware.
- Audit log append-only; ekspor log untuk auditor.
- **Batas akses vendor:** tim support tidak punya akses default ke data pelanggan; akses sementara hanya atas persetujuan Admin Instansi dan terekam. Telemetri tidak berisi isi dokumen.
- Kepatuhan yang perlu dipetakan saat desain (verifikasi ke regulasi terbaru): UU ITE dan ketentuan tanda tangan elektronik, UU Kearsipan dan pedoman JRA, UU Pelindungan Data Pribadi (termasuk peran vendor sebagai pemroses data dan perjanjiannya), pedoman tata naskah dinas, serta persyaratan hosting/pengadaan bila segmen pemerintah dibuka.
- Pengujian keamanan: SAST/dependency scanning di CI dan uji penetrasi sebelum rilis komersial.
- Kebijakan pembaruan keamanan: patch keamanan kritis didistribusikan dan diterapkan dalam waktu yang ditetapkan di kontrak layanan.

## 14. Peta Jalan dan Prioritas

| Fase | Cakupan | Keluaran |
|---|---|---|
| **0. Discovery & Desain** | Validasi SOP dengan 1–2 pengguna Politeknik, ERD, desain UI, keputusan hosting vs on-premise, desain API lisensi, validasi harga | PRD final, ERD, prototipe, rancangan lisensi |
| **1. MVP** | Autentikasi, organisasi, RBAC, profil instansi dasar, surat masuk, disposisi, surat keluar dengan alur persetujuan dan penomoran, tracking, pencarian, laporan dasar, arsip dasar, notifikasi email, **wizard setup, lisensi dasar dengan kuota per paket, paket Docker, update/rollback, backup/restore** | Pilot di 1 Politeknik, siap pelanggan berbayar pertama |
| **2. Penguatan** | TTE + QR verifikasi, template lanjutan, OCR, WhatsApp, dashboard, PWA/mobile polish, delegasi/Plt, akses support terbatas, berkas lisensi offline | Rilis komersial |
| **3. Perluasan** | Profil instansi tambahan (BUMN, swasta, RS, dsb.), layanan permohonan, surat tugas/SPPD, rapat, MoU/PKS, SSO, integrasi SIAKAD/SIMPEG, JRA lengkap | Siap multi-segmen |
| **4. Platform** | Workflow builder, REST API publik, add-on lewat entitlement, analitik; kajian segmen pemerintah (SRIKANDI) | Skala bisnis |

**Catatan:** mekanisme lisensi, pembaruan, dan backup **harus masuk MVP**, karena tanpa itu pelanggan berbayar pertama tidak dapat dilayani secara berkelanjutan.

## 15. Kriteria Penerimaan (contoh representatif)

**FR-OUT-05 Penomoran otomatis**
- *Given* surat berstatus Disetujui dan format nomor `{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}`,
- *When* TU menerbitkan nomor,
- *Then* nomor unik terbentuk sesuai format; dua penerbitan simultan tidak menghasilkan nomor sama; nomor tidak dapat diubah setelah terbit; tindakan tercatat di audit log.

**FR-DSP-01 Disposisi**
- *Given* surat berstatus Menunggu Disposisi,
- *When* pimpinan memilih 3 penerima, instruksi, dan tenggat lalu menyimpan,
- *Then* status menjadi Didisposisi; tiap penerima mendapat notifikasi; timeline surat mencatat disposisi; lembar disposisi dapat dicetak.

**FR-OUT-04 Revisi**
- *Given* surat dalam pemeriksaan,
- *When* pemeriksa memilih Minta Revisi dengan catatan,
- *Then* surat kembali ke pembuat berstatus Perlu Revisi; versi sebelumnya tersimpan; setelah diajukan ulang, alur dimulai dari tahap yang dikonfigurasi.

**FR-LIC-04 Lisensi kedaluwarsa**
- *Given* lisensi telah melewati masa berlaku dan masa tenggang,
- *When* pengguna membuka aplikasi,
- *Then* aplikasi berjalan **baca-saja**; pengguna tetap dapat melihat, mencari, dan **mengekspor** seluruh data; tidak ada data yang dihapus; Admin Instansi melihat pesan yang jelas tentang status dan cara memperpanjang; setelah lisensi diperbarui, fungsi penuh pulih tanpa instalasi ulang.

**FR-OPS-02 Pembaruan dan rollback**
- *Given* instalasi berjalan pada versi N dengan data aktual,
- *When* admin/vendor menjalankan pembaruan ke versi N+1 dan migrasi gagal,
- *Then* sistem otomatis kembali ke versi N dengan data utuh; kegagalan tercatat dan dilaporkan; pembaruan yang berhasil tidak menghilangkan data atau konfigurasi.

**FR-OPS-05 Telemetri**
- *Given* telemetri aktif,
- *When* payload yang dikirim diperiksa,
- *Then* hanya memuat versi, status lisensi, jumlah pengguna aktif, dan indikator kesehatan; tidak ada isi surat, nama berkas, atau data pribadi pengguna.

## 16. Risiko, Asumsi, dan Pertanyaan Terbuka

### Risiko
| Risiko | Mitigasi |
|---|---|
| **Margin tipis** pada harga langganan rendah (beban hosting dan dukungan per pelanggan) | Otomasi pemasangan/update/backup, batasan dukungan di paket, biaya setup dan add-on terpisah, opsi bayar tahunan (Lampiran C) |
| Beban dukungan membengkak seiring jumlah pelanggan | Dokumentasi, health check, telemetri minimal, wizard, batas lingkup dukungan di kontrak |
| Pembaruan terfragmentasi (pelanggan menunda update) | Pembaruan satu perintah, kebijakan versi yang didukung, patch keamanan wajib |
| Pembajakan/penyebaran kode (khususnya Mode B on-premise) | Kontrak, lisensi, perlindungan kode, memprioritaskan Mode A |
| Segmen pemerintah terbentur kewajiban SRIKANDI | Prioritaskan kampus dan non-pemerintah; kaji aturan dan jalur integrasi sebelum menawarkan ke pemerintah |
| Ketergantungan penyedia TTE | Abstraksi penyedia (adapter), dukung tanda tangan basah sebagai cadangan |
| Resistensi pengguna dari kertas ke digital | Mode hybrid, pelatihan, UI sederhana, pilot bertahap |
| Lingkup membengkak | Ikuti fase; MVP ketat pada persuratan dan operasional |
| Keputusan single-tenant sulit dibalik | Disadari sejak awal; evaluasi ulang bila jumlah pelanggan dan biaya operasional menuntut |

### Asumsi
- Pengguna awal adalah Politeknik dengan alur sesuai SOP terlampir.
- Sistem langganan terpisah akan menyediakan API verifikasi lisensi sesuai kontrak Bagian 10.5.
- Penyedia TTE dipilih per instansi; integrasi dibuat melalui adapter.
- Aplikasi web responsif cukup untuk v1.
- Harga paket (Lampiran C) adalah hipotesis awal dan belum divalidasi ke pasar.
- Seluruh pelanggan awal dilayani dengan Mode A (dihosting vendor).

### Pertanyaan Terbuka (perlu keputusan)
1. ~~Penyajian~~ **Diputuskan:** Mode A (dihosting vendor) pada awal; Mode B ditunda sampai ada permintaan pelanggan.
2. **Paket dan harga:** usulan 3 paket ada di Lampiran C; perlu divalidasi ke 3-5 calon pelanggan, termasuk batas kuota, biaya setup, dan add-on.
3. **Periode penagihan:** bulanan saja, atau juga tahunan (umumnya lebih cocok untuk instansi yang menganggarkan per tahun)?
4. **Perilaku saat lisensi berakhir:** apakah mode baca-saja plus ekspor (usulan) dapat diterima secara bisnis?
5. **Penyedia TTE awal** dan apakah TTE wajib di MVP atau cukup tanda tangan basah dulu.
6. **Kepemilikan data dan kontrak:** perjanjian pemrosesan data, SLA, kebijakan retensi data setelah berhenti berlangganan.
7. **Segmen pemerintah:** apakah akan dikaji, dan kapan.
8. **Nama produk, domain, dan identitas visual.**

## Lampiran A — Contoh Format Nomor Surat (dapat dikonfigurasi per instansi)

| Token | Arti |
|---|---|
| `{urut}` | Nomor urut (reset per tahun/unit sesuai pengaturan) |
| `{kode_unit}` | Kode unit/jurusan pembuat |
| `{kode_klasifikasi}` | Kode klasifikasi arsip |
| `{bulan_romawi}` | Bulan dalam angka Romawi |
| `{tahun}` | Tahun surat |

Contoh: `0123/PL.7/KP.01.02/X/2026`

## Lampiran B — Sifat dan Jenis Surat (nilai awal profil Politeknik)

- **Sifat:** Biasa, Penting, Segera, Rahasia.
- **Jenis:** Undangan, Permohonan, Pemberitahuan, Edaran, Surat Tugas, Surat Keterangan, Nota Dinas, SK, Lainnya.
- Seluruh nilai dapat ditambah atau diubah oleh Admin Instansi; profil instansi lain memiliki nilai awal berbeda.

## Lampiran C — Kerangka Ekonomi Langganan (untuk validasi, bukan angka final)

### C.1 Usulan Paket (hipotesis awal, Mode A dihosting vendor)

| | **Dasar** | **Standar** (jangkar) | **Lanjutan** |
|---|---|---|---|
| **Harga/bulan** | Rp450.000 | **Rp750.000** | mulai Rp1.750.000 |
| **Bayar tahunan** | Rp4,5 juta (bayar 10 bulan) | Rp7,5 juta (bayar 10 bulan) | Rp17,5 juta |
| **Pengguna aktif** | hingga 25 | hingga 100 | 300+ (dapat dinegosiasi) |
| **Penyimpanan** | 10 GB | 50 GB | 200 GB |
| **Fitur** | Surat masuk, disposisi, surat keluar, penomoran, arsip dasar, notifikasi email | Semua paket Dasar + OCR, dashboard, delegasi/Plt, template tak terbatas, integrasi TTE dan WhatsApp | Semua paket Standar + SSO, integrasi SIAKAD/SIMPEG, domain sendiri, API, modul tambahan (rapat, MoU, SPPD) |
| **Backup** | harian, simpan 14 hari | harian, simpan 30 hari | harian, simpan 90 hari + uji restore berkala |
| **Dukungan** | tiket/email, respons ±2 hari kerja | chat + tiket, respons ±1 hari kerja | prioritas, respons ±4 jam kerja, SLA tertulis |
| **Biaya setup (sekali)** | Rp2,5 juta | Rp5 juta | mulai Rp10 juta |
| **Cocok untuk** | Sekolah, yayasan, kampus kecil, kantor kecil | Politeknik/kampus menengah, rumah sakit, BUMD kecil | Kampus besar, korporasi, instansi dengan integrasi |

Catatan: seluruh angka di atas, terutama biaya setup dan batas kuota, adalah titik awal untuk diuji ke calon pelanggan, bukan angka yang sudah divalidasi pasar.

### C.2 Aturan Komersial yang Diusulkan

- **Fitur inti persuratan ada di semua paket**; pembeda utama adalah kuota, dukungan, dan integrasi.
- **Pengguna di atas kuota** ditagih per pengguna tambahan per bulan (titik awal uji: sekitar Rp5.000-10.000).
- **Biaya pihak ketiga diteruskan ke pelanggan atau dijual sebagai add-on:** gateway WhatsApp, sertifikat/tanda tangan TTE dari penyedia, penyimpanan di atas kuota.
- **Bayar tahunan** diberi insentif (bayar 10 bulan); opsi ini cocok untuk instansi yang menganggarkan per tahun.
- **Harga pilot** untuk pelanggan pertama: diskon besar 6-12 bulan dengan imbalan masukan dan testimoni; harga normal tetap ditulis di kontrak.
- **Kuota** ditegakkan sesuai FR-LIC-07: akses ke data yang ada tidak pernah dikunci karena kuota.
- **Pindah paket** dapat dilakukan kapan saja (FR-LIC-08).

### C.3 Perhitungan Margin

Margin kotor per pelanggan per bulan (rumus):

```
Margin = Harga langganan
       − biaya server/instance (hosting)
       − biaya penyimpanan dan backup
       − biaya layanan pihak ketiga yang tidak diteruskan ke pelanggan
         (email, OCR eksternal, dll.)
       − biaya pembayaran/penagihan
       − alokasi waktu dukungan (jam dukungan × nilai jam tim)
```

Hal yang perlu diisi dengan angka riil sebelum harga dikunci:
1. Biaya server per instance pada penyedia yang dipilih (isi sendiri dari harga aktual).
2. Rata-rata jam dukungan per pelanggan per bulan yang realistis, dan nilai jam tim.
3. Biaya yang **diteruskan ke pelanggan** (usulan): gateway WhatsApp, biaya TTE/sertifikat per pengguna dari penyedia, penyimpanan di atas kuota.
4. **Biaya setup sekali bayar** (instalasi, konfigurasi, import data, pelatihan) agar biaya onboarding tidak tertutup langganan bulanan.
5. Kuota paket (pengguna aktif, penyimpanan) dan harga tambahan di atas kuota.
6. Add-on berbayar (modul lanjutan, kustomisasi, SLA lebih ketat).
7. Opsi diskon bayar tahunan.
