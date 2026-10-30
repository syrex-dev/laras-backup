<?php

namespace Tests\Feature;

use App\Domain\Lisensi\Models\LicenseState;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuratKeluarDraftTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $signer;
    protected KlasifikasiSurat $klasifikasi;
    protected TemplateSurat $template;
    protected OrgUnit $unit;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'surat-keluar.buat']);
        Permission::firstOrCreate(['name' => 'surat-keluar.lihat']);

        $roleStaff = Role::firstOrCreate(['name' => 'Staf']);
        $roleStaff->givePermissionTo(['surat-keluar.buat', 'surat-keluar.lihat']);

        $this->unit = OrgUnit::create(['name' => 'Jurusan Teknik Informatika', 'code' => 'JTK', 'type' => 'jurusan']);
        $this->position = Position::create(['name' => 'Dosen / Staf', 'org_unit_id' => $this->unit->id, 'level' => 3]);

        $this->creator = User::factory()->create([
            'name' => 'Budi Penyusun',
            'email' => 'budi@laras.test',
            'position_id' => $this->position->id,
        ]);
        $this->creator->assignRole($roleStaff);

        $this->signer = User::factory()->create([
            'name' => 'Dr. Direktur',
            'email' => 'direktur@laras.test',
        ]);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'KP.01',
            'nama' => 'Kepegawaian dan Penugasan',
        ]);

        $this->template = TemplateSurat::create([
            'nama' => 'Surat Tugas Standard',
            'jenis' => 'surat_tugas',
            'konten_default' => '<p>Nomor: {nomor_surat}</p><p>Kepada: {tujuan}</p>',
        ]);

        LicenseState::create([
            'license_key' => 'LIC-TEST-ACTIVE',
            'institution_slug' => 'poltek-demo',
            'plan' => 'standar',
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'last_verified_at' => now(),
            'max_users' => 100,
            'max_storage_bytes' => 10 * 1024 * 1024, // 10 MB kuota
            'signature_hash' => 'hash123',
        ]);
    }

    public function test_creator_can_create_draft_with_automatic_version_1(): void
    {
        $action = app(SaveDraftSuratKeluarAction::class);

        $file = UploadedFile::fake()->create('lampiran_tugas.pdf', 500, 'application/pdf');

        $suratKeluar = $action->execute(
            $this->creator,
            [
                'template_id' => $this->template->id,
                'klasifikasi_id' => $this->klasifikasi->id,
                'penandatangan_id' => $this->signer->id,
                'tujuan' => 'Dr. Ir. Ahmad',
                'perihal' => 'Penugasan Pelatihan AI',
                'isi_surat' => '<p>Melaksanakan pelatihan AI tingkat lanjut.</p>',
                'sifat' => 'penting',
                'catatan_perubahan' => 'Draf awal disusun oleh penyusun',
            ],
            [$file]
        );

        $this->assertEquals(SuratKeluar::STATUS_DRAF, $suratKeluar->status);
        $this->assertEquals(1, $suratKeluar->versions()->count());
        $this->assertEquals(1, $suratKeluar->versions()->first()->version_number);
        $this->assertEquals('Penugasan Pelatihan AI', $suratKeluar->versions()->first()->perihal);
        $this->assertEquals(1, $suratKeluar->attachments()->count());
        $this->assertEquals('lampiran_tugas.pdf', $suratKeluar->attachments()->first()->original_filename);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'surat_keluar.create_draft',
        ]);
    }

    public function test_substantial_update_increments_version_number(): void
    {
        $action = app(SaveDraftSuratKeluarAction::class);

        $suratKeluar = $action->execute(
            $this->creator,
            [
                'klasifikasi_id' => $this->klasifikasi->id,
                'tujuan' => 'Dr. Ir. Ahmad',
                'perihal' => 'Penugasan Riset Versi 1',
                'isi_surat' => '<p>Konten awal versi 1</p>',
            ]
        );

        $this->assertEquals(1, $suratKeluar->versions()->count());

        // Update substansial pada isi surat
        $updated = $action->execute(
            $this->creator,
            [
                'klasifikasi_id' => $this->klasifikasi->id,
                'tujuan' => 'Dr. Ir. Ahmad',
                'perihal' => 'Penugasan Riset Versi 2 (Revisi Ruang Lingkup)',
                'isi_surat' => '<p>Konten diperbarui dengan penambahan jadwal kegiatan.</p>',
                'catatan_perubahan' => 'Menambahkan rincian jadwal kegiatan',
            ],
            [],
            $suratKeluar
        );

        $this->assertEquals(2, $updated->versions()->count());
        $latestVersion = $updated->versions()->where('version_number', 2)->first();
        $this->assertNotNull($latestVersion);
        $this->assertEquals('Penugasan Riset Versi 2 (Revisi Ruang Lingkup)', $latestVersion->perihal);
        $this->assertEquals('Menambahkan rincian jadwal kegiatan', $latestVersion->catatan_perubahan);
    }

    public function test_storage_quota_is_enforced_on_attachments(): void
    {
        $action = app(SaveDraftSuratKeluarAction::class);

        // File 15 MB (melebihi kuota 10 MB)
        $hugeFile = UploadedFile::fake()->create('file_raksasa.pdf', 15 * 1024, 'application/pdf');

        $this->expectException(ValidationException::class);

        $action->execute(
            $this->creator,
            [
                'klasifikasi_id' => $this->klasifikasi->id,
                'tujuan' => 'Dr. Ir. Ahmad',
                'perihal' => 'Penugasan File Besar',
                'isi_surat' => '<p>Tes kuota</p>',
            ],
            [$hugeFile]
        );
    }
}
