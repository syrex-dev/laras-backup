<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\CreateReplyFromDisposisiAction;
use App\Domain\SuratKeluar\Actions\ResolveDisposisiFromReplyAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SuratKeluarReplyDisposisiTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $recipientUser;
    protected OrgUnit $unit;
    protected Position $posLeader;
    protected Position $posRecipient;
    protected KlasifikasiSurat $klasifikasi;
    protected SuratMasuk $suratMasuk;
    protected Disposisi $disposisi;
    protected DisposisiRecipient $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        InstitutionProfile::current();

        $this->unit = OrgUnit::create([
            'name' => 'Jurusan Teknik Informatika',
            'code' => 'TI',
            'type' => 'academic',
        ]);

        $this->posLeader = Position::create([
            'org_unit_id' => $this->unit->id,
            'name' => 'Direktur Politeknik',
            'level' => 1,
            'is_penandatangan' => true,
        ]);

        $this->posRecipient = Position::create([
            'org_unit_id' => $this->unit->id,
            'name' => 'Ketua Jurusan TI',
            'level' => 2,
        ]);

        $this->leader = User::factory()->create([
            'name' => 'Prof. Dr. Direktur',
            'position_id' => $this->posLeader->id,
        ]);

        $this->recipientUser = User::factory()->create([
            'name' => 'Dr. Kajur TI',
            'position_id' => $this->posRecipient->id,
        ]);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Kerjasama Antar Lembaga',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'user_id' => $this->leader->id,
            'nomor_surat' => '055/EXT-MITRA/IX/2026',
            'asal_surat' => 'PT Mitra Teknologi Solusi',
            'pengirim' => 'Direktur Utama PT Mitra',
            'perihal' => 'Penawaran Kerjasama Magang dan Riset',
            'tanggal_surat' => '2026-09-15',
            'tanggal_terima' => '2026-09-16',
            'nomor_agenda' => 'SM-2026-0001',
            'sifat' => 'penting',
            'status' => 'didisposisikan',
            'klasifikasi_id' => $this->klasifikasi->id,
        ]);

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        $this->leader->givePermissionTo('disposisi.buat');

        // Buat disposisi dari pimpinan ke penerima
        $actionCreateDisposisi = app(CreateDisposisiAction::class);
        $this->disposisi = $actionCreateDisposisi->execute($this->leader, $this->suratMasuk, [
            'instruksi' => 'Tindak lanjuti dengan surat balasan resmi',
            'tenggat_waktu' => '2026-11-10',
            'prioritas' => 'segera',
            'recipients' => [
                [
                    'user_id' => $this->recipientUser->id,
                    'position_id' => $this->posRecipient->id,
                    'unit_id' => $this->unit->id,
                ],
            ],
        ]);

        $this->recipient = $this->disposisi->recipients->first();
    }

    public function test_create_reply_from_disposisi_initializes_draft_and_links_oq10(): void
    {
        $actionCreateReply = app(CreateReplyFromDisposisiAction::class);

        $suratKeluar = $actionCreateReply->execute($this->recipientUser, $this->recipient, [
            'perihal' => 'Surat Balasan: Kesediaan Kerjasama Magang dan Riset',
        ]);

        // 1. Verifikasi data surat keluar yang dihasilkan
        $this->assertEquals(SuratKeluar::STATUS_DRAF, $suratKeluar->status);
        $this->assertEquals('Surat Balasan: Kesediaan Kerjasama Magang dan Riset', $suratKeluar->perihal);
        $this->assertEquals('Direktur Utama PT Mitra', $suratKeluar->tujuan);
        $this->assertEquals($this->suratMasuk->id, $suratKeluar->reply_to_surat_masuk_id);
        $this->assertEquals($this->recipientUser->id, $suratKeluar->user_id);

        // 2. Verifikasi aktivasi tautan OQ-10 di disposisi_recipients
        $this->recipient->refresh();
        $this->assertEquals($suratKeluar->id, $this->recipient->surat_keluar_id);
        $this->assertEquals(DisposisiRecipient::STATUS_DIPROSES, $this->recipient->status);

        // Relasi Eloquent dua arah
        $this->assertEquals($suratKeluar->id, $this->recipient->suratKeluar->id);
        $this->assertEquals($this->recipient->id, $suratKeluar->disposisiRecipient->id);

        // 3. Verifikasi riwayat draf v1
        $this->assertCount(1, $suratKeluar->versions);
        $this->assertEquals(1, $suratKeluar->versions->first()->version_number);

        // 4. Verifikasi activity log audit
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'disposisi.create_reply',
            'subject_id' => $suratKeluar->id,
            'causer_id' => $this->recipientUser->id,
        ]);
    }

    public function test_resolve_disposisi_requires_letter_to_be_numbered(): void
    {
        $actionCreateReply = app(CreateReplyFromDisposisiAction::class);
        $actionResolve = app(ResolveDisposisiFromReplyAction::class);

        $suratKeluar = $actionCreateReply->execute($this->recipientUser, $this->recipient);

        // Status masih draf (belum bernomor) -> harus gagal
        $this->expectException(ValidationException::class);
        $actionResolve->execute($this->recipientUser, $suratKeluar);
    }

    public function test_resolve_disposisi_marks_recipient_and_parent_disposisi_completed(): void
    {
        $actionCreateReply = app(CreateReplyFromDisposisiAction::class);
        $actionAllocate = app(AllocateNomorSuratKeluarAction::class);
        $actionResolve = app(ResolveDisposisiFromReplyAction::class);

        $suratKeluar = $actionCreateReply->execute($this->recipientUser, $this->recipient);

        // Simulasikan persetujuan berjenjang dan alokasi nomor resmi
        $suratKeluar->update([
            'status' => SuratKeluar::STATUS_DISETUJUI,
            'status_approval' => 'approved',
            'penandatangan_id' => $this->leader->id,
        ]);

        $suratKeluar = $actionAllocate->execute($suratKeluar, $this->leader);
        $this->assertTrue($suratKeluar->isBernomor());
        $this->assertNotEmpty($suratKeluar->nomor_surat);

        // Selesaikan tindak lanjut disposisi dengan konfirmasi
        $resolvedRecipient = $actionResolve->execute(
            $this->recipientUser,
            $suratKeluar,
            'Surat balasan resmi telah terbit dan siap dikirimkan kepada mitra.'
        );

        // 1. Verifikasi recipient berstatus selesai
        $this->assertEquals(DisposisiRecipient::STATUS_SELESAI, $resolvedRecipient->status);
        $this->assertNotNull($resolvedRecipient->selesai_pada);
        $this->assertStringContainsString('Surat balasan resmi telah terbit', $resolvedRecipient->catatan_tindak_lanjut);

        // 2. Verifikasi parent disposisi otomatis selesai (karena seluruh penerima sudah selesai)
        $this->disposisi->refresh();
        $this->assertEquals(Disposisi::STATUS_SELESAI, $this->disposisi->status);
        $this->assertNotNull($this->disposisi->selesai_pada);

        // 3. Verifikasi activity log audit
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'disposisi.resolve_by_reply',
            'subject_id' => $suratKeluar->id,
            'causer_id' => $this->recipientUser->id,
        ]);
    }
}
