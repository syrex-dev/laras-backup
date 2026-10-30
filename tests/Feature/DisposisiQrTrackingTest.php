<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Actions\FollowupDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiQrTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pimpinan;
    protected User $kajur;
    protected Position $posPimpinan;
    protected Position $posKajur;
    protected OrgUnit $unitJurusan;
    protected SuratMasuk $surat;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'surat-masuk.lihat', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'disposisi.teruskan', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'disposisi.tindaklanjut', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.buat', 'disposisi.teruskan']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        $roleAdmin = Role::firstOrCreate(['name' => 'Admin Instansi', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.buat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        $unitPimpinan = OrgUnit::create(['name' => 'Pimpinan', 'code' => 'PIM', 'type' => 'pimpinan', 'order_index' => 1]);
        $this->unitJurusan = OrgUnit::create(['name' => 'Jurusan TI', 'code' => 'JTI', 'type' => 'jurusan', 'order_index' => 2]);

        $this->posPimpinan = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitPimpinan->id, 'order_index' => 1]);
        $this->posKajur = Position::create(['name' => 'Ketua Jurusan TI', 'org_unit_id' => $this->unitJurusan->id, 'order_index' => 2]);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'position_id' => $this->posPimpinan->id]);
        $this->admin->assignRole('Admin Instansi');

        $this->pimpinan = User::factory()->create(['name' => 'Direktur Utama', 'position_id' => $this->posPimpinan->id]);
        $this->pimpinan->assignRole('Pimpinan');

        $this->kajur = User::factory()->create(['name' => 'Kajur TI', 'position_id' => $this->posKajur->id]);
        $this->kajur->assignRole('Kepala Unit');

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Humas',
            'retensi_aktif' => 1,
            'retensi_inaktif' => 2,
        ]);

        $this->surat = SuratMasuk::create([
            'nomor_agenda' => '0001/SM/2026',
            'nomor_surat' => 'B-101/KEM/2026',
            'tanggal_surat' => '2026-03-01',
            'tanggal_diterima' => '2026-03-02',
            'pengirim' => 'Kementerian',
            'perihal' => 'Undangan Rapat Koordinasi Nasional',
            'sifat' => 'penting',
            'status' => 'terdaftar',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unitPimpinan->id,
            'user_id' => $this->admin->id,
            'qr_token' => 'QR-TRACK-TOKEN-TEST-12345',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_qr_tracking_and_redirects_to_login(): void
    {
        $response = $this->get(route('surat-masuk.lacak', $this->surat->qr_token));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_qr_tracking(): void
    {
        $userTanpaHak = User::factory()->create();
        // Tanpa role atau permission surat-masuk.lihat / disposisi.lihat

        $response = $this->actingAs($userTanpaHak)
            ->get(route('surat-masuk.lacak', $this->surat->qr_token));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_track_letter_initial_position(): void
    {
        $response = $this->actingAs($this->pimpinan)
            ->get(route('surat-masuk.lacak', $this->surat->qr_token));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('SuratMasuk/Tracking')
            ->has('surat')
            ->where('surat.nomor_agenda', '0001/SM/2026')
            ->where('currentPosition.location', 'Bagian Tata Usaha Persuratan')
            ->where('currentPosition.holder', 'Staf Tata Usaha')
            ->where('currentPosition.status_label', 'Menunggu Disposisi Pimpinan')
            ->has('disposisiChain', 0)
        );
    }

    public function test_tracking_reflects_disposition_recipient_and_chain(): void
    {
        // Pimpinan mendisposisikan ke Kajur
        $action = app(CreateDisposisiAction::class);
        $disposisi = $action->execute(
            sender: $this->pimpinan,
            suratMasuk: $this->surat,
            data: [
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
                'instruksi' => 'Tolong siapkan materi delegasi rakor.',
                'prioritas' => 'penting',
                'tenggat_waktu' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ]
        );

        $this->assertEquals('didisposisikan', $this->surat->fresh()->status);

        $response = $this->actingAs($this->kajur)
            ->get(route('surat-masuk.lacak', $this->surat->qr_token));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('SuratMasuk/Tracking')
            ->where('surat.nomor_agenda', '0001/SM/2026')
            ->where('currentPosition.location', 'Ketua Jurusan TI')
            ->where('currentPosition.holder', 'Kajur TI')
            ->has('disposisiChain', 1)
            ->where('disposisiChain.0.instruksi', 'Tolong siapkan materi delegasi rakor.')
        );
    }

    public function test_tracking_reflects_completed_letter_position(): void
    {
        $action = app(CreateDisposisiAction::class);
        $disposisi = $action->execute(
            sender: $this->pimpinan,
            suratMasuk: $this->surat,
            data: [
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
                'instruksi' => 'Hadiri rakor daring.',
                'prioritas' => 'biasa',
            ]
        );

        $recipient = $disposisi->recipients->first();

        // Kajur menindaklanjuti dan menyelesaikan
        $followupAction = app(FollowupDisposisiAction::class);
        $followupAction->execute(
            user: $this->kajur,
            recipient: $recipient,
            data: [
                'status' => 'selesai',
                'catatan' => 'Sudah dihadiri dan laporan telah diunggah.',
            ]
        );

        $this->assertEquals('selesai', $this->surat->fresh()->status);

        $response = $this->actingAs($this->pimpinan)
            ->get(route('surat-masuk.lacak', $this->surat->qr_token));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('SuratMasuk/Tracking')
            ->where('surat.status', 'selesai')
            ->where('currentPosition.location', 'Unit Kearsipan Dinamis')
            ->where('currentPosition.holder', 'Petugas Arsip')
        );
    }
}
