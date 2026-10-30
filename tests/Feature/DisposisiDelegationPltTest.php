<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\Pengguna\Models\PositionDelegation;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiDelegationPltTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajurDefinitif;
    private User $pltKajur;
    private Position $posDirektur;
    private Position $posKajur;
    private SuratMasuk $suratMasuk;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'Dr. Budi Direktur',
            'email' => 'direktur@poltek.ac.id',
            'position_id' => $this->posDirektur->id,
            'is_active' => true,
        ]);
        $this->direktur->assignRole($rolePimpinan);

        $this->kajurDefinitif = User::factory()->create([
            'name' => 'Ir. Santoso Definitif',
            'email' => 'santoso@poltek.ac.id',
            'position_id' => $this->posKajur->id,
            'is_active' => true,
        ]);
        $this->kajurDefinitif->assignRole($roleKepalaUnit);

        $this->pltKajur = User::factory()->create([
            'name' => 'Drs. Hendra Plt',
            'email' => 'hendra.plt@poltek.ac.id',
            'is_active' => true,
        ]);
        $this->pltKajur->assignRole($roleKepalaUnit);

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'OT.01',
            'nama' => 'Organisasi dan Tata Laksana',
            'keterangan' => 'Arsip tata laksana',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000200',
            'nomor_surat' => '200/KEMENDIKTI/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Sekretariat Jenderal',
            'perihal' => 'Evaluasi Kinerja Institusi Triwulan IV',
            'sifat' => 'penting',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);
    }

    public function test_before_delegation_period_disposition_is_received_by_definitif_officer(): void
    {
        // Pendelegasian dijadwalkan untuk masa depan (misal minggu depan)
        PositionDelegation::create([
            'position_id' => $this->posKajur->id,
            'original_user_id' => $this->kajurDefinitif->id,
            'delegate_user_id' => $this->pltKajur->id,
            'start_date' => Carbon::now()->addDays(7)->toDateString(),
            'end_date' => Carbon::now()->addDays(14)->toDateString(),
            'reason' => 'Dinas luar negeri ke Jerman',
            'is_active' => true,
        ]);

        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Siapkan dokumen evaluasi kinerja triwulan',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);

        $recipient = $disposisi->recipients->first();
        $this->assertFalse($recipient->is_delegated);
        $this->assertNull($recipient->delegated_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->recipient_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->effectiveUser()->id);
        $this->assertEquals('', $recipient->delegation_label);
        $this->assertEquals($this->kajurDefinitif->name, $recipient->display_name);
    }

    public function test_during_delegation_period_disposition_is_received_by_plt_with_clear_official_label(): void
    {
        // Pendelegasian aktif HARI INI
        PositionDelegation::create([
            'position_id' => $this->posKajur->id,
            'original_user_id' => $this->kajurDefinitif->id,
            'delegate_user_id' => $this->pltKajur->id,
            'start_date' => Carbon::now()->subDays(2)->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'reason' => 'Ibadah Umroh',
            'surat_tugas_number' => 'ST/055/DIR/2026',
            'is_active' => true,
        ]);

        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Siapkan bahan rapat pimpinan',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);

        $recipient = $disposisi->recipients->first();
        $this->assertTrue($recipient->is_delegated);
        $this->assertEquals($this->pltKajur->id, $recipient->delegated_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->recipient_user_id);

        // Penerima efektif adalah pemegang Plt
        $this->assertEquals($this->pltKajur->id, $recipient->effectiveUser()->id);

        // Tercatat jelas "atas nama jabatan X"
        $this->assertEquals('atas nama jabatan Ketua Jurusan TI', $recipient->delegation_label);
        $this->assertStringContainsString('Plt. Ketua Jurusan TI', $recipient->display_name);
        $this->assertStringContainsString($this->pltKajur->name, $recipient->display_name);
    }

    public function test_after_delegation_period_disposition_reverts_back_to_definitif_officer(): void
    {
        // Pendelegasian sudah kedaluwarsa (masa berlaku kemarin)
        PositionDelegation::create([
            'position_id' => $this->posKajur->id,
            'original_user_id' => $this->kajurDefinitif->id,
            'delegate_user_id' => $this->pltKajur->id,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->subDays(1)->toDateString(),
            'reason' => 'Cuti sakit pasca operasi',
            'is_active' => true,
        ]);

        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Lanjutkan koordinasi pasca evaluasi',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);

        $recipient = $disposisi->recipients->first();
        $this->assertFalse($recipient->is_delegated);
        $this->assertNull($recipient->delegated_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->recipient_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->effectiveUser()->id);
        $this->assertEquals('', $recipient->delegation_label);
    }

    public function test_open_disposition_stays_with_plt_holder_after_period_expires(): void
    {
        // Disposisi didelegasikan ke Plt
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Selesaikan telaah kurikulum',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);
        $recipient = $disposisi->recipients->first();
        $recipient->update([
            'is_delegated' => true,
            'delegated_user_id' => $this->pltKajur->id,
            'status' => 'diproses',
        ]);
        $recipient->refresh();
        $disposisi->load('recipients');

        // Pemegang Plt tetap dapat melihat dan menindaklanjuti sampai selesai
        $this->assertTrue($this->pltKajur->can('view', $disposisi));
        $this->assertTrue($this->pltKajur->can('followup', [Disposisi::class, $recipient]));
    }

    public function test_original_definitif_officer_has_readonly_access_to_open_plt_disposition(): void
    {
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Telaah teknis program studi',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);
        $recipient = $disposisi->recipients->first();
        $recipient->update([
            'is_delegated' => true,
            'delegated_user_id' => $this->pltKajur->id,
            'status' => 'diproses',
        ]);
        $recipient->refresh();
        $disposisi->load('recipients');

        // Pejabat definitif asli BERHAK melihat (read-only)
        $this->assertTrue($this->kajurDefinitif->can('view', $disposisi));
        $responseView = $this->actingAs($this->kajurDefinitif)->get("/disposisi/recipient/{$recipient->id}");
        $responseView->assertStatus(200);

        // Pejabat definitif asli TIDAK BERHAK menindaklanjuti selama masih dipegang Plt (baca-saja)
        $this->assertFalse($this->kajurDefinitif->can('followup', [Disposisi::class, $recipient]));
        $responseFollowup = $this->actingAs($this->kajurDefinitif)->post("/disposisi/recipient/{$recipient->id}/followup", [
            'status' => 'selesai',
            'catatan' => 'Mencoba eksekusi tanpa ambil alih',
        ]);
        $responseFollowup->assertStatus(403);
    }

    public function test_supervisor_can_take_over_disposition_from_plt_holder(): void
    {
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Koordinasi segera dengan senat akademik',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);
        $recipient = $disposisi->recipients->first();
        $recipient->update([
            'is_delegated' => true,
            'delegated_user_id' => $this->pltKajur->id,
            'status' => 'diproses',
        ]);

        // Atasan (Direktur selaku pemberi) mengambil alih disposisi
        $this->assertTrue($this->direktur->can('takeOver', [Disposisi::class, $recipient]));

        $responseTakeover = $this->actingAs($this->direktur)->post("/disposisi/recipient/{$recipient->id}/takeover", [
            'alasan' => 'Masa penugasan Plt selesai, dialihkan kembali ke pejabat definitif.',
            'target_user_id' => $this->kajurDefinitif->id,
        ]);

        $responseTakeover->assertSessionHasNoErrors();
        $responseTakeover->assertRedirect();

        // Disposisi kini kembali ke pejabat definitif
        $recipient->refresh();
        $this->assertFalse($recipient->is_delegated);
        $this->assertNull($recipient->delegated_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->recipient_user_id);
        $this->assertEquals($this->kajurDefinitif->id, $recipient->effectiveUser()->id);

        // Tercatat di activity logs
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'disposisi.ambil_alih',
        ]);

        // Pejabat definitif kini berhak mengeksekusi/menindaklanjuti
        $this->assertTrue($this->kajurDefinitif->can('followup', [Disposisi::class, $recipient]));
    }

    public function test_unrelated_user_cannot_take_over_disposition(): void
    {
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Instruksi rahasia',
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);
        $recipient = $disposisi->recipients->first();
        $recipient->update([
            'is_delegated' => true,
            'delegated_user_id' => $this->pltKajur->id,
        ]);

        $unrelatedUser = User::factory()->create();

        $response = $this->actingAs($unrelatedUser)->post("/disposisi/recipient/{$recipient->id}/takeover", [
            'alasan' => 'Mencoba ambil alih tanpa hak',
        ]);

        $response->assertStatus(403);
    }
}
