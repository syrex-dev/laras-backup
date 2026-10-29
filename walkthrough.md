# Walkthrough Project Simpan.in

## 1. Ringkasan Proyek
Simpan.in adalah aplikasi manajemen surat digital untuk organisasi dengan alur kerja berbasis role:
- Admin
- Sekretaris
- Pimpinan
- Staf

Fokus utama pada saat ini adalah memastikan seluruh fitur, tombol, dan akses route sesuai dengan hak peran masing-masing agar tampilan bersih, aman, dan rapi.

---

## 2. Hak Akses yang Benar

### Admin
Boleh mengakses:
- Manajemen User
- Profil Organisasi / Templat Kop Surat
- Klasifikasi Surat
- Templat Surat
- Log Audit

Tidak boleh tampil/akses:
- Pembuatan surat keluar oleh Sekretaris
- Approval / reject surat keluar
- Aksi tanda tangan Pimpinan

### Sekretaris
Boleh mengakses:
- Surat Keluar
- Pembuatan dan edit draft surat keluar
- Klasifikasi Surat
- Templat Surat

Tidak boleh tampil/akses:
- Manajemen User
- Templat Kop Surat
- Approval surat keluar

### Pimpinan
Boleh mengakses:
- Approval / reject surat keluar
- Aksi disposisi terkait pimpinan
- Tanda tangan / profil tanda tangan

Tidak boleh tampil/akses:
- Pembuatan surat keluar sebagai Sekretaris
- Manajemen User
- Templat Kop Surat

### Staf
Boleh mengakses:
- Surat Masuk dan surat yang relevan
- Disposisi yang ditunjuk untuknya

Tidak boleh tampil/akses:
- Form pembuatan surat keluar
- Manajemen User
- Templat Surat
- Templat Kop Surat
- Aksi approval

---

## 3. Perbaikan yang Dilakukan

### A. Penyesuaian Route dan Middleware
Perbaikan utama dilakukan di [routes/web.php](routes/web.php):
- Klasifikasi surat dibatasi hanya untuk Admin dan Sekretaris
- Surat keluar hanya menerima create/edit untuk role Sekretaris
- Approval dan reject surat keluar hanya untuk Pimpinan
- Pembuatan disposisi dibatasi hanya untuk Pimpinan

Tujuan: backend juga harus konsisten dengan tampilan agar tidak ada celah akses.

### B. Penyesuaian UI Blade
Perubahan dilakukan di beberapa view utama:
- [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php)
- [resources/views/surat-keluar/index.blade.php](resources/views/surat-keluar/index.blade.php)
- [resources/views/surat-keluar/show.blade.php](resources/views/surat-keluar/show.blade.php)
- [resources/views/surat-masuk/show.blade.php](resources/views/surat-masuk/show.blade.php)

Semua tombol dan link yang tidak sesuai role dihapus atau dibatasi dengan pengecekan role.

### C. Konsistensi Akses
Dibuat pengaturan agar role tidak hanya terlihat di UI tetapi juga aman di backend. Ini mencegah user yang mencoba akses langsung via URL atau form.

---

## 4. Alur kerja sistem yang sudah sesuai

### Login
- URL: http://127.0.0.1:8000/login
- Akun demo yang tersedia:
  - Admin: admin@himase.ac.id / password
  - Sekretaris: sekretaris@himase.ac.id / password
  - Pimpinan: ketua@himase.ac.id / password
  - Staf: staf@himase.ac.id / password

### Dashboard
Setelah login, dashboard menampilkan statistik umum dan hanya item yang relevan sesuai role.

### Surat Keluar
- Sekretaris: bisa buat dan kelola draft
- Pimpinan: bisa approve/reject jika masih draft
- role lain tidak bisa menampilkan tombol aksi sesuai hak

### Disposisi
- Pimpinan bisa membuat disposisi
- penerima disposisi hanya melihat data yang ditugaskan kepada mereka
- status update dilakukan hanya oleh penerima yang berhak

### Admin Tools
- Admin bisa akses user management dan profil organisasi
- menu tidak muncul untuk role lain

---

## 5. Langkah Validasi
Untuk mengecek lagi setelah perubahan:
1. Login menggunakan akun masing-masing role
2. Cek sidebar menu
3. Cek halaman surat keluar dan disposisi
4. Coba akses URL sensitif secara manual
5. Pastikan semua akses yang tidak berwenang menghasilkan 403 atau tidak muncul tombol

---

## 6. Catatan Final
Semua perbaikan saat ini bertujuan agar sistem tidak hanya bekerja, tetapi juga terlihat rapi dan aman sesuai prinsip role-based access control.

Jika ada tambahan kebutuhan QA lanjutan, semua akses utama sudah dibatasi pada level yang benar dan konsisten.
