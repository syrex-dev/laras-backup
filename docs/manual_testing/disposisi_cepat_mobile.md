# Panduan Pengujian Manual: Disposisi Cepat Mobile (Alur <= 3 Ketukan & Baca Naskah)

Dokumen ini menyediakan panduan uji fungsional dan usability untuk fitur **Disposisi Cepat (Quick Disposition)** pada perangkat mobile/smartphone.

---

## 1. Sumber Data Preset (Tanpa Hardcode)
Preset rekomendasi dihasilkan secara dinamis langsung dari database:
1. **Template Instruksi (`instruction_templates`)**: Diambil dari tabel database berdasarkan status aktif (`is_active = true`) dan urutan (`urutan`). Contoh: "Tindak lanjuti segera", "Koordinasikan", dsb.
2. **Hierarki Organisasi (`positions` & `org_units`)**: Menentukan penerima bawahan langsung berdasarkan relasi `parent_id` unit organisasi dan `level` jabatan pengguna yang sedang login.
3. **Kamus Terminologi (`useTerminology`)**: Label jabatan dan unit ditampilkan sesuai konfigurasi instansi (`terminology_dict`), tanpa hardcode istilah "Kajur", "Jurusan", atau "Fakultas".

---

## 2. Perhitungan Ketukan Layar (Tap Count Calculation)

### Skenario A: Disposisi Cepat Langsung (Pimpinan telah mengetahui naskah)
*Target: Tepat 3 Ketukan.*
1. **Ketukan 1**: Dari kartu surat di Kotak Masuk Disposisi, ketuk tombol **⚡ Disposisi Cepat**. Modal terbuka langsung dengan opsi preset.
2. **Ketukan 2**: Ketuk salah satu kartu **Preset Rekomendasi** (misal: *Kepala Bagian Akademik — Segera tindak lanjuti*). Penerima, instruksi, dan prioritas langsung terpilih otomatis dengan indikator centang hijau.
3. **Ketukan 3**: Ketuk tombol **🚀 Kirim Disposisi Sekarang**. Disposisi terkirim, modal tertutup, dan data ter-update seketika.

### Skenario B: Disposisi Cepat dengan Membaca Naskah di Tengah Alur
*Pimpinan ingin memeriksa ringkasan, tanggal, nomor surat, atau berkas lampiran sebelum memutuskan disposisi.*
1. **Ketukan 1**: Ketuk tombol **⚡ Disposisi Cepat** pada kartu surat. Modal terbuka.
2. **(Opsional Membaca) Ketukan 2a**: Ketuk tombol accordion **📖 Baca Isi Naskah Lengkap & Lampiran**.
   - Layar ekspansi langsung memuat nomor naskah, tanggal surat, sifat (Biasa/Penting/Rahasia), ekstrak teks ringkasan, serta daftar berkas lampiran.
   - Pimpinan dapat membaca atau mengunduh lampiran tanpa keluar dari modal atau berpindah halaman.
3. **Ketukan 2 / 3**: Ketuk kartu **Preset Rekomendasi** yang diinginkan.
4. **Ketukan 3 / 4**: Ketuk tombol **🚀 Kirim Disposisi Sekarang**. Disposisi selesai terkirim.

---

## 3. Langkah Pengujian Manual Langkah demi Langkah (Mobile Viewport)

### Persiapan Uji
1. Buka browser (Chrome / Safari / Firefox) dan aktifkan *Device Mode* (Viewport: 375x667 atau 390x844).
2. Login sebagai akun Pimpinan (contoh: `direktur@laras.test` / password akun demo).
3. Akses menu **Kotak Masuk Disposisi** (`/disposisi`).

### Skenario Uji 1: Verifikasi Preset Dinamis
- **Langkah**: Ketuk tombol **⚡ Disposisi Cepat** pada salah satu surat.
- **Harapan**:
  - Judul modal menampilkan *⚡ Disposisi Cepat (1-Touch Preset)*.
  - Kartu preset menampilkan nama posisi bawahan yang sesuai dengan hierarki organisasi di DB.
  - Tidak ada nama jabatan statis/hardcoded di template.
  - Preset pertama terpilih secara default atau mudah dipilih dengan 1 sentuhan.

### Skenario Uji 2: Pembacaan Surat di Tengah Alur (In-Flow Reading)
- **Langkah**: Saat modal terbuka, ketuk **📖 Baca Isi Naskah Lengkap & Lampiran**.
- **Harapan**:
  - Accordion terbuka mulus (*smooth transition*).
  - Menampilkan Nomor Surat, Tanggal Surat, Sifat Naskah, dan Teks Ringkasan.
  - Jika surat memiliki lampiran, tombol unduh berkas lampiran tampil dan dapat diunduh.
  - Mengetuk tombol sekali lagi akan melipat panel pembaca naskah.

### Skenario Uji 3: Pengiriman Disposisi & Validasi Database
- **Langkah**: Ketuk salah satu preset, lalu ketuk **🚀 Kirim Disposisi Sekarang**.
- **Harapan**:
  - Tombol berubah status menjadi *Mengirim...* dan terkunci sementara (*disabled*).
  - Muncul notifikasi flash *Disposisi naskah berhasil dikirim*.
  - Modal tertutup otomatis.
  - Surat masuk bersangkutan di kotak masuk penerima kini berstatus `belum_dibaca`.

---

## 4. Hasil Uji Otomatis
- Feature Test: [DisposisiCepatTest.php](../../tests/Feature/DisposisiCepatTest.php)
  - `test_disposisi_index_provides_dynamic_database_presets_without_hardcoded_titles`: **PASS**
  - `test_quick_disposisi_submission_creates_disposisi_and_recipient`: **PASS**
  - `test_unauthorized_user_cannot_submit_quick_disposisi`: **PASS**
