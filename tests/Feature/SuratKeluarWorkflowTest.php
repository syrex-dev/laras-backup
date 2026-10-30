<?php

namespace Tests\Feature;

use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\Pengguna\Models\PositionDelegation;
use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\RejectSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\RequestRevisionSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Notifications\SuratKeluarApprovalNotification;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuratKeluarWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $headUnit;
    protected User $pltHeadUnit;
    protected User $director;
    protected User $unrelatedUser;
    protected Position $posStaff;
    protected Position $posHead;
    protected Position $posDirector;
    protected OrgUnit $unit;
    protected KlasifikasiSurat $klasifikasi;
    protected SuratKeluar $suratKeluar;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'surat-keluar.buat']);
        Permission::firstOrCreate(['name' => 'surat-keluar.lihat']);
        Permission::firstOrCreate(['name' => 'surat-keluar.periksa']);
        Permission::firstOrCreate(['name' => 'surat-keluar.setujui']);
        Permission::firstOrCreate(['name' => 'surat-keluar.kelola']);

        $roleStaff = Role::firstOrCreate(['name' => 'Staf']);
        $roleStaff->givePermissionTo(['surat-keluar.buat', 'surat-keluar.lihat']);

        $roleHead = Role::firstOrCreate(['name' => 'Kepala Unit']);
        $roleHead->givePermissionTo(['surat-keluar.periksa', 'surat-keluar.lihat']);

        $roleDirector = Role::firstOrCreate(['name' => 'Pimpinan']);
        $roleDirector->givePermissionTo(['surat-keluar.periksa', 'surat-keluar.setujui', 'surat-keluar.lihat']);

        $this->unit = OrgUnit::create(['name' => 'Bagian Akademik', 'code' => 'BAK', 'type' => 'bagian']);

        $this->posStaff = Position::create(['name' => 'Staf Pengolah', 'org_unit_id' => $this->unit->id, 'level' => 3]);
        $this->posHead = Position::create(['name' => 'Kepala Bagian Akademik', 'org_unit_id' => $this->unit->id, 'level' => 2]);
        $this->posDirector = Position::create(['name' => 'Direktur', 'org_unit_id' => $this->unit->id, 'level' => 1]);

        $this->creator = User::factory()->create([
            'name' => 'Agus Pengaju',
            'position_id' => $this->posStaff->id,
        ]);
        $this->creator->assignRole($roleStaff);

        $this->headUnit = User::factory()->create([
            'name' => 'Ir. Hendra Kabag',
            'position_id' => $this->posHead->id,
        ]);
        $this->headUnit->assignRole($roleHead);

        $this->pltHeadUnit = User::factory()->create([
            'name' => 'Drs. Bambang Plt',
        ]);
        $this->pltHeadUnit->assignRole($roleHead);

        $this->director = User::factory()->create([
            'name' => 'Prof. Dr. Budi Direktur',
            'position_id' => $this->posDirector->id,
        ]);
        $this->director->assignRole($roleDirector);

        $this->unrelatedUser = User::factory()->create([
            'name' => 'Pengguna Lain',
        ]);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'PP.01',
            'nama' => 'Pendidikan dan Pelatihan',
        ]);

        // Buat draf surat keluar
        $draftAction = app(SaveDraftSuratKeluarAction::class);
        $this->suratKeluar = $draftAction->execute($this->creator, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'penandatangan_id' => $this->director->id,
            'tujuan' => 'Kementerian Pendidikan',
            'perihal' => 'Laporan Akhir Program Vokasi 2026',
            'isi_surat' => '<p>Bersama ini kami sampaikan laporan...</p>',
            'sifat' => 'penting',
        ]);
    }

    public function test_submit_draft_transitions_to_diperiksa_and_initializes_chain(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);

        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            [
                'step_order' => 1,
                'role_type' => SuratKeluarApproval::ROLE_PARAF,
                'position_id' => $this->posHead->id,
                'user_id' => $this->headUnit->id,
            ],
            [
                'step_order' => 2,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'position_id' => $this->posDirector->id,
                'user_id' => $this->director->id,
            ],
        ]);

        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $submitted->status);
        $this->assertEquals(1, $submitted->current_approval_step);
        $this->assertEquals(2, $submitted->approvals()->count());

        Notification::assertSentTo($this->headUnit, SuratKeluarApprovalNotification::class);
    }

    public function test_step_1_approver_paraf_advances_to_step_2(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            [
                'step_order' => 1,
                'role_type' => SuratKeluarApproval::ROLE_PARAF,
                'user_id' => $this->headUnit->id,
            ],
            [
                'step_order' => 2,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'user_id' => $this->director->id,
            ],
        ]);

        $approveAction = app(ApproveSuratKeluarAction::class);
        $approvedStep1 = $approveAction->execute($this->headUnit, $submitted, 'Telah diperiksa dan diparaf');

        $this->assertEquals(2, $approvedStep1->current_approval_step);
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $approvedStep1->status);

        $step1 = $approvedStep1->approvals()->where('step_order', 1)->first();
        $this->assertEquals(SuratKeluarApproval::STATUS_DIPARAF, $step1->status);
        $this->assertEquals('Telah diperiksa dan diparaf', $step1->catatan);

        Notification::assertSentTo($this->director, SuratKeluarApprovalNotification::class);
    }

    public function test_non_turn_approver_cannot_act_out_of_turn(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            [
                'step_order' => 1,
                'role_type' => SuratKeluarApproval::ROLE_PARAF,
                'user_id' => $this->headUnit->id,
            ],
            [
                'step_order' => 2,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'user_id' => $this->director->id,
            ],
        ]);

        $approveAction = app(ApproveSuratKeluarAction::class);

        // Direktur mencoba menyetujui padahal masih giliran step 1 (Kabag)
        $this->expectException(ValidationException::class);
        $approveAction->execute($this->director, $submitted);
    }

    public function test_plt_can_approve_when_active_delegation_exists(): void
    {
        // Delegasikan jabatan Kabag ke Plt hari ini
        PositionDelegation::create([
            'position_id' => $this->posHead->id,
            'original_user_id' => $this->headUnit->id,
            'delegate_user_id' => $this->pltHeadUnit->id,
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'reason' => 'Dinas ke Jakarta',
            'is_active' => true,
        ]);

        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            [
                'step_order' => 1,
                'role_type' => SuratKeluarApproval::ROLE_PARAF,
                'position_id' => $this->posHead->id,
            ],
            [
                'step_order' => 2,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'user_id' => $this->director->id,
            ],
        ]);

        // Plt Kabag memparaf naskah
        $approveAction = app(ApproveSuratKeluarAction::class);
        $approvedStep1 = $approveAction->execute($this->pltHeadUnit, $submitted, 'Diparaf oleh Plt Kabag');

        $this->assertEquals(2, $approvedStep1->current_approval_step);
        $step1 = $approvedStep1->approvals()->where('step_order', 1)->first();
        $this->assertEquals(SuratKeluarApproval::STATUS_DIPARAF, $step1->status);
        $this->assertEquals($this->pltHeadUnit->id, $step1->acted_user_id);
    }

    public function test_request_revision_requires_catatan_and_transitions_to_revisi(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            [
                'step_order' => 1,
                'role_type' => SuratKeluarApproval::ROLE_PARAF,
                'user_id' => $this->headUnit->id,
            ],
            [
                'step_order' => 2,
                'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN,
                'user_id' => $this->director->id,
            ],
        ]);

        $revisionAction = app(RequestRevisionSuratKeluarAction::class);

        // Validasi: catatan kosong ditolak
        try {
            $revisionAction->execute($this->headUnit, $submitted, '   ');
            $this->fail('Harus gagal jika catatan kosong.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('catatan', $e->errors());
        }

        // Minta revisi dengan catatan valid
        $revised = $revisionAction->execute($this->headUnit, $submitted, 'Perbaiki tabel anggaran pada paragraf 2');

        $this->assertEquals(SuratKeluar::STATUS_REVISI, $revised->status);
        $this->assertEquals('Perbaiki tabel anggaran pada paragraf 2', $revised->catatan_revisi);

        Notification::assertSentTo($this->creator, SuratKeluarApprovalNotification::class);
    }

    public function test_creator_can_update_revision_and_resubmit(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            ['step_order' => 1, 'role_type' => 'penandatangan', 'user_id' => $this->director->id],
        ]);

        $revisionAction = app(RequestRevisionSuratKeluarAction::class);
        $revised = $revisionAction->execute($this->director, $submitted, 'Tolong ubah perihal');

        $draftAction = app(SaveDraftSuratKeluarAction::class);
        $updated = $draftAction->execute($this->creator, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Kementerian Pendidikan',
            'perihal' => 'Laporan Akhir Program Vokasi 2026 - Revisi 1',
            'isi_surat' => '<p>Konten revisi baru...</p>',
            'catatan_perubahan' => 'Memperbaiki perihal sesuai instruksi pimpinan',
        ], [], $revised);

        $this->assertEquals(2, $updated->versions()->count());

        // Pengaju dapat mengajukan kembali
        $resubmitted = $submitAction->execute($this->creator, $updated);
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $resubmitted->status);
    }

    public function test_reject_requires_alasan_and_transitions_to_ditolak(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            ['step_order' => 1, 'role_type' => 'penandatangan', 'user_id' => $this->director->id],
        ]);

        $rejectAction = app(RejectSuratKeluarAction::class);

        // Tolak tanpa alasan harus gagal
        try {
            $rejectAction->execute($this->director, $submitted, '');
            $this->fail('Tolak tanpa alasan harus gagal.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('alasan', $e->errors());
        }

        $rejected = $rejectAction->execute($this->director, $submitted, 'Kegiatan dibatalkan karena pemotongan anggaran nasional.');

        $this->assertEquals(SuratKeluar::STATUS_DITOLAK, $rejected->status);
        $this->assertEquals('Kegiatan dibatalkan karena pemotongan anggaran nasional.', $rejected->alasan_tolak);

        Notification::assertSentTo($this->creator, SuratKeluarApprovalNotification::class);
    }

    public function test_final_approver_approval_transitions_to_disetujui(): void
    {
        $submitAction = app(SubmitSuratKeluarAction::class);
        $submitted = $submitAction->execute($this->creator, $this->suratKeluar, [
            ['step_order' => 1, 'role_type' => 'penandatangan', 'user_id' => $this->director->id],
        ]);

        $approveAction = app(ApproveSuratKeluarAction::class);
        $approved = $approveAction->execute($this->director, $submitted, 'Disetujui untuk diterbitkan nomor');

        $this->assertEquals(SuratKeluar::STATUS_DISETUJUI, $approved->status);
        Notification::assertSentTo($this->creator, SuratKeluarApprovalNotification::class);
    }

    public function test_invalid_transition_throws_validation_exception(): void
    {
        $approveAction = app(ApproveSuratKeluarAction::class);

        // Draf yang belum diajukan tidak dapat langsung disetujui
        $this->expectException(ValidationException::class);
        $approveAction->execute($this->director, $this->suratKeluar);
    }
}
