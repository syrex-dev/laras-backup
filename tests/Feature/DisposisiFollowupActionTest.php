<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Actions\FollowupDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiFollowupActionTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajur;
    private Position $posDirektur;
    private Position $posKajur;
    private SuratMasuk $suratMasuk;
    private Disposisi $disposisi;
    private DisposisiRecipient $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.teruskan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.tindaklanjut', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['disposisi.buat', 'disposisi.lihat', 'disposisi.teruskan']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['disposisi.lihat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        $unitDir = OrgUnit::create(['name' => 'Direktorat', 'code' => 'DIR', 'type' => 'pimpinan']);
        $unitJtk = OrgUnit::create(['name' => 'Jurusan Komputer', 'code' => 'JTK', 'type' => 'jurusan', 'parent_id' => $unitDir->id]);

        $this->posDirektur = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitDir->id, 'level' => 1]);
        $this->posKajur = Position::create(['name' => 'Ketua Jurusan TI', 'org_unit_id' => $unitJtk->id, 'level' => 2]);

        $this->direktur = User::factory()->create([
            'name' => 'Direktur',
            'email' => 'direktur@test.ac.id',
            'position_id' => $this->posDirektur->id,
            'is_active' => true,
        ]);
        $this->direktur->assignRole($rolePimpinan);

        $this->kajur = User::factory()->create([
            'name' => 'Kajur TI',
            'email' => 'kajur@test.ac.id',
            'position_id' => $this->posKajur->id,
            'is_active' => true,
        ]);
        $this->kajur->assignRole($roleKepalaUnit);

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'TU.01',
            'nama' => 'Tata Usaha Umum',
            'keterangan' => 'Arsip umum',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000300',
            'nomor_surat' => '300/SET/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Kementerian Keuangan',
            'perihal' => 'Pengajuan Anggaran Tambahan TA 2026',
            'sifat' => 'segera',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);

        $createAction = new CreateDisposisiAction();
        $this->disposisi = $createAction->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Siapkan rekapitulasi usulan kebutuhan anggaran sarana laboratorium',
            'tenggat_waktu' => now()->addDays(5)->toDateString(),
            'prioritas' => Disposisi::PRIORITAS_SEGERA,
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);

        $this->recipient = $this->disposisi->recipients->first();
    }

    public function test_recipient_can_process_and_complete_disposition_with_proof_attachment(): void
    {
        $action = new FollowupDisposisiAction();

        // 1. Tahap Diproses
        $followup1 = $action->execute($this->kajur, $this->recipient, [
            'status' => DisposisiRecipient::STATUS_DIPROSES,
            'catatan' => 'Sedang menyusun rincian spesifikasi peralatan laboratorium bersama para kaprodi.',
        ]);

        $this->assertEquals(DisposisiRecipient::STATUS_DIPROSES, $this->recipient->fresh()->status);
        $this->assertNotNull($this->recipient->fresh()->dibaca_pada);
        $this->assertNull($this->recipient->fresh()->selesai_pada);
        $this->assertEquals('didisposisikan', $this->suratMasuk->fresh()->status);

        // 2. Tahap Selesai dengan unggahan berkas bukti penyelesaian
        $fakePdf = UploadedFile::fake()->create('laporan_kebutuhan_anggaran.pdf', 300, 'application/pdf');

        $followup2 = $action->execute(
            $this->kajur,
            $this->recipient->fresh(),
            [
                'status' => DisposisiRecipient::STATUS_SELESAI,
                'catatan' => 'Seluruh usulan anggaran telah selesai disusun dan disetujui dalam rapat jurusan.',
            ],
            $fakePdf
        );

        $freshRecipient = $this->recipient->fresh();
        $this->assertEquals(DisposisiRecipient::STATUS_SELESAI, $freshRecipient->status);
        $this->assertNotNull($freshRecipient->selesai_pada);

        // Verifikasi berkas tersimpan di disk privat
        $this->assertEquals(1, $followup2->attachments->count());
        $attachment = $followup2->attachments->first();
        $this->assertEquals('laporan_kebutuhan_anggaran.pdf', $attachment->file_name);
        Storage::disk('local')->assertExists($attachment->file_path);

        // Seluruh penerima selesai -> Disposisi selesai -> Surat Masuk selesai
        $this->assertEquals(Disposisi::STATUS_SELESAI, $this->disposisi->fresh()->status);
        $this->assertEquals('selesai', $this->suratMasuk->fresh()->status);

        // Jejak audit tercatat
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'disposisi.followup',
            'subject_id' => $this->recipient->id,
            'causer_id' => $this->kajur->id,
        ]);
    }

    public function test_uploading_attachment_exceeding_storage_quota_is_rejected(): void
    {
        // Set kuota kecil (misal 50 KB)
        $license = LicenseState::current();
        $license->update(['max_storage_bytes' => 50000]); // 50 KB

        $action = new FollowupDisposisiAction();

        // Unggah berkas 200 KB yang melebihi kuota 50 KB
        $largeFile = UploadedFile::fake()->create('berkas_besar.pdf', 200, 'application/pdf');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kapasitas penyimpanan telah melebihi batas kuota');

        $action->execute(
            $this->kajur,
            $this->recipient,
            [
                'status' => DisposisiRecipient::STATUS_DIPROSES,
                'catatan' => 'Unggah dokumen besar',
            ],
            $largeFile
        );
    }
}
