<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuratKeluarSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staffUnitA;
    protected User $staff2UnitA;
    protected User $staffUnitB;
    protected User $reviewerA;
    protected User $signerA;
    protected OrgUnit $unitA;
    protected OrgUnit $unitB;
    protected Position $posStaffA;
    protected Position $posStaff2A;
    protected Position $posStaffB;
    protected Position $posReviewerA;
    protected Position $posSignerA;
    protected KlasifikasiSurat $klasifikasi;
    protected TemplateSurat $template;

    protected function setUp(): void
    {
        parent::setUp();
        InstitutionProfile::current();
        LicenseState::current();

        $permissions = [
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.periksa',
            'surat-keluar.setujui',
            'surat-keluar.batalkan',
            'surat-keluar.kelola',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->unitA = OrgUnit::create(['name' => 'Jurusan Teknik Informatika', 'code' => 'JTI', 'type' => 'academic']);
        $this->unitB = OrgUnit::create(['name' => 'Jurusan Teknik Mesin', 'code' => 'JTM', 'type' => 'academic']);

        $this->posStaffA = Position::create(['org_unit_id' => $this->unitA->id, 'name' => 'Staf TU JTI', 'level' => 3]);
        $this->posStaff2A = Position::create(['org_unit_id' => $this->unitA->id, 'name' => 'Laboran JTI', 'level' => 3]);
        $this->posStaffB = Position::create(['org_unit_id' => $this->unitB->id, 'name' => 'Staf TU JTM', 'level' => 3]);
        $this->posReviewerA = Position::create(['org_unit_id' => $this->unitA->id, 'name' => 'Kajur JTI', 'level' => 2]);
        $this->posSignerA = Position::create(['org_unit_id' => $this->unitA->id, 'name' => 'Direktur', 'level' => 1, 'is_penandatangan' => true]);

        $this->admin = User::factory()->create(['name' => 'Super Admin']);
        $this->admin->givePermissionTo(Permission::all());

        $this->staffUnitA = User::factory()->create(['name' => 'Budi JTI', 'position_id' => $this->posStaffA->id]);
        $this->staffUnitA->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.buat']);

        $this->staff2UnitA = User::factory()->create(['name' => 'Siti JTI', 'position_id' => $this->posStaff2A->id]);
        $this->staff2UnitA->givePermissionTo(['surat-keluar.lihat']);

        $this->staffUnitB = User::factory()->create(['name' => 'Agus JTM', 'position_id' => $this->posStaffB->id]);
        $this->staffUnitB->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.buat']);

        $this->reviewerA = User::factory()->create(['name' => 'Dr. Kajur JTI', 'position_id' => $this->posReviewerA->id]);
        $this->reviewerA->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.periksa']);

        $this->signerA = User::factory()->create(['name' => 'Prof. Direktur', 'position_id' => $this->posSignerA->id]);
        $this->signerA->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.periksa', 'surat-keluar.setujui']);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Kerjasama',
        ]);

        $this->template = TemplateSurat::create([
            'nama' => 'Surat Tugas Standar',
            'jenis' => TemplateSurat::JENIS_SURAT_TUGAS,
            'kode' => 'ST-SEC',
            'konten_default' => '<p>Surat tugas pengujian keamanan...</p>',
            'format_penomoran' => '{nomor}/ST/{unit}/{tahun}',
            'is_active' => true,
        ]);
    }

    public function test_user_without_create_permission_cannot_create_draft(): void
    {
        $payload = [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Rektor Mitra',
            'perihal' => 'Pengujian Akses Terlarang',
            'isi_surat' => '<p>Konten</p>',
        ];

        // staff2UnitA only has surat-keluar.lihat
        $response = $this->actingAs($this->staff2UnitA)->post(route('surat-keluar.store'), $payload);
        $response->assertForbidden();
    }

    public function test_idor_unauthorized_user_cannot_view_draft_of_another_user(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $draftUnitA = $saveAction->execute($this->staffUnitA, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitA->id,
            'tujuan' => 'Vendor Software Inc',
            'perihal' => 'Draf Rahasia Internal JTI',
            'isi_surat' => '<p>Naskah draf yang belum terbit...</p>',
            'sifat' => SuratKeluar::SIFAT_BIASA,
        ]);

        // Staf B from different unit cannot view draft
        $responseB = $this->actingAs($this->staffUnitB)->get(route('surat-keluar.show', $draftUnitA));
        $responseB->assertForbidden();

        // Staf 2 from SAME unit also cannot view draft of another staff before published!
        $response2A = $this->actingAs($this->staff2UnitA)->get(route('surat-keluar.show', $draftUnitA));
        $response2A->assertForbidden();

        // Owner can view
        $responseOwner = $this->actingAs($this->staffUnitA)->get(route('surat-keluar.show', $draftUnitA));
        $responseOwner->assertOk();

        // Admin can view
        $responseAdmin = $this->actingAs($this->admin)->get(route('surat-keluar.show', $draftUnitA));
        $responseAdmin->assertOk();
    }

    public function test_idor_unauthorized_user_cannot_update_or_delete_draft(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $draftUnitA = $saveAction->execute($this->staffUnitA, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Mitra A',
            'perihal' => 'Draf Awal',
            'isi_surat' => '<p>Konten asli</p>',
        ]);

        // Staf B cannot update draft A
        $responseUpdate = $this->actingAs($this->staffUnitB)->put(route('surat-keluar.update', $draftUnitA), [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Mitra A Dibajak',
            'perihal' => 'Draf Diretas',
            'isi_surat' => '<p>Konten bajakan</p>',
        ]);
        $responseUpdate->assertForbidden();

        // Staf B cannot delete draft A
        $responseDelete = $this->actingAs($this->staffUnitB)->delete(route('surat-keluar.destroy', $draftUnitA));
        $responseDelete->assertForbidden();
    }

    public function test_visibility_of_published_numbered_letters_by_unit_and_sifat(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $submitAction = app(SubmitSuratKeluarAction::class);
        $approveAction = app(ApproveSuratKeluarAction::class);

        // 1. Regular numbered letter (sifat = biasa)
        $publicLetter = $saveAction->execute($this->staffUnitA, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitA->id,
            'penandatangan_id' => $this->signerA->id,
            'tujuan' => 'Seluruh Dosen JTI',
            'perihal' => 'Pengumuman Jadwal UAS JTI',
            'isi_surat' => '<p>Jadwal UAS semester ganjil...</p>',
            'sifat' => SuratKeluar::SIFAT_BIASA,
        ]);
        $submitAction->execute($this->staffUnitA, $publicLetter, [
            ['user_id' => $this->signerA->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN],
        ]);
        $approveAction->execute($this->signerA, $publicLetter, 'Disetujui', true);
        $publicLetter->refresh();

        // Pegawai lain dalam SATU unit berhak melihat surat bernomor non-rahasia
        $responseSameUnit = $this->actingAs($this->staff2UnitA)->get(route('surat-keluar.show', $publicLetter));
        $responseSameUnit->assertOk();

        // Pegawai unit LAIN tetap tidak boleh melihat (IDOR isolation)
        $responseOtherUnit = $this->actingAs($this->staffUnitB)->get(route('surat-keluar.show', $publicLetter));
        $responseOtherUnit->assertForbidden();

        // 2. Confidential numbered letter (sifat = rahasia)
        $secretLetter = $saveAction->execute($this->staffUnitA, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unitA->id,
            'penandatangan_id' => $this->signerA->id,
            'tujuan' => 'Komisi Disiplin',
            'perihal' => 'Surat Keputusan Sanksi Akademik Mahasiswa',
            'isi_surat' => '<p>Dokumen sanksi rahasia...</p>',
            'sifat' => SuratKeluar::SIFAT_RAHASIA,
        ]);
        $submitAction->execute($this->staffUnitA, $secretLetter, [
            ['user_id' => $this->signerA->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN],
        ]);
        $approveAction->execute($this->signerA, $secretLetter, 'Disetujui rahasia', true);
        $secretLetter->refresh();

        // Rekan satu unit TIDAK BERHAK melihat surat rahasia meski sudah bernomor!
        $responseSameUnitSecret = $this->actingAs($this->staff2UnitA)->get(route('surat-keluar.show', $secretLetter));
        $responseSameUnitSecret->assertForbidden();

        // Hanya pihak berwenang (pembuat, penandatangan, admin kelola) yang berhak
        $this->actingAs($this->staffUnitA)->get(route('surat-keluar.show', $secretLetter))->assertOk();
        $this->actingAs($this->signerA)->get(route('surat-keluar.show', $secretLetter))->assertOk();
        $this->actingAs($this->admin)->get(route('surat-keluar.show', $secretLetter))->assertOk();
    }

    public function test_non_reviewer_cannot_act_on_another_user_turn(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $submitAction = app(SubmitSuratKeluarAction::class);

        $letter = $saveAction->execute($this->staffUnitA, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Rektor Mitra',
            'perihal' => 'Pemeriksaan Berjenjang',
            'isi_surat' => '<p>Konten</p>',
        ]);

        $submitAction->execute($this->staffUnitA, $letter, [
            ['user_id' => $this->reviewerA->id, 'role_type' => SuratKeluarApproval::ROLE_PEMERIKSA],
            ['user_id' => $this->signerA->id, 'role_type' => SuratKeluarApproval::ROLE_PENANDATANGAN],
        ]);

        // Staf B tries to approve Kajur's turn
        $response = $this->actingAs($this->staffUnitB)->patch(route('surat-keluar.approve', $letter), [
            'catatan' => 'Mencoba membajak paraf',
        ]);
        // Controller/Action will reject with ValidationException (redirects back with error)
        $response->assertSessionHasErrors('authorization');
    }

    public function test_license_read_only_blocks_mutations_and_allows_safe_queries(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $letter = $saveAction->execute($this->staffUnitA, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Mitra Lisensi',
            'perihal' => 'Uji Lisensi Kedaluwarsa',
            'isi_surat' => '<p>Konten</p>',
        ]);

        // Set lisensi menjadi expired
        $license = LicenseState::current();
        $license->update([
            'status' => 'expired',
            'expires_at' => Carbon::now()->subMonths(2),
            'grace_period_days' => 0,
        ]);
        $this->assertTrue($license->fresh()->isReadOnly());

        // 1. Safe requests (GET) ALWAYS allowed
        $this->actingAs($this->staffUnitA)->get(route('surat-keluar.index'))->assertOk();
        $this->actingAs($this->staffUnitA)->get(route('surat-keluar.show', $letter))->assertOk();
        $this->actingAs($this->staffUnitA)->get(route('surat-keluar.pdf', $letter))->assertOk();

        // 2. Mutations (POST, PUT, DELETE) blocked with 403 on Inertia/API or redirect error
        $postResponse = $this->actingAs($this->staffUnitA)->post(route('surat-keluar.store'), [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Tujuan Ditolak Lisensi',
            'perihal' => 'Mutasi Terlarang',
        ], ['X-Inertia' => 'true']);
        $postResponse->assertStatus(403);
        $postResponse->assertJsonFragment(['is_read_only' => true]);

        $putResponse = $this->actingAs($this->staffUnitA)->put(route('surat-keluar.update', $letter), [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Update Terlarang',
            'perihal' => 'Perihal Update Terlarang',
        ], ['X-Inertia' => 'true']);
        $putResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->staffUnitA)->delete(route('surat-keluar.destroy', $letter), [], [
            'X-Inertia' => 'true',
        ]);
        $deleteResponse->assertStatus(403);
    }
}
