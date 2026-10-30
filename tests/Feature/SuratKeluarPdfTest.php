<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\GenerateSuratKeluarPdfAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratKeluarPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $signer;
    protected OrgUnit $unit;
    protected Position $signerPosition;
    protected KlasifikasiSurat $klasifikasi;
    protected TemplateSurat $template;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        InstitutionProfile::current()->update([
            'name' => 'Politeknik Negeri Digital Nusantara',
            'address' => 'Jl. Teknologi No. 42, Kota Bandung',
            'email' => 'persuratan@pndn.ac.id',
            'phone' => '(022) 87654321',
        ]);

        $this->unit = OrgUnit::create([
            'name' => 'Jurusan Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'type' => 'academic',
        ]);

        $this->signerPosition = Position::create([
            'org_unit_id' => $this->unit->id,
            'name' => 'Ketua Jurusan RPL',
            'level' => 2,
        ]);

        $this->creator = User::factory()->create(['name' => 'Staff Administrasi TU']);
        $this->signer = User::factory()->create([
            'name' => 'Dr. Ir. Budi Santoso, M.T.',
            'nip' => '197505122000031001',
            'position_id' => $this->signerPosition->id,
        ]);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.02',
            'nama' => 'Kerjasama dan Kemitraan',
        ]);

        $this->template = TemplateSurat::create([
            'kode' => 'ST-MITRA',
            'nama' => 'Surat Tugas Kemitraan Industri',
            'jenis' => TemplateSurat::JENIS_SURAT_TUGAS,
            'konten_default' => '<p>Diberikan tugas kepada dosen untuk kunjungan industri kemitraan.</p>',
            'format_nomor' => '{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}',
            'is_active' => true,
        ]);
    }

    public function test_generate_pdf_produces_valid_pdf_binary_with_header_and_signature_placeholder(): void
    {
        $saveDraft = app(SaveDraftSuratKeluarAction::class);
        $allocateNomor = app(AllocateNomorSuratKeluarAction::class);
        $generatePdf = app(GenerateSuratKeluarPdfAction::class);

        $suratKeluar = $saveDraft->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Surat Tugas Kunjungan Industri PT Inovasi Solusi',
            'tujuan' => 'Pimpinan PT Inovasi Solusi',
            'isi_surat' => '<p>Dengan ini menugaskan staf untuk melaksanakan audiensi kemitraan industri.</p>',
            'tanggal_surat' => '2026-10-30',
        ]);

        // Beri persetujuan dan nomor resmi
        $suratKeluar->update([
            'status' => SuratKeluar::STATUS_DISETUJUI,
            'status_approval' => 'approved',
        ]);
        $suratKeluar = $allocateNomor->execute($suratKeluar, $this->signer);

        $this->assertTrue($suratKeluar->isBernomor());
        $this->assertEquals('1/RPL/HM.02/X/2026', $suratKeluar->nomor_surat);

        // Eksekusi pembuatan PDF
        $result = $generatePdf->execute($suratKeluar, true);

        // 1. Verifikasi binary PDF
        $this->assertNotEmpty($result['pdf_binary']);
        $this->assertStringStartsWith('%PDF-', $result['pdf_binary'], 'Output harus merupakan berkas binary PDF yang valid.');

        // 2. Verifikasi berkas tersimpan di storage
        $this->assertNotNull($result['file_path']);
        Storage::disk('public')->assertExists($result['file_path']);
        $this->assertEquals($result['file_path'], $suratKeluar->fresh()->file_pdf);

        // 3. Verifikasi view rendered HTML memuat kop instansi, nomor resmi, dan placeholder tanda tangan Fase 3B
        $view = view('pdf.surat-keluar', [
            'surat' => $suratKeluar,
            'institution' => InstitutionProfile::first(),
            'renderedContent' => '<p>Dengan ini menugaskan staf untuk melaksanakan audiensi kemitraan industri.</p>',
            'jabatanPenandatangan' => 'Ketua Jurusan RPL',
            'namaPenandatangan' => 'Dr. Ir. Budi Santoso, M.T.',
            'tanggalFormatted' => '30 Oktober 2026',
        ])->render();

        // Kop Instansi
        $this->assertStringContainsString('Politeknik Negeri Digital Nusantara', $view);
        $this->assertStringContainsString('Jl. Teknologi No. 42, Kota Bandung', $view);

        // Nomor Surat
        $this->assertStringContainsString('1/RPL/HM.02/X/2026', $view);

        // Tujuan & Perihal
        $this->assertStringContainsString('Pimpinan PT Inovasi Solusi', $view);
        $this->assertStringContainsString('Surat Tugas Kunjungan Industri PT Inovasi Solusi', $view);

        // Placeholder Tanda Tangan Fase 3B
        $this->assertStringContainsString('[AREA TANDA TANGAN ELEKTRONIK / BASAH]', $view);
        $this->assertStringContainsString('Fase 3B: TTE / Segel Elektronik', $view);
        $this->assertStringContainsString('Dr. Ir. Budi Santoso, M.T.', $view);
        $this->assertStringContainsString('197505122000031001', $view);
    }

    public function test_pdf_generation_blocks_remote_assets_and_restricts_chroot(): void
    {
        $saveDraft = app(SaveDraftSuratKeluarAction::class);
        $generatePdf = app(GenerateSuratKeluarPdfAction::class);

        // Uji coba penyisipan aset jarak jauh dan berkas di luar chroot
        $maliciousContent = <<<HTML
            <p>Naskah Uji Keamanan Dompdf</p>
            <img src="http://127.0.0.1:5433/probe-network-image.png" alt="probe-remote">
            <img src="file:///etc/passwd" alt="probe-chroot">
            <link rel="stylesheet" href="http://evil.com/untrusted.css">
HTML;

        $suratKeluar = $saveDraft->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Uji Sandbox Dompdf',
            'tujuan' => 'Tim Keamanan Siber',
            'isi_surat' => $maliciousContent,
            'tanggal_surat' => '2026-10-30',
        ]);

        $result = $generatePdf->execute($suratKeluar, false);

        $this->assertNotEmpty($result['pdf_binary']);
        $this->assertStringStartsWith('%PDF-', $result['pdf_binary']);

        // Pastikan tidak ada isi /etc/passwd seperti root:x:0:0 atau konten remote
        $this->assertStringNotContainsString('root:x:0:0', $result['pdf_binary']);
        $this->assertStringNotContainsString('probe-network-image', $result['pdf_binary']);
    }
}
