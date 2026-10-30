<?php

namespace Database\Seeders;

use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\RejectSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\RequestRevisionSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuratKeluarDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi basis data dengan contoh realistis naskah dinas keluar pada berbagai status:
     * 1. Draf awal (baru disusun oleh staf TU)
     * 2. Sedang Diperiksa (pemeriksaan berjenjang tingkat 1 / paraf)
     * 3. Diminta Revisi (dikembalikan pemeriksa disertai catatan perbaikan & riwayat versi)
     * 4. Ditolak (ditolak definitif dengan alasan resmi)
     * 5. Bernomor Resmi (telah disetujui penuh & memperoleh nomor agenda serta nomor surat otomatis)
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('SuratKeluarDemoSeeder dilewati karena sistem tidak berada pada environment local/testing.');
            return;
        }

        // Ambil user kunci
        $stafTu = User::where('email', 'staf.tu@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Staf TU'))->first()
            ?? User::first();

        $kajur = User::where('email', 'kajur.jtk@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Kepala Unit'))->first()
            ?? User::first();

        $direktur = User::where('email', 'direktur@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Pimpinan'))->first()
            ?? User::first();

        $admin = User::where('email', 'admin@contoh.ac.id')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'Admin Instansi'))->first()
            ?? User::first();

        $unitJtk = OrgUnit::where('code', 'JTK')->first() ?? OrgUnit::first();
        $unitSekret = OrgUnit::where('code', 'SEKRET')->first() ?? $unitJtk;

        $posDirektur = $direktur?->position ?? Position::first();
        $posKajur = $kajur?->position ?? Position::first();

        $klasifikasiHm = KlasifikasiSurat::where('kode_klasifikasi', 'HM.01')->first()
            ?? KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'HM.01'], ['nama' => 'Hubungan Masyarakat & Kerjasama']);
        $klasifikasiKp = KlasifikasiSurat::where('kode_klasifikasi', 'KP.02')->first()
            ?? KlasifikasiSurat::firstOrCreate(['kode_klasifikasi' => 'KP.02'], ['nama' => 'Kepegawaian & Penugasan']);

        $tplTugas = TemplateSurat::where('kode', 'ST-01')->first() ?? TemplateSurat::first();
        $tplNota = TemplateSurat::where('kode', 'ND-01')->first() ?? $tplTugas;

        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $submitAction = app(SubmitSuratKeluarAction::class);
        $approveAction = app(ApproveSuratKeluarAction::class);
        $reviseAction = app(RequestRevisionSuratKeluarAction::class);
        $rejectAction = app(RejectSuratKeluarAction::class);

        // -------------------------------------------------------------
        // 1. SURAT STATUS: DRAF
        // -------------------------------------------------------------
        $draft = $saveAction->execute($stafTu, [
            'template_id' => $tplNota?->id,
            'klasifikasi_id' => $klasifikasiHm->id,
            'org_unit_id' => $unitSekret?->id,
            'penandatangan_id' => $direktur->id,
            'tujuan' => 'Seluruh Ketua Jurusan & Kepala Bagian',
            'perihal' => 'Penyusunan Rencana Kebutuhan Sarana Laboratorium T.A. 2027',
            'isi_surat' => '<p>Sehubungan dengan penyusunan RKAT tahun anggaran mendatang, dimohon seluruh pimpinan unit kerja untuk mendata kebutuhan peremajaan perangkat lab...</p>',
            'sifat' => SuratKeluar::SIFAT_BIASA,
            'tanggal_surat' => now()->format('Y-m-d'),
            'catatan_perubahan' => 'Draf awal disusun berdasarkan arahan rapim',
        ]);

        // -------------------------------------------------------------
        // 2. SURAT STATUS: DIPERIKSA (Sedang menunggu paraf Kajur)
        // -------------------------------------------------------------
        $inReview = $saveAction->execute($stafTu, [
            'template_id' => $tplTugas?->id,
            'klasifikasi_id' => $klasifikasiKp->id,
            'org_unit_id' => $unitJtk?->id,
            'penandatangan_id' => $direktur->id,
            'tujuan' => 'Dr. Ir. Ahmad Fadillah, M.T. (Dosen JTK)',
            'perihal' => 'Surat Tugas Partisipasi Konferensi Internasional ICSE 2026',
            'isi_surat' => '<p>Direktur Politeknik Contoh dengan ini menugaskan kepada dosen yang bersangkutan untuk mempresentasikan paper ilmiah pada Konferensi Internasional...</p>',
            'sifat' => SuratKeluar::SIFAT_PENTING,
            'tanggal_surat' => now()->format('Y-m-d'),
        ]);

        $submitAction->execute($stafTu, $inReview, [
            ['user_id' => $kajur->id, 'role_type' => SuratKeluarApproval::ROLE_PARAF, 'position_id' => $posKajur?->id],
            ['user_id' => $direktur->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN, 'position_id' => $posDirektur?->id],
        ]);

        // -------------------------------------------------------------
        // 3. SURAT STATUS: REVISI (Dengan catatan perbaikan dari pemeriksa)
        // -------------------------------------------------------------
        $revision = $saveAction->execute($stafTu, [
            'template_id' => $tplNota?->id,
            'klasifikasi_id' => $klasifikasiHm->id,
            'org_unit_id' => $unitSekret?->id,
            'penandatangan_id' => $direktur->id,
            'tujuan' => 'Dinas Pendidikan Provinsi Jawa Barat',
            'perihal' => 'Usulan Program Beasiswa Vokasi Unggulan Daerah',
            'isi_surat' => '<p>Bersama ini kami mengajukan rincian kuota penerima beasiswa vokasi...</p>',
            'sifat' => SuratKeluar::SIFAT_PENTING,
        ]);

        $submitAction->execute($stafTu, $revision, [
            ['user_id' => $kajur->id, 'role_type' => SuratKeluarApproval::ROLE_PEMERIKSA, 'position_id' => $posKajur?->id],
            ['user_id' => $direktur->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN, 'position_id' => $posDirektur?->id],
        ]);

        $reviseAction->execute(
            $kajur,
            $revision,
            'Mohon lampirkan tabel kalkulasi anggaran biaya hidup mahasiswa per semester sesuai standar SBM terbaru.'
        );

        // -------------------------------------------------------------
        // 4. SURAT STATUS: DITOLAK
        // -------------------------------------------------------------
        $rejected = $saveAction->execute($stafTu, [
            'template_id' => $tplNota?->id,
            'klasifikasi_id' => $klasifikasiHm->id,
            'org_unit_id' => $unitSekret?->id,
            'penandatangan_id' => $direktur->id,
            'tujuan' => 'Panitia Lomba Nasional Mahasiswa',
            'perihal' => 'Permohonan Dana Bantuan Transportasi Kontingen Lomba',
            'isi_surat' => '<p>Pengajuan dispensasi dana perjalanan kontingen...</p>',
            'sifat' => SuratKeluar::SIFAT_BIASA,
        ]);

        $submitAction->execute($stafTu, $rejected, [
            ['user_id' => $direktur->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN, 'position_id' => $posDirektur?->id],
        ]);

        $rejectAction->execute(
            $direktur,
            $rejected,
            'Pengajuan dana tidak dapat diproses karena anggaran pos kemahasiswaan triwulan ini telah dialokasikan penuh. Gunakan pos sponsor mitra.'
        );

        // -------------------------------------------------------------
        // 5. SURAT STATUS: BERNOMOR RESMI (Alur lengkap + nomor resmi)
        // -------------------------------------------------------------
        $approved = $saveAction->execute($stafTu, [
            'template_id' => $tplTugas?->id,
            'klasifikasi_id' => $klasifikasiKp->id,
            'org_unit_id' => $unitJtk?->id,
            'penandatangan_id' => $direktur->id,
            'tujuan' => 'Tim Pengembang Sistem E-Office Politeknik',
            'perihal' => 'Penetapan Gugus Tugas Modernisasi Tata Kelola Persuratan Elektronik',
            'isi_surat' => '<p>Menetapkan susunan panitia dan tim pengembang sistem persuratan berbasis otomasi digital untuk semester genap...</p>',
            'sifat' => SuratKeluar::SIFAT_PENTING,
            'tanggal_surat' => now()->format('Y-m-d'),
        ]);

        $submitAction->execute($stafTu, $approved, [
            ['user_id' => $kajur->id, 'role_type' => SuratKeluarApproval::ROLE_PARAF, 'position_id' => $posKajur?->id],
            ['user_id' => $direktur->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN, 'position_id' => $posDirektur?->id],
        ]);

        // Kajur menyetujui paraf
        $approveAction->execute($kajur, $approved, 'Telah dikoreksi dan disetujui');
        $approved->refresh();

        // Direktur menyetujui final & alokasi nomor
        $approveAction->execute($direktur, $approved, 'Disetujui untuk diterbitkan naskah resminya', true);

        // -------------------------------------------------------------
        // 6. SURAT BALASAN DISPOSISI TERSELESAIKAN (OQ-10)
        // -------------------------------------------------------------
        $recipientWaiting = DisposisiRecipient::where('status', '!=', DisposisiRecipient::STATUS_SELESAI)->first();
        if ($recipientWaiting) {
            $replyLetter = $saveAction->execute($kajur, [
                'template_id' => $tplNota?->id,
                'klasifikasi_id' => $klasifikasiHm->id,
                'org_unit_id' => $unitJtk?->id,
                'penandatangan_id' => $kajur->id,
                'tujuan' => 'Direktur Politeknik',
                'perihal' => 'Tindak Lanjut Arahan Disposisi: Kesiapan Kurikulum Baru',
                'isi_surat' => '<p>Menindaklanjuti lembar arahan disposisi, Jurusan Teknik Komputer telah merampungkan draf RPS dan modul praktikum pendukung...</p>',
                'sifat' => SuratKeluar::SIFAT_BIASA,
            ]);

            $recipientWaiting->update([
                'surat_keluar_id' => $replyLetter->id,
                'status' => DisposisiRecipient::STATUS_DIPROSES,
            ]);

            $submitAction->execute($kajur, $replyLetter, [
                ['user_id' => $kajur->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN, 'position_id' => $posKajur?->id],
            ]);

            $approveAction->execute($kajur, $replyLetter, 'Disetujui sebagai tanggapan resmi unit', true);
            $replyLetter->refresh();

            // Selesaikan disposisi
            $resolveAction = app(\App\Domain\SuratKeluar\Actions\ResolveDisposisiFromReplyAction::class);
            $resolveAction->execute($kajur, $replyLetter, 'Laporan kesiapan kurikulum telah selesai disusun.');
        }

        $this->command?->info('SuratKeluarDemoSeeder berhasil menambahkan ragam naskah dinas keluar (Draf, Diperiksa, Revisi, Ditolak, Bernomor, Balasan Disposisi).');
    }
}
