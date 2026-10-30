<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\ActivityLog;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Instansi\Models\SystemSetting;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\CancelSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratKeluar\Services\NomorSuratKeluarService;
use App\Domain\SuratMasuk\Models\NumberingSequence;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SuratKeluarNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $signer;
    protected OrgUnit $unitJti;
    protected KlasifikasiSurat $klasifikasi;
    protected TemplateSurat $template;

    protected function setUp(): void
    {
        parent::setUp();

        InstitutionProfile::current();

        $this->unitJti = OrgUnit::create([
            'name' => 'Jurusan Teknologi Informasi',
            'code' => 'JTI',
            'type' => 'academic',
        ]);

        $this->creator = User::factory()->create(['name' => 'Dosen Pengaju']);
        $this->signer = User::factory()->create(['name' => 'Direktur Utama']);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'OT.01',
            'nama' => 'Organisasi dan Tata Kerja',
        ]);

        $this->template = TemplateSurat::create([
            'kode' => 'ND-TEST',
            'nama' => 'Nota Dinas Internal',
            'jenis' => TemplateSurat::JENIS_NOTA_DINAS,
            'konten_default' => '<p>Perihal: {perihal}</p>',
            'format_nomor' => '{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}',
            'is_active' => true,
        ]);
    }

    public function test_format_nomor_resolves_all_standard_tokens(): void
    {
        $service = app(NomorSuratKeluarService::class);

        $context = [
            'kode_unit' => 'JTI',
            'kode_klasifikasi' => 'OT.01',
            'bulan_romawi' => 'X',
            'bulan' => '10',
            'tahun' => 2026,
        ];

        // Format standar e-office
        $pattern = '{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}';
        $formatted = $service->formatNomor(1, $context, $pattern);
        $this->assertEquals('1/JTI/OT.01/X/2026', $formatted);

        // Format dengan padding
        $paddedPattern = '{urut:4}/{kode_unit}/{bulan_romawi}/{tahun}';
        $formattedPadded = $service->formatNomor(42, $context, $paddedPattern);
        $this->assertEquals('0042/JTI/X/2026', $formattedPadded);
    }

    public function test_number_can_only_be_allocated_when_letter_is_approved(): void
    {
        $actionSave = app(SaveDraftSuratKeluarAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);

        $suratKeluar = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Pengajuan Pengadaan Server',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>Mohon disetujui pengadaan server.</p>',
            'tanggal_surat' => '2026-10-30',
        ]);

        $this->assertEquals(SuratKeluar::STATUS_DRAF, $suratKeluar->status);

        // 1. Gagal jika masih draf
        $this->expectException(ValidationException::class);
        $actionAllocate->execute($suratKeluar, $this->signer);
    }

    public function test_allocate_number_success_on_approved_status(): void
    {
        $actionSave = app(SaveDraftSuratKeluarAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);

        $suratKeluar = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Nota Dinas Pembelian Komputer',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>Rincian komputer baru.</p>',
            'tanggal_surat' => '2026-10-30',
        ]);

        // Simulasikan persetujuan akhir
        $suratKeluar->update([
            'status' => SuratKeluar::STATUS_DISETUJUI,
            'status_approval' => 'approved',
        ]);

        $allocated = $actionAllocate->execute($suratKeluar, $this->signer);

        $this->assertEquals(SuratKeluar::STATUS_BERNOMOR, $allocated->status);
        $this->assertEquals('1/JTI/OT.01/X/2026', $allocated->nomor_surat);
        $this->assertTrue($allocated->isBernomor());

        // Verifikasi activity log audit
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'surat_keluar.number_allocated',
            'subject_id' => $suratKeluar->id,
        ]);
    }

    public function test_cannot_reallocate_number_to_already_numbered_letter(): void
    {
        $actionSave = app(SaveDraftSuratKeluarAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);

        $suratKeluar = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Pengajuan Workshop',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>Workshop AI</p>',
            'tanggal_surat' => '2026-10-30',
        ]);

        $suratKeluar->update(['status' => SuratKeluar::STATUS_DISETUJUI]);
        $actionAllocate->execute($suratKeluar, $this->signer);

        // Percobaan alokasi kedua harus ditolak
        $this->expectException(ValidationException::class);
        $actionAllocate->execute($suratKeluar, $this->signer);
    }

    public function test_transaction_rollback_does_not_consume_or_gap_number(): void
    {
        $unitCode = 'JTI';
        $year = 2026;

        // Pastikan sequence bersih
        NumberingSequence::where('type', 'surat_keluar')
            ->where('year', $year)
            ->where('unit_code', $unitCode)
            ->delete();

        // 1. Simulasikan transaksi yang gagal/dibatalkan setelah mengambil nomor
        try {
            DB::transaction(function () use ($unitCode, $year) {
                $val = NumberingSequence::nextVal('surat_keluar', $year, $unitCode, 'SK');
                $this->assertEquals(1, $val);
                throw new \RuntimeException('Database constraint error or business logic failure!');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        // 2. Transaksi baru harus mendapatkan nomor 1 KEMBALI (tidak terbuang/tanpa celah)
        $nextVal = DB::transaction(function () use ($unitCode, $year) {
            return NumberingSequence::nextVal('surat_keluar', $year, $unitCode, 'SK');
        });

        $this->assertEquals(1, $nextVal, 'Nomor harus kembali ke 1 setelah rollback transaksi (anti-gap).');
    }

    public function test_cancellation_preserves_number_records_reason_and_audit(): void
    {
        $actionSave = app(SaveDraftSuratKeluarAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);
        $actionCancel = app(CancelSuratKeluarAction::class);

        $suratKeluar = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Surat Pembelian Server Batal',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>Dibatalkan</p>',
            'tanggal_surat' => '2026-10-30',
        ]);

        $suratKeluar->update(['status' => SuratKeluar::STATUS_DISETUJUI]);
        $actionAllocate->execute($suratKeluar, $this->signer);

        $allocatedNumber = $suratKeluar->nomor_surat;
        $this->assertNotEmpty($allocatedNumber);

        // Batalkan surat bernomor
        $cancelled = $actionCancel->execute($this->signer, $suratKeluar, 'Anggaran dibatalkan oleh kementerian pusat');

        $this->assertEquals(SuratKeluar::STATUS_DIBATALKAN, $cancelled->status);
        $this->assertEquals('Anggaran dibatalkan oleh kementerian pusat', $cancelled->alasan_batal);
        $this->assertEquals($allocatedNumber, $cancelled->nomor_surat, 'Nomor surat yang telah terbit tetap tersimpan untuk audit.');

        // Audit terdata
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'surat_keluar.cancel',
            'subject_id' => $suratKeluar->id,
        ]);

        // Naskah berikutnya harus mendapat nomor berikutnya (nomor yang dibatalkan tidak dipakai ulang)
        $suratBaru = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Surat Pengganti',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>Baru</p>',
            'tanggal_surat' => '2026-10-30',
        ]);
        $suratBaru->update(['status' => SuratKeluar::STATUS_DISETUJUI]);
        $actionAllocate->execute($suratBaru, $this->signer);

        $this->assertEquals('2/JTI/OT.01/X/2026', $suratBaru->nomor_surat, 'Nomor tidak boleh memakai ulang nomor yang dibatalkan.');
    }

    public function test_sequence_resets_per_year(): void
    {
        $service = app(NomorSuratKeluarService::class);
        $actionSave = app(SaveDraftSuratKeluarAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);

        // Surat tahun 2026
        $surat2026 = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Surat Akhir 2026',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>2026</p>',
            'tanggal_surat' => '2026-12-31',
        ]);
        $surat2026->update(['status' => SuratKeluar::STATUS_DISETUJUI]);
        $actionAllocate->execute($surat2026, $this->signer, Carbon::parse('2026-12-31'));

        $this->assertEquals('1/JTI/OT.01/XII/2026', $surat2026->nomor_surat);

        // Surat tahun 2027: sequence harus reset ke 1
        $surat2027 = $actionSave->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitJti->id,
            'penandatangan_id' => $this->signer->id,
            'perihal' => 'Surat Awal 2027',
            'tujuan' => 'Direktur',
            'isi_surat' => '<p>2027</p>',
            'tanggal_surat' => '2027-01-05',
        ]);
        $surat2027->update(['status' => SuratKeluar::STATUS_DISETUJUI]);
        $actionAllocate->execute($surat2027, $this->signer, Carbon::parse('2027-01-05'));

        $this->assertEquals('1/JTI/OT.01/I/2027', $surat2027->nomor_surat, 'Sequence harus reset ke 1 pada pergantian tahun.');
    }
}
