<?php

namespace Tests\Feature;

use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Database\Seeders\PoliteknikDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratMasukWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $stafTu;

    protected User $direktur;

    protected User $arsip;

    protected KlasifikasiSurat $klasifikasi;

    protected OrgUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PoliteknikDemoSeeder::class);

        $this->stafTu = User::where('email', 'tu@contoh.ac.id')->firstOrFail();
        $this->direktur = User::where('email', 'direktur@contoh.ac.id')->firstOrFail();
        $this->arsip = User::where('email', 'arsip@contoh.ac.id')->firstOrFail();

        $this->klasifikasi = KlasifikasiSurat::firstOrCreate(
            ['kode_klasifikasi' => 'HM.01'],
            ['nama' => 'Kerjasama Antar Lembaga', 'keterangan' => 'Arsip naskah kerjasama']
        );

        $this->unit = OrgUnit::where('code', 'TU')->firstOrFail();
        Storage::fake('local');
    }

    public function test_staf_tu_can_register_incoming_letter_with_private_attachment_and_agenda_number(): void
    {
        $file = UploadedFile::fake()->create('surat_undangan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->stafTu)->post('/surat-masuk', [
            'pengirim' => 'Kementerian Keuangan RI',
            'nomor_surat' => 'S-1234/MK.01/2026',
            'tanggal_surat' => '2026-10-25',
            'tanggal_diterima' => '2026-10-29',
            'perihal' => 'Undangan Sosialisasi Anggaran DIPA Tahun 2027',
            'sifat' => 'penting',
            'jenis' => 'email',
            'klasifikasi_id' => $this->klasifikasi->id,
            'unit_tujuan_id' => $this->unit->id,
            'keterangan' => 'Naskah masuk via email resmi pimpinan.',
            'lampiran' => [$file],
        ]);

        $surat = SuratMasuk::where('nomor_surat', 'S-1234/MK.01/2026')->firstOrFail();
        $response->assertRedirect(route('surat-masuk.show', $surat->id));

        $this->assertStringStartsWith('SM-2026-', $surat->nomor_agenda);
        $this->assertEquals('terdaftar', $surat->status);
        $this->assertEquals('penting', $surat->sifat);
        $this->assertNotEmpty($surat->qr_token);
        $this->assertStringContainsString('S-1234/MK.01/2026', $surat->search_text);

        // Verifikasi lampiran privat
        $this->assertCount(1, $surat->attachments);
        $attachment = $surat->attachments->first();
        $this->assertEquals('surat_undangan.pdf', $attachment->file_name);
        Storage::disk('local')->assertExists($attachment->file_path);

        // Verifikasi audit log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'surat_masuk.created',
            'subject_id' => $surat->id,
            'causer_id' => $this->stafTu->id,
        ]);
    }

    public function test_registration_validation_fails_with_indonesian_messages(): void
    {
        $response = $this->actingAs($this->stafTu)->post('/surat-masuk', [
            'pengirim' => '',
            'nomor_surat' => '',
            'tanggal_surat' => '',
            'tanggal_diterima' => '',
            'perihal' => '',
            'sifat' => 'invalid_sifat',
            'jenis' => 'invalid_jenis',
            'klasifikasi_id' => 99999,
        ]);

        $response->assertSessionHasErrors([
            'pengirim',
            'nomor_surat',
            'tanggal_surat',
            'tanggal_diterima',
            'perihal',
            'sifat',
            'jenis',
            'klasifikasi_id',
        ]);
    }

    public function test_registration_is_rejected_when_storage_quota_is_exceeded(): void
    {
        $license = LicenseState::current();
        // Set batas storage sangat kecil: 100 KB
        $license->update(['max_storage_bytes' => 102400]);

        // Kirim file 500 KB
        $largeFile = UploadedFile::fake()->create('dokumen_besar.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->stafTu)->post('/surat-masuk', [
            'pengirim' => 'Instansi Mitra',
            'nomor_surat' => '001/MITRA/2026',
            'tanggal_surat' => '2026-10-25',
            'tanggal_diterima' => '2026-10-29',
            'perihal' => 'Pengajuan Kerjasama Strategis',
            'sifat' => 'biasa',
            'jenis' => 'fisik',
            'klasifikasi_id' => $this->klasifikasi->id,
            'lampiran' => [$largeFile],
        ]);

        $response->assertSessionHasErrors(['lampiran']);
        $this->assertDatabaseMissing('surat_masuk', [
            'nomor_surat' => '001/MITRA/2026',
        ]);
    }

    public function test_attachment_download_and_preview_are_authorized_and_protected(): void
    {
        $file = UploadedFile::fake()->create('dokumen_rahasia.pdf', 100, 'application/pdf');

        $this->actingAs($this->stafTu)->post('/surat-masuk', [
            'pengirim' => 'Badan Pemeriksa Keuangan',
            'nomor_surat' => 'BPK/01/2026',
            'tanggal_surat' => '2026-10-20',
            'tanggal_diterima' => '2026-10-29',
            'perihal' => 'Hasil Evaluasi Kepatuhan',
            'sifat' => 'rahasia',
            'jenis' => 'fisik',
            'klasifikasi_id' => $this->klasifikasi->id,
            'lampiran' => [$file],
        ]);

        $surat = SuratMasuk::where('nomor_surat', 'BPK/01/2026')->firstOrFail();
        $attachment = $surat->attachments->first();

        // 1. Download oleh staf yang berwenang (staf TU / Direktur)
        $downloadResponse = $this->actingAs($this->direktur)
            ->get("/surat-masuk/{$surat->id}/attachments/{$attachment->id}/download");
        $downloadResponse->assertOk();

        // 2. Preview oleh staf yang berwenang
        $previewResponse = $this->actingAs($this->stafTu)
            ->get("/surat-masuk/{$surat->id}/attachments/{$attachment->id}/preview");
        $previewResponse->assertOk();
        $previewResponse->assertHeader('Content-Type', 'application/pdf');

        // 3. User tanpa login ditolak
        auth()->logout();
        $this->get("/surat-masuk/{$surat->id}/attachments/{$attachment->id}/download")
            ->assertRedirect(route('login'));
    }

    public function test_cancellation_requires_reason_and_updates_status_without_hard_delete(): void
    {
        $surat = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-0099',
            'nomor_surat' => '99/BATAL/X/2026',
            'pengirim' => 'Pengirim Keliru',
            'tanggal_surat' => '2026-10-20',
            'tanggal_diterima' => '2026-10-29',
            'perihal' => 'Surat yang salah registrasi',
            'sifat' => 'biasa',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'klasifikasi_id' => $this->klasifikasi->id,
            'user_id' => $this->stafTu->id,
        ]);

        // Coba batalkan tanpa alasan -> HARUS DITOLAK
        $responseWithoutReason = $this->actingAs($this->stafTu)
            ->post("/surat-masuk/{$surat->id}/cancel", ['alasan_batal' => '']);
        $responseWithoutReason->assertSessionHasErrors(['alasan_batal']);

        // Batalkan dengan alasan resmi
        $responseWithReason = $this->actingAs($this->stafTu)
            ->post("/surat-masuk/{$surat->id}/cancel", [
                'alasan_batal' => 'Salah registrasi unit dan berkas ditarik kembali oleh pengirim.',
            ]);

        $responseWithReason->assertSessionHas('success');
        $surat->refresh();

        // Verifikasi status berubah bukan hard delete
        $this->assertEquals('dibatalkan', $surat->status);
        $this->assertNull($surat->deleted_at);
        $this->assertEquals('Salah registrasi unit dan berkas ditarik kembali oleh pengirim.', $surat->alasan_batal);
        $this->assertEquals($this->stafTu->id, $surat->dibatalkan_oleh_id);
        $this->assertNotNull($surat->dibatalkan_pada);

        // Verifikasi audit log mencatat pembatalan
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'surat_masuk.cancelled',
            'subject_id' => $surat->id,
        ]);
    }

    public function test_printable_lembar_disposisi_and_bukti_terima_generate_pdf(): void
    {
        $surat = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-0005',
            'nomor_surat' => '005/DISP/2026',
            'pengirim' => 'Direktorat Jenderal Pendidikan Vokasi',
            'tanggal_surat' => '2026-10-20',
            'tanggal_diterima' => '2026-10-29',
            'perihal' => 'Undangan Rapat Koordinasi Pimpinan',
            'sifat' => 'segera',
            'jenis' => 'email',
            'status' => 'terdaftar',
            'klasifikasi_id' => $this->klasifikasi->id,
            'user_id' => $this->stafTu->id,
        ]);

        // Lembar Disposisi PDF
        $disposisiPdf = $this->actingAs($this->direktur)
            ->get("/surat-masuk/{$surat->id}/print-disposisi");
        $disposisiPdf->assertOk();
        $disposisiPdf->assertHeader('Content-Type', 'application/pdf');

        // Bukti Terima PDF
        $buktiPdf = $this->actingAs($this->stafTu)
            ->get("/surat-masuk/{$surat->id}/print-bukti");
        $buktiPdf->assertOk();
        $buktiPdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_incoming_letters_list_runs_without_n_plus_one_queries(): void
    {
        // 0. Warm-up request agar permission cache dan session terinisialisasi
        $this->actingAs($this->stafTu)->get('/surat-masuk');

        // 1. Buat 5 surat masuk
        for ($i = 1; $i <= 5; $i++) {
            SuratMasuk::create([
                'nomor_agenda' => sprintf('SM-2026-%04d', $i + 100),
                'nomor_surat' => "NO-{$i}/2026",
                'pengirim' => "Pengirim {$i}",
                'tanggal_surat' => '2026-10-20',
                'tanggal_diterima' => '2026-10-29',
                'perihal' => "Perihal surat masuk ke-{$i}",
                'sifat' => 'biasa',
                'jenis' => 'fisik',
                'status' => 'terdaftar',
                'klasifikasi_id' => $this->klasifikasi->id,
                'unit_tujuan_id' => $this->unit->id,
                'user_id' => $this->stafTu->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->stafTu)->get('/surat-masuk');
        $queryCountWith5 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 2. Tambahkan 10 surat masuk lagi (total 15 data)
        for ($i = 6; $i <= 15; $i++) {
            SuratMasuk::create([
                'nomor_agenda' => sprintf('SM-2026-%04d', $i + 100),
                'nomor_surat' => "NO-{$i}/2026",
                'pengirim' => "Pengirim {$i}",
                'tanggal_surat' => '2026-10-20',
                'tanggal_diterima' => '2026-10-29',
                'perihal' => "Perihal surat masuk ke-{$i}",
                'sifat' => 'biasa',
                'jenis' => 'fisik',
                'status' => 'terdaftar',
                'klasifikasi_id' => $this->klasifikasi->id,
                'unit_tujuan_id' => $this->unit->id,
                'user_id' => $this->stafTu->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->stafTu)->get('/surat-masuk');
        $queryCountWith15 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Buktikan jumlah query konstan (O(1)), tidak bertambah seiring bertambahnya data baris
        $this->assertEquals(
            $queryCountWith5,
            $queryCountWith15,
            "Terdeteksi N+1 query issue! Query bertambah dari {$queryCountWith5} menjadi {$queryCountWith15} saat data bertambah."
        );
    }
}
