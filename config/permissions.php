<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Resmi Sistem E-Office (Sesuai PRD)
    |--------------------------------------------------------------------------
    |
    | 5 Peran resmi PRD yang diakui dalam sistem persuratan single-tenant:
    | 1. Admin Instansi - Administrator sistem, konfigurasi, pengguna, dan audit
    | 2. Pimpinan       - Rektor/Direktur/Ketua, pengambil keputusan tertinggi, penandatangan
    | 3. Kepala Unit    - Dekan/Kajur/Kaprodi/Kabag, pembuat disposisi unit, pemeriksa/paraf
    | 4. Staf TU        - Tata usaha, operator registrasi surat masuk, penyiap draf
    | 5. Petugas Arsip  - Pengelola klasifikasi arsip, retensi berkas, dan log arsip
    |
    */
    'roles' => [
        'Admin Instansi' => 'Administrator instansi pengelola konfigurasi, pengguna, dan pengawasan sistem',
        'Pimpinan' => 'Pimpinan instansi dengan wewenang persetujuan tertinggi dan disposisi utama',
        'Kepala Unit' => 'Pimpinan tingkat unit kerja / departemen dengan hak disposisi berjenjang dan paraf',
        'Staf TU' => 'Staf tata usaha pengelola registrasi persuratan masuk dan keluar',
        'Petugas Arsip' => 'Pengelola klasifikasi naskah dinas, retensi arsip, dan log statis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar Hak Akses (Permissions) Terpusat
    |--------------------------------------------------------------------------
    |
    | Satu sumber kebenaran seluruh permission yang digunakan dalam aplikasi.
    |
    */
    'permissions' => [
        // --- Modul Organisasi & Pengguna ---
        'organisasi.kelola' => [
            'label' => 'Kelola Master Organisasi',
            'description' => 'Mengatur profil instansi, kamus istilah, unit kerja, jabatan, dan akun pengguna',
            'module' => 'organisasi',
        ],
        'organisasi.lihat' => [
            'label' => 'Lihat Struktur Organisasi',
            'description' => 'Melihat daftar unit kerja, jabatan, dan profil pejabat instansi',
            'module' => 'organisasi',
        ],

        // --- Modul Surat Masuk ---
        'surat-masuk.lihat' => [
            'label' => 'Lihat Surat Masuk',
            'description' => 'Melihat daftar dan membaca rincian surat masuk yang terdaftar',
            'module' => 'surat_masuk',
        ],
        'surat-masuk.registrasi' => [
            'label' => 'Registrasi Surat Masuk',
            'description' => 'Mencatat surat masuk baru, menerbitkan nomor agenda, dan mengunggah lampiran',
            'module' => 'surat_masuk',
        ],
        'surat-masuk.ubah' => [
            'label' => 'Ubah Surat Masuk',
            'description' => 'Memperbarui data dan metadata surat masuk sebelum diproses lebih lanjut',
            'module' => 'surat_masuk',
        ],
        'surat-masuk.batalkan' => [
            'label' => 'Batalkan Surat Masuk',
            'description' => 'Membatalkan pencatatan surat masuk dengan menyertakan alasan resmi',
            'module' => 'surat_masuk',
        ],

        // --- Modul Disposisi ---
        'disposisi.lihat' => [
            'label' => 'Lihat Kotak Masuk Disposisi',
            'description' => 'Melihat daftar disposisi yang diterima atau diteruskan oleh pengguna',
            'module' => 'disposisi',
        ],
        'disposisi.buat' => [
            'label' => 'Buat Disposisi Awal',
            'description' => 'Menyusun instruksi disposisi awal dari naskah surat masuk',
            'module' => 'disposisi',
        ],
        'disposisi.teruskan' => [
            'label' => 'Teruskan Disposisi Berjenjang',
            'description' => 'Meneruskan instruksi disposisi ke staf atau pimpinan bawahan',
            'module' => 'disposisi',
        ],
        'disposisi.tindaklanjut' => [
            'label' => 'Tindak Lanjut Disposisi',
            'description' => 'Mengisi laporan penyelesaian dan mengunggah bukti tindak lanjut disposisi',
            'module' => 'disposisi',
        ],
        'disposisi.kelola' => [
            'label' => 'Tata Kelola Disposisi',
            'description' => 'Akses administratif pengawasan dan aksi disposisi atas nama pejabat terkait',
            'module' => 'disposisi',
        ],
        'disposisi.lihat-semua' => [
            'label' => 'Lihat Semua Disposisi',
            'description' => 'Melihat seluruh riwayat disposisi instansi untuk pengawasan dan audit',
            'module' => 'disposisi',
        ],
        'disposisi.ambil-alih' => [
            'label' => 'Ambil Alih Disposisi',
            'description' => 'Mengambil alih disposisi tertunda atau mandat pejabat sementara (Plt)',
            'module' => 'disposisi',
        ],

        // --- Modul Surat Keluar ---
        'surat-keluar.lihat' => [
            'label' => 'Lihat Surat Keluar',
            'description' => 'Melihat daftar dan isi naskah dinas keluar unit kerja',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.buat' => [
            'label' => 'Buat Draf Surat Keluar',
            'description' => 'Menyusun draf baru naskah dinas keluar berbasis template resmi',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.ubah-semua' => [
            'label' => 'Ubah Semua Draf Surat Keluar',
            'description' => 'Mengedit dan merevisi draf surat keluar milik anggota tim lain dalam unit',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.periksa' => [
            'label' => 'Pemeriksaan / Paraf Naskah',
            'description' => 'Memberikan paraf atau review koreksi pada alur pemeriksaan berjenjang',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.setujui' => [
            'label' => 'Persetujuan Akhir Surat Keluar',
            'description' => 'Memberikan persetujuan final sebagai penandatangan naskah dinas',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.nomori' => [
            'label' => 'Alokasi Nomor Surat Keluar',
            'description' => 'Menerbitkan nomor resmi surat keluar dari urutan penomoran otomatis',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.batalkan' => [
            'label' => 'Batalkan Surat Keluar',
            'description' => 'Membatalkan naskah surat keluar yang telah berstatus bernomor resmi',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.template' => [
            'label' => 'Kelola Template Naskah Dinas',
            'description' => 'Membuat, mengedit, dan mematikan master template surat keluar instansi',
            'module' => 'surat_keluar',
        ],
        'surat-keluar.kelola' => [
            'label' => 'Tata Kelola Administratif Surat Keluar',
            'description' => 'Pengawasan global dan intervensi alur kerja surat keluar antar-unit',
            'module' => 'surat_keluar',
        ],

        // --- Modul Arsip & Klasifikasi ---
        'arsip.lihat' => [
            'label' => 'Lihat Klasifikasi & Berkas Arsip',
            'description' => 'Melihat daftar kode klasifikasi naskah dan jadwal retensi arsip (JRA)',
            'module' => 'arsip',
        ],
        'arsip.kelola' => [
            'label' => 'Kelola Master Klasifikasi Arsip',
            'description' => 'Mengatur dan memperbarui master tabel klasifikasi surat dinas',
            'module' => 'arsip',
        ],

        // --- Modul Operasi, Audit & Pemeliharaan ---
        'sistem.audit' => [
            'label' => 'Lihat Jejak Audit Sistem',
            'description' => 'Melihat log aktivitas append-only seluruh aksi penting pengguna',
            'module' => 'sistem',
        ],
        'sistem.pemeliharaan' => [
            'label' => 'Pemeliharaan Sistem & Pencadangan',
            'description' => 'Menjalankan pencadangan database dan mengelola data di tong sampah (recycle bin)',
            'module' => 'sistem',
        ],

        // --- Ekstensi Fase Mendatang (Ditandai Khusus) ---
        'surat-keluar.tte' => [
            'label' => 'Tanda Tangan Elektronik Tersertifikasi',
            'description' => 'Menandatangani dokumen via integrasi sertifikat digital BSrE / Peruri',
            'module' => 'surat_keluar',
            'phase' => 'Fase 3B',
        ],
        'surat-keluar.kirim-eksternal' => [
            'label' => 'Kirim Eksternal Resmi',
            'description' => 'Mengirimkan surat keluar dan tembusan ke instansi luar via email resmi',
            'module' => 'surat_keluar',
            'phase' => 'Fase 3B',
        ],
        'arsip.penyusutan' => [
            'label' => 'Penyusutan & Pemusnahan Arsip',
            'description' => 'Menjalankan jadwal retensi pemusnahan berkas inaktif sesuai regulasi ANRI',
            'module' => 'arsip',
            'phase' => 'Fase 4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Default Peran (Roles) ke Hak Akses (Permissions)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini dijadikan acuan resmi oleh RolePermissionSeeder dan
    | command `eoffice:sync-permissions` untuk menyelaraskan database.
    |
    */
    'role_permissions' => [
        'Admin Instansi' => [
            'organisasi.kelola',
            'organisasi.lihat',
            'surat-masuk.lihat',
            'surat-masuk.registrasi',
            'surat-masuk.ubah',
            'surat-masuk.batalkan',
            'disposisi.lihat',
            'disposisi.buat',
            'disposisi.teruskan',
            'disposisi.tindaklanjut',
            'disposisi.kelola',
            'disposisi.lihat-semua',
            'disposisi.ambil-alih',
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.ubah-semua',
            'surat-keluar.periksa',
            'surat-keluar.nomori',
            'surat-keluar.batalkan',
            'surat-keluar.template',
            'surat-keluar.kelola',
            'arsip.lihat',
            'arsip.kelola',
            'sistem.audit',
            'sistem.pemeliharaan',
            'surat-keluar.kirim-eksternal',
        ],

        'Pimpinan' => [
            'organisasi.lihat',
            'surat-masuk.lihat',
            'disposisi.lihat',
            'disposisi.buat',
            'disposisi.teruskan',
            'disposisi.tindaklanjut',
            'disposisi.ambil-alih',
            'surat-keluar.lihat',
            'surat-keluar.periksa',
            'surat-keluar.setujui',
            'surat-keluar.nomori',
            'surat-keluar.batalkan',
            'arsip.lihat',
            'surat-keluar.tte',
        ],

        'Kepala Unit' => [
            'organisasi.lihat',
            'surat-masuk.lihat',
            'disposisi.lihat',
            'disposisi.buat',
            'disposisi.teruskan',
            'disposisi.tindaklanjut',
            'disposisi.ambil-alih',
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.ubah-semua',
            'surat-keluar.periksa',
            'surat-keluar.setujui',
            'arsip.lihat',
            'surat-keluar.tte',
        ],

        'Staf TU' => [
            'organisasi.lihat',
            'surat-masuk.lihat',
            'surat-masuk.registrasi',
            'surat-masuk.ubah',
            'surat-masuk.batalkan',
            'disposisi.lihat',
            'disposisi.tindaklanjut',
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.nomori',
            'surat-keluar.template',
            'arsip.lihat',
            'surat-keluar.kirim-eksternal',
        ],

        'Petugas Arsip' => [
            'organisasi.lihat',
            'surat-masuk.lihat',
            'surat-keluar.lihat',
            'arsip.lihat',
            'arsip.kelola',
            'arsip.penyusutan',
        ],
    ],
];
