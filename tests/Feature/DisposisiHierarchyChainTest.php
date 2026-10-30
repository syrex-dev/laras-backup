<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Actions\ForwardDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiHierarchyChainTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajurJtk;
    private User $kaprodiTi;
    private User $stafTi;
    private User $kajurMesin;
    private Position $posDirektur;
    private Position $posKajurJtk;
    private Position $posKaprodiTi;
    private Position $posStafTi;
    private Position $posKajurMesin;
    private SuratMasuk $suratMasuk;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Permissions
        Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.teruskan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.tindaklanjut', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['disposisi.buat', 'disposisi.lihat', 'disposisi.teruskan']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['disposisi.lihat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        $roleStaf = Role::firstOrCreate(['name' => 'Staf', 'guard_name' => 'web']);
        $roleStaf->givePermissionTo(['disposisi.lihat', 'disposisi.tindaklanjut']);

        // 2. Units (Direktorat -> JTK -> D4-TI, dan unit paralel JTM)
        $unitDir = OrgUnit::create(['name' => 'Direktorat', 'code' => 'DIR', 'type' => 'pimpinan']);
        $unitJtk = OrgUnit::create(['name' => 'Jurusan Komputer', 'code' => 'JTK', 'type' => 'jurusan', 'parent_id' => $unitDir->id]);
        $unitTi = OrgUnit::create(['name' => 'Prodi TI', 'code' => 'D4-TI', 'type' => 'prodi', 'parent_id' => $unitJtk->id]);
        $unitMesin = OrgUnit::create(['name' => 'Jurusan Mesin', 'code' => 'JTM', 'type' => 'jurusan', 'parent_id' => $unitDir->id]);

        // 3. Positions
        $this->posDirektur = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitDir->id, 'level' => 1]);
        $this->posKajurJtk = Position::create(['name' => 'Kajur Komputer', 'org_unit_id' => $unitJtk->id, 'level' => 2]);
        $this->posKaprodiTi = Position::create(['name' => 'Kaprodi TI', 'org_unit_id' => $unitTi->id, 'level' => 3]);
        $this->posStafTi = Position::create(['name' => 'Staf Pengajar TI', 'org_unit_id' => $unitTi->id, 'level' => 4]);
        $this->posKajurMesin = Position::create(['name' => 'Kajur Mesin', 'org_unit_id' => $unitMesin->id, 'level' => 2]);

        // 4. Users
        $this->direktur = User::factory()->create([
            'name' => 'Dr. Direktur',
            'email' => 'direktur@poltek.ac.id',
            'position_id' => $this->posDirektur->id,
            'is_active' => true,
        ]);
        $this->direktur->assignRole($rolePimpinan);

        $this->kajurJtk = User::factory()->create([
            'name' => 'Budi Kajur Komputer',
            'email' => 'kajur.jtk@poltek.ac.id',
            'position_id' => $this->posKajurJtk->id,
            'is_active' => true,
        ]);
        $this->kajurJtk->assignRole($roleKepalaUnit);

        $this->kaprodiTi = User::factory()->create([
            'name' => 'Siti Kaprodi TI',
            'email' => 'kaprodi.ti@poltek.ac.id',
            'position_id' => $this->posKaprodiTi->id,
            'is_active' => true,
        ]);
        $this->kaprodiTi->assignRole($roleKepalaUnit);

        $this->stafTi = User::factory()->create([
            'name' => 'Ahmad Staf TI',
            'email' => 'staf.ti@poltek.ac.id',
            'position_id' => $this->posStafTi->id,
            'is_active' => true,
        ]);
        $this->stafTi->assignRole($roleStaf);

        $this->kajurMesin = User::factory()->create([
            'name' => 'Joko Kajur Mesin',
            'email' => 'kajur.mesin@poltek.ac.id',
            'position_id' => $this->posKajurMesin->id,
            'is_active' => true,
        ]);
        $this->kajurMesin->assignRole($roleKepalaUnit);

        // 5. Surat Masuk
        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'PP.01',
            'nama' => 'Pendidikan dan Pengajaran',
            'keterangan' => 'Arsip kurikulum',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000100',
            'nomor_surat' => '100/DIKTI/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Direktorat Jenderal Vokasi',
            'perihal' => 'Pengembangan Kurikulum Merdeka Vokasi 2026',
            'sifat' => 'segera',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);
    }

    public function test_three_tier_hierarchy_disposition_chain_succeeds(): void
    {
        $createAction = new CreateDisposisiAction();
        $forwardAction = new ForwardDisposisiAction();

        // -------------------------------------------------------------
        // Tingkat 1: Direktur -> Kajur JTK
        // -------------------------------------------------------------
        $dispTingkat1 = $createAction->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Harap dikaji dan siapkan bahan kurikulum baru',
            'tenggat_waktu' => now()->addDays(7)->toDateString(),
            'prioritas' => Disposisi::PRIORITAS_SEGERA,
            'recipients' => [
                ['position_id' => $this->posKajurJtk->id],
            ],
        ]);

        $this->assertNull($dispTingkat1->parent_disposisi_id);
        $this->assertEquals(1, $dispTingkat1->recipients->count());
        $recipientKajur = $dispTingkat1->recipients->first();
        $this->assertEquals($this->kajurJtk->id, $recipientKajur->recipient_user_id);
        $this->assertEquals(DisposisiRecipient::STATUS_BELUM_DIBACA, $recipientKajur->status);

        // -------------------------------------------------------------
        // Tingkat 2: Kajur JTK -> Kaprodi TI (Penerusan berjenjang)
        // -------------------------------------------------------------
        $dispTingkat2 = $forwardAction->execute($this->kajurJtk, $recipientKajur, [
            'instruksi' => 'Tolong susun draf capaian pembelajaran lulusan prodi TI',
            'catatan' => 'Fokus pada kompetensi AI dan Cloud',
            'recipients' => [
                ['position_id' => $this->posKaprodiTi->id],
            ],
        ]);

        $this->assertEquals($dispTingkat1->id, $dispTingkat2->parent_disposisi_id);
        $this->assertEquals(DisposisiRecipient::STATUS_DITERUSKAN, $recipientKajur->fresh()->status);

        $recipientKaprodi = $dispTingkat2->recipients->first();
        $this->assertEquals($recipientKajur->id, $recipientKaprodi->parent_recipient_id);
        $this->assertEquals($this->kaprodiTi->id, $recipientKaprodi->recipient_user_id);

        // -------------------------------------------------------------
        // Tingkat 3: Kaprodi TI -> Staf TI (Penerusan berjenjang tingkat 3)
        // -------------------------------------------------------------
        $dispTingkat3 = $forwardAction->execute($this->kaprodiTi, $recipientKaprodi, [
            'instruksi' => 'Siapkan RPS modul Cloud Computing semester depan',
            'catatan' => 'Selesaikan sebelum rapat jurusan minggu depan',
            'recipients' => [
                ['user_id' => $this->stafTi->id],
            ],
        ]);

        $this->assertEquals($dispTingkat2->id, $dispTingkat3->parent_disposisi_id);
        $this->assertEquals(DisposisiRecipient::STATUS_DITERUSKAN, $recipientKaprodi->fresh()->status);

        $recipientStaf = $dispTingkat3->recipients->first();
        $this->assertEquals($recipientKaprodi->id, $recipientStaf->parent_recipient_id);
        $this->assertEquals($this->stafTi->id, $recipientStaf->recipient_user_id);
        $this->assertEquals(DisposisiRecipient::STATUS_BELUM_DIBACA, $recipientStaf->status);

        // Verifikasi integritas riwayat pohon disposisi (3 tingkat utuh)
        $this->assertEquals($this->direktur->id, $dispTingkat3->parent->parent->pemberi_id);
        $this->assertEquals($this->kajurJtk->id, $dispTingkat3->parent->pemberi_id);
        $this->assertEquals($this->kaprodiTi->id, $dispTingkat3->pemberi_id);
    }

    public function test_cycle_detection_prevents_forwarding_back_to_ancestor(): void
    {
        $createAction = new CreateDisposisiAction();
        $forwardAction = new ForwardDisposisiAction();

        // Tingkat 1: Direktur -> Kajur
        $disp1 = $createAction->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Tindak lanjuti',
            'recipients' => [
                ['position_id' => $this->posKajurJtk->id],
            ],
        ]);
        $recKajur = $disp1->recipients->first();

        // Kajur mencoba meneruskan kembali ke Direktur (A -> B -> A siklus!)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('siklus');

        $forwardAction->execute($this->kajurJtk, $recKajur, [
            'instruksi' => 'Kembalikan ke Direktur',
            'recipients' => [
                ['user_id' => $this->direktur->id],
            ],
        ]);
    }

    public function test_cycle_detection_prevents_forwarding_back_to_intermediate_ancestor(): void
    {
        $createAction = new CreateDisposisiAction();
        $forwardAction = new ForwardDisposisiAction();

        // Tingkat 1: Direktur -> Kajur
        $disp1 = $createAction->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Tindak lanjuti',
            'recipients' => [
                ['position_id' => $this->posKajurJtk->id],
            ],
        ]);
        $recKajur = $disp1->recipients->first();

        // Tingkat 2: Kajur -> Kaprodi
        $disp2 = $forwardAction->execute($this->kajurJtk, $recKajur, [
            'instruksi' => 'Tolong kerjakan',
            'recipients' => [
                ['position_id' => $this->posKaprodiTi->id],
            ],
        ]);
        $recKaprodi = $disp2->recipients->first();

        // Kaprodi mencoba meneruskan kembali ke Kajur (B -> C -> B siklus!)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('siklus');

        $forwardAction->execute($this->kaprodiTi, $recKaprodi, [
            'instruksi' => 'Meneruskan kembali ke Kajur',
            'recipients' => [
                ['position_id' => $this->posKajurJtk->id],
            ],
        ]);
    }

    public function test_hierarchy_boundary_prevents_forwarding_outside_unit_scope(): void
    {
        $createAction = new CreateDisposisiAction();
        $forwardAction = new ForwardDisposisiAction();

        // Tingkat 1: Direktur -> Kajur Komputer
        $disp1 = $createAction->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Tindak lanjuti',
            'recipients' => [
                ['position_id' => $this->posKajurJtk->id],
            ],
        ]);
        $recKajur = $disp1->recipients->first();

        // Kajur Komputer (unit JTK) mencoba meneruskan ke Kajur Mesin (unit JTM di luar hierarkinya!)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('luar cakupan unit kerja');

        $forwardAction->execute($this->kajurJtk, $recKajur, [
            'instruksi' => 'Lempar ke jurusan lain',
            'recipients' => [
                ['position_id' => $this->posKajurMesin->id],
            ],
        ]);
    }
}
