<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\ActivityLog;
use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiCreateActionTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajur;
    private User $kaprodi;
    private Position $posDirektur;
    private Position $posKajur;
    private Position $posKaprodi;
    private SuratMasuk $suratMasuk;
    private InstructionTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Permissions & Roles
        Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.teruskan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.tindaklanjut', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['disposisi.buat', 'disposisi.lihat', 'disposisi.teruskan']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['disposisi.lihat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        // 2. Units & Positions
        $unitDir = OrgUnit::create(['name' => 'Direktorat', 'code' => 'DIR', 'type' => 'pimpinan']);
        $unitJtk = OrgUnit::create(['name' => 'Jurusan Komputer', 'code' => 'JTK', 'type' => 'jurusan', 'parent_id' => $unitDir->id]);
        $unitTi = OrgUnit::create(['name' => 'Prodi TI', 'code' => 'TI', 'type' => 'prodi', 'parent_id' => $unitJtk->id]);

        $this->posDirektur = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitDir->id, 'level' => 1]);
        $this->posKajur = Position::create(['name' => 'Kajur', 'org_unit_id' => $unitJtk->id, 'level' => 2]);
        $this->posKaprodi = Position::create(['name' => 'Kaprodi', 'org_unit_id' => $unitTi->id, 'level' => 3]);

        // 3. Users
        $this->direktur = User::factory()->create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@test.ac.id',
            'position_id' => $this->posDirektur->id,
            'is_active' => true,
        ]);
        $this->direktur->assignRole($rolePimpinan);

        $this->kajur = User::factory()->create([
            'name' => 'Ketua Jurusan',
            'email' => 'kajur@test.ac.id',
            'position_id' => $this->posKajur->id,
            'is_active' => true,
        ]);
        $this->kajur->assignRole($roleKepalaUnit);

        $this->kaprodi = User::factory()->create([
            'name' => 'Ketua Prodi TI',
            'email' => 'kaprodi@test.ac.id',
            'position_id' => $this->posKaprodi->id,
            'is_active' => true,
        ]);
        $this->kaprodi->assignRole($roleKepalaUnit);

        // 4. Surat Masuk
        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Kerjasama Akademik',
            'keterangan' => 'Arsip kerjasama',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000001',
            'nomor_surat' => '001/UND/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Kementerian Dikti',
            'perihal' => 'Undangan Rapat Koordinasi Vokasi',
            'sifat' => 'penting',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);

        // 5. Instruction Template
        $this->template = InstructionTemplate::create([
            'kode' => 'TL',
            'nama' => 'Tindak lanjuti segera',
            'deskripsi' => 'Instruksi pemrosesan cepat',
            'urutan' => 1,
            'is_active' => true,
        ]);
    }

    public function test_pimpinan_can_create_disposition_to_multiple_recipients_and_status_transitions(): void
    {
        $action = new CreateDisposisiAction();

        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruction_template_id' => $this->template->id,
            'instruksi' => 'Siapkan delegasi dosen yang berkompeten',
            'catatan' => 'Harap koordinasikan dengan Wadir 1',
            'tenggat_waktu' => now()->addDays(3)->toDateString(),
            'prioritas' => Disposisi::PRIORITAS_SEGERA,
            'recipients' => [
                ['position_id' => $this->posKajur->id],
                ['user_id' => $this->kaprodi->id],
            ],
        ]);

        $this->assertInstanceOf(Disposisi::class, $disposisi);
        $this->assertEquals(Disposisi::STATUS_DIDISPOSISIKAN, $disposisi->status);
        $this->assertEquals(Disposisi::PRIORITAS_SEGERA, $disposisi->prioritas);
        $this->assertStringContainsString('Tindak lanjuti segera', $disposisi->instruksi);
        $this->assertStringContainsString('Siapkan delegasi dosen', $disposisi->instruksi);

        // Status surat masuk berubah menjadi didisposisikan
        $this->assertEquals('didisposisikan', $this->suratMasuk->fresh()->status);

        // Recipient count
        $this->assertCount(2, $disposisi->recipients);

        // Recipient 1 (via position)
        $r1 = $disposisi->recipients->firstWhere('recipient_position_id', $this->posKajur->id);
        $this->assertNotNull($r1);
        $this->assertEquals($this->kajur->id, $r1->recipient_user_id);
        $this->assertEquals(DisposisiRecipient::STATUS_BELUM_DIBACA, $r1->status);

        // Recipient 2 (via user)
        $r2 = $disposisi->recipients->firstWhere('recipient_user_id', $this->kaprodi->id);
        $this->assertNotNull($r2);
        $this->assertEquals($this->posKaprodi->id, $r2->recipient_position_id);

        // Audit log tercatat
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'disposisi.created',
            'subject_type' => Disposisi::class,
            'subject_id' => $disposisi->id,
            'causer_id' => $this->direktur->id,
        ]);
    }

    public function test_self_disposition_is_strictly_rejected(): void
    {
        $action = new CreateDisposisiAction();

        $this->expectException(ValidationException::class);

        $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Proses sendiri',
            'recipients' => [
                ['user_id' => $this->direktur->id],
            ],
        ]);
    }

    public function test_self_disposition_via_position_is_strictly_rejected(): void
    {
        $action = new CreateDisposisiAction();

        $this->expectException(ValidationException::class);

        $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Proses sendiri',
            'recipients' => [
                ['position_id' => $this->posDirektur->id],
            ],
        ]);
    }

    public function test_inactive_recipient_is_rejected(): void
    {
        $inactiveUser = User::factory()->create([
            'name' => 'Pegawai Cuti',
            'email' => 'cuti@test.ac.id',
            'is_active' => false,
        ]);

        $action = new CreateDisposisiAction();

        $this->expectException(ValidationException::class);

        $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Harap kerjakan',
            'recipients' => [
                ['user_id' => $inactiveUser->id],
            ],
        ]);
    }

    public function test_unauthorized_user_without_permission_is_rejected(): void
    {
        $regularUser = User::factory()->create(['is_active' => true]);

        $action = new CreateDisposisiAction();

        $this->expectException(AuthorizationException::class);

        $action->execute($regularUser, $this->suratMasuk, [
            'instruksi' => 'Disposisi ilegal',
            'recipients' => [
                ['user_id' => $this->kajur->id],
            ],
        ]);
    }
}
