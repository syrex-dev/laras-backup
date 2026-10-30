<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\InstructionTemplate;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiUiRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajur;
    private Position $posDirektur;
    private Position $posKajur;
    private SuratMasuk $suratMasuk;
    private Disposisi $disposisi;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'disposisi.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'disposisi.lihat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'surat-masuk.lihat', 'guard_name' => 'web']);

        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->givePermissionTo(['disposisi.buat', 'disposisi.lihat', 'surat-masuk.lihat']);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo(['disposisi.lihat', 'surat-masuk.lihat']);

        $unitDir = OrgUnit::create(['name' => 'Direktorat', 'code' => 'DIR', 'type' => 'pimpinan']);
        $unitJtk = OrgUnit::create(['name' => 'Jurusan Komputer', 'code' => 'JTK', 'type' => 'jurusan', 'parent_id' => $unitDir->id]);

        $this->posDirektur = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitDir->id, 'level' => 1]);
        $this->posKajur = Position::create(['name' => 'Ketua Jurusan TI', 'org_unit_id' => $unitJtk->id, 'level' => 2]);

        $this->direktur = User::factory()->create([
            'name' => 'Direktur Utama',
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

        InstructionTemplate::create([
            'kode' => 'TL',
            'nama' => 'Tindak lanjuti segera',
            'is_active' => true,
        ]);

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'UM.01',
            'nama' => 'Umum',
            'keterangan' => 'Arsip umum',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000500',
            'nomor_surat' => '500/UM/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Sekretariat Utama',
            'perihal' => 'Pemberitahuan Audit Eksternal BPK',
            'sifat' => 'sangat_segera',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);

        $action = new CreateDisposisiAction();
        $this->disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Siapkan seluruh SPJ dan berkas pendukung',
            'tenggat_waktu' => now()->addDays(4)->toDateString(),
            'prioritas' => Disposisi::PRIORITAS_SANGAT_SEGERA,
            'recipients' => [
                ['position_id' => $this->posKajur->id],
            ],
        ]);
    }

    public function test_disposisi_inbox_renders_successfully_with_inertia(): void
    {
        $this->actingAs($this->kajur)
            ->get('/disposisi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Disposisi/Index')
                ->has('items.data', 1)
                ->has('instructionTemplates')
                ->has('subordinatePositions')
                ->where('items.data.0.status', 'belum_dibaca')
            );
    }

    public function test_disposisi_detail_renders_with_timeline_tree(): void
    {
        $recipient = $this->disposisi->recipients->first();

        $this->actingAs($this->kajur)
            ->get("/disposisi/recipient/{$recipient->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Disposisi/Show')
                ->has('recipient')
                ->has('timelineTree', 1)
                ->where('recipient.id', $recipient->id)
                ->where('recipient.status', 'dibaca') // otomatis ditandai dibaca saat dibuka
            );
    }

    public function test_surat_masuk_show_includes_disposisi_chain_prop(): void
    {
        $this->actingAs($this->direktur)
            ->get("/surat-masuk/{$this->suratMasuk->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuratMasuk/Show')
                ->has('surat')
                ->has('disposisiChain', 1)
                ->where('disposisiChain.0.instruksi', 'Siapkan seluruh SPJ dan berkas pendukung')
            );
    }
}
