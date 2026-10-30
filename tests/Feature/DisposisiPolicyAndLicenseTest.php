<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiPolicyAndLicenseTest extends TestCase
{
    use RefreshDatabase;

    protected User $pimpinan;
    protected User $kajur;
    protected User $kaprodi;
    protected User $stafBiasa;
    protected Position $posPimpinan;
    protected Position $posKajur;
    protected Position $posKaprodi;
    protected SuratMasuk $surat;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'surat-masuk.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.teruskan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.tindaklanjut', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.buat', 'disposisi.teruskan']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.teruskan', 'disposisi.tindaklanjut']);

        $roleStaf = Role::firstOrCreate(['name' => 'Staf', 'guard_name' => 'web']);
        $roleStaf->givePermissionTo(['disposisi.lihat', 'disposisi.tindaklanjut']);

        $unitDir = OrgUnit::create(['name' => 'Direktorat', 'code' => 'DIR', 'type' => 'pimpinan', 'order_index' => 1]);
        $unitJurusan = OrgUnit::create(['name' => 'Jurusan TI', 'code' => 'JTI', 'type' => 'jurusan', 'order_index' => 2, 'parent_id' => $unitDir->id]);
        $unitProdi = OrgUnit::create(['name' => 'Prodi TI', 'code' => 'TI', 'type' => 'prodi', 'order_index' => 3, 'parent_id' => $unitJurusan->id]);

        $this->posPimpinan = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitDir->id, 'order_index' => 1]);
        $this->posKajur = Position::create(['name' => 'Kajur TI', 'org_unit_id' => $unitJurusan->id, 'order_index' => 2]);
        $this->posKaprodi = Position::create(['name' => 'Kaprodi TI', 'org_unit_id' => $unitProdi->id, 'order_index' => 3]);

        $this->pimpinan = User::factory()->create(['name' => 'Direktur', 'position_id' => $this->posPimpinan->id]);
        $this->pimpinan->assignRole('Pimpinan');

        $this->kajur = User::factory()->create(['name' => 'Kajur', 'position_id' => $this->posKajur->id]);
        $this->kajur->assignRole('Kepala Unit');

        $this->kaprodi = User::factory()->create(['name' => 'Kaprodi', 'position_id' => $this->posKaprodi->id]);
        $this->kaprodi->assignRole('Staf');

        $this->stafBiasa = User::factory()->create(['name' => 'Staf Luar']);

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Humas',
            'retensi_aktif' => 1,
            'retensi_inaktif' => 2,
        ]);

        $this->surat = SuratMasuk::create([
            'nomor_agenda' => '0001/SM/2026',
            'nomor_surat' => 'B-01/TEST/2026',
            'tanggal_surat' => '2026-03-01',
            'tanggal_diterima' => '2026-03-02',
            'pengirim' => 'Instansi Luar',
            'perihal' => 'Undangan Rapat',
            'sifat' => 'biasa',
            'status' => 'terdaftar',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unitDir->id,
            'user_id' => $this->pimpinan->id,
            'qr_token' => 'QR-POLICY-TEST-TOKEN',
        ]);
    }

    public function test_pimpinan_can_create_disposition_while_unauthorized_user_cannot(): void
    {
        // 1. Pimpinan berwenang membuat disposisi
        $response = $this->actingAs($this->pimpinan)
            ->post(route('disposisi.store'), [
                'surat_masuk_id' => $this->surat->id,
                'instruksi' => 'Instruksi pimpinan sah.',
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disposisi', [
            'surat_masuk_id' => $this->surat->id,
            'instruksi' => 'Instruksi pimpinan sah.',
        ]);

        // 2. Staf biasa tanpa wewenang ditolak (403)
        $responseStaf = $this->actingAs($this->stafBiasa)
            ->post(route('disposisi.store'), [
                'surat_masuk_id' => $this->surat->id,
                'instruksi' => 'Instruksi ilegal.',
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
            ]);

        $responseStaf->assertStatus(403);
    }

    public function test_recipient_can_forward_while_unrelated_user_cannot(): void
    {
        // Disposisi dari Pimpinan ke Kajur
        $action = app(CreateDisposisiAction::class);
        $disposisi = $action->execute(
            sender: $this->pimpinan,
            suratMasuk: $this->surat,
            data: [
                'instruksi' => 'Teruskan ke prodi.',
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
            ]
        );

        $recipient = $disposisi->recipients->first();

        // 1. User yang bukan penerima (stafBiasa) ditolak meneruskan (403)
        $responseUnauthorized = $this->actingAs($this->stafBiasa)
            ->post(route('disposisi.recipient.forward', $recipient->id), [
                'instruksi' => 'Meneruskan tanpa izin.',
                'recipients' => [
                    ['user_id' => $this->kaprodi->id, 'position_id' => $this->posKaprodi->id],
                ],
            ]);

        $responseUnauthorized->assertStatus(403);

        // 2. Kajur (penerima sah) diizinkan meneruskan ke Kaprodi
        $responseAuthorized = $this->actingAs($this->kajur)
            ->post(route('disposisi.recipient.forward', $recipient->id), [
                'instruksi' => 'Mohon tindak lanjuti tingkat prodi.',
                'recipients' => [
                    ['user_id' => $this->kaprodi->id, 'position_id' => $this->posKaprodi->id],
                ],
            ]);

        $responseAuthorized->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disposisi', [
            'parent_disposisi_id' => $disposisi->id,
            'instruksi' => 'Mohon tindak lanjuti tingkat prodi.',
        ]);
    }

    public function test_recipient_can_followup_while_unrelated_user_cannot(): void
    {
        $action = app(CreateDisposisiAction::class);
        $disposisi = $action->execute(
            sender: $this->pimpinan,
            suratMasuk: $this->surat,
            data: [
                'instruksi' => 'Hadiri pertemuan.',
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
            ]
        );

        $recipient = $disposisi->recipients->first();

        // 1. Staf luar ditolak menindaklanjuti (403)
        $responseUnauthorized = $this->actingAs($this->stafBiasa)
            ->post(route('disposisi.recipient.followup', $recipient->id), [
                'status' => 'selesai',
                'catatan' => 'Laporan palsu.',
            ]);

        $responseUnauthorized->assertStatus(403);

        // 2. Kajur (penerima sah) berhasil menindaklanjuti
        $responseAuthorized = $this->actingAs($this->kajur)
            ->post(route('disposisi.recipient.followup', $recipient->id), [
                'status' => 'selesai',
                'catatan' => 'Pertemuan telah dihadiri.',
            ]);

        $responseAuthorized->assertSessionHasNoErrors();
        $this->assertEquals('selesai', $recipient->fresh()->status);
    }

    public function test_expired_license_blocks_disposition_mutations_in_read_only_mode(): void
    {
        // 1. Buat lisensi kedaluwarsa di luar grace period
        $license = LicenseState::current();
        $license->update([
            'status' => 'expired',
            'expires_at' => Carbon::now()->subDays(30),
            'grace_period_days' => 7,
        ]);

        // 2. Operasi mutasi (POST /disposisi) harus terblokir 403
        $responseCreate = $this->actingAs($this->pimpinan)
            ->postJson(route('disposisi.store'), [
                'surat_masuk_id' => $this->surat->id,
                'instruksi' => 'Instruksi saat kedaluwarsa.',
                'recipients' => [
                    ['user_id' => $this->kajur->id, 'position_id' => $this->posKajur->id],
                ],
            ]);

        $responseCreate->assertStatus(403);
        $responseCreate->assertJsonFragment(['is_read_only' => true]);

        // 3. Operasi baca (GET /disposisi) tetap diizinkan
        $responseRead = $this->actingAs($this->pimpinan)
            ->get(route('disposisi.index'));

        $responseRead->assertOk();
    }
}
