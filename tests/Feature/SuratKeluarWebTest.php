<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\AllocateNomorSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Actions\SubmitSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuratKeluarWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $creator;
    protected User $reviewer;
    protected User $signer;
    protected OrgUnit $unit;
    protected Position $posAdmin;
    protected Position $posCreator;
    protected Position $posReviewer;
    protected Position $posSigner;
    protected KlasifikasiSurat $klasifikasi;
    protected TemplateSurat $template;

    protected function setUp(): void
    {
        parent::setUp();
        InstitutionProfile::current();

        $permissions = [
            'surat-keluar.lihat',
            'surat-keluar.buat',
            'surat-keluar.periksa',
            'surat-keluar.setujui',
            'surat-keluar.batalkan',
            'surat-keluar.kelola',
            'disposisi.buat',
            'disposisi.lihat',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->unit = OrgUnit::create([
            'name' => 'Sekretariat Pimpinan',
            'code' => 'SEKRET',
            'type' => 'administrative',
        ]);

        $this->posAdmin = Position::create(['org_unit_id' => $this->unit->id, 'name' => 'Admin Arsip', 'level' => 3]);
        $this->posCreator = Position::create(['org_unit_id' => $this->unit->id, 'name' => 'Staf Tata Usaha', 'level' => 3]);
        $this->posReviewer = Position::create(['org_unit_id' => $this->unit->id, 'name' => 'Kepala Bagian Umum', 'level' => 2]);
        $this->posSigner = Position::create(['org_unit_id' => $this->unit->id, 'name' => 'Direktur', 'level' => 1, 'is_penandatangan' => true]);

        $this->admin = User::factory()->create(['name' => 'Admin Sistem', 'position_id' => $this->posAdmin->id]);
        $this->admin->givePermissionTo(Permission::all());

        $this->creator = User::factory()->create(['name' => 'Budi Staf TU', 'position_id' => $this->posCreator->id]);
        $this->creator->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.buat']);

        $this->reviewer = User::factory()->create(['name' => 'Siti Kabag', 'position_id' => $this->posReviewer->id]);
        $this->reviewer->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.periksa']);

        $this->signer = User::factory()->create(['name' => 'Prof. Direktur', 'position_id' => $this->posSigner->id]);
        $this->signer->givePermissionTo(['surat-keluar.lihat', 'surat-keluar.periksa', 'surat-keluar.setujui']);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01',
            'nama' => 'Hubungan Antar Lembaga',
        ]);

        $this->template = TemplateSurat::create([
            'nama' => 'Surat Tugas Standar',
            'jenis' => TemplateSurat::JENIS_SURAT_TUGAS,
            'kode' => 'ST-01',
            'konten_default' => '<p>Yang bertanda tangan di bawah ini menugaskan kepada [nama_pegawai]...</p>',
            'format_penomoran' => '{nomor}/ST/{unit}/{bulan_romawi}/{tahun}',
            'is_active' => true,
        ]);
    }

    public function test_index_displays_letter_list_with_server_pagination_and_filters(): void
    {
        SuratKeluar::create([
            'user_id' => $this->creator->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'tujuan' => 'Kementerian Pendidikan',
            'perihal' => 'Laporan Kinerja Tahunan 2026',
            'isi_surat' => '<p>Laporan kinerja lengkap...</p>',
            'status' => SuratKeluar::STATUS_DRAF,
            'status_approval' => 'draft',
        ]);

        SuratKeluar::create([
            'user_id' => $this->creator->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'tujuan' => 'Dinas Tenaga Kerja',
            'perihal' => 'Permohonan Narasumber Workshop',
            'isi_surat' => '<p>Undangan narasumber...</p>',
            'status' => SuratKeluar::STATUS_BERNOMOR,
            'status_approval' => 'approved',
            'nomor_surat' => '001/ST/SEKRET/X/2026',
        ]);

        $response = $this->actingAs($this->creator)->get(route('surat-keluar.index'));
        $response->assertOk();

        // Filter status=draf
        $filterResponse = $this->actingAs($this->creator)->get(route('surat-keluar.index', ['status' => 'draf']));
        $filterResponse->assertOk();

        // Search perihal
        $searchResponse = $this->actingAs($this->creator)->get(route('surat-keluar.index', ['search' => 'Narasumber']));
        $searchResponse->assertOk();
    }

    public function test_create_renders_editor_with_templates_and_reply_data(): void
    {
        $suratMasuk = SuratMasuk::create([
            'user_id' => $this->admin->id,
            'nomor_surat' => '99/EXT/2026',
            'asal_surat' => 'Vendor Software Inc',
            'pengirim' => 'CTO Vendor',
            'perihal' => 'Penawaran Audit Keamanan',
            'tanggal_surat' => '2026-10-01',
            'tanggal_diterima' => '2026-10-02',
            'sifat' => 'biasa',
            'klasifikasi_id' => $this->klasifikasi->id,
        ]);

        $action = app(CreateDisposisiAction::class);
        $disposisi = $action->execute($this->admin, $suratMasuk, [
            'sifat' => 'biasa',
            'instruksi' => 'Siapkan tanggapan dan draf surat balasan resmi',
            'catatan' => 'Mohon dijawab segera',
            'recipients' => [
                ['user_id' => $this->creator->id, 'instruksi' => 'Siapkan balasan resmi'],
            ],
        ]);
        $recipient = $disposisi->recipients->first();

        $response = $this->actingAs($this->creator)->get(route('surat-keluar.create', [
            'reply_to_disposisi_id' => $recipient->id,
        ]));

        $response->assertOk();
    }

    public function test_store_creates_draft_and_stores_version(): void
    {
        $payload = [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'penandatangan_id' => $this->signer->id,
            'tujuan' => 'Rektor Universitas Mitra',
            'perihal' => 'Undangan Diskusi Kurikulum Vokasi',
            'isi_surat' => '<p>Kami mengundang Bapak/Ibu hadir pada diskusi...</p>',
            'sifat' => 'biasa',
            'tanggal_surat' => '2026-10-30',
        ];

        $response = $this->actingAs($this->creator)->post(route('surat-keluar.store'), $payload);
        $response->assertRedirect(route('surat-keluar.index'));

        $this->assertDatabaseHas('surat_keluar', [
            'perihal' => 'Undangan Diskusi Kurikulum Vokasi',
            'tujuan' => 'Rektor Universitas Mitra',
            'status' => SuratKeluar::STATUS_DRAF,
        ]);

        $letter = SuratKeluar::where('perihal', 'Undangan Diskusi Kurikulum Vokasi')->first();
        $this->assertNotNull($letter);
        $this->assertEquals(1, $letter->versions()->count());
    }

    public function test_store_with_submit_now_submits_to_approval_workflow(): void
    {
        $payload = [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'penandatangan_id' => $this->signer->id,
            'tujuan' => 'Gubernur Jawa Barat',
            'perihal' => 'Laporan Kegiatan Pengabdian',
            'isi_surat' => '<p>Laporan lengkap kegiatan...</p>',
            'sifat' => 'penting',
            'submit_now' => true,
            'approval_chain' => [
                ['user_id' => $this->reviewer->id, 'urutan' => 1, 'tipe' => 'pemeriksa'],
                ['user_id' => $this->signer->id, 'urutan' => 2, 'tipe' => 'penandatangan'],
            ],
        ];

        $response = $this->actingAs($this->creator)->post(route('surat-keluar.store'), $payload);
        $letter = SuratKeluar::where('perihal', 'Laporan Kegiatan Pengabdian')->first();

        $response->assertRedirect(route('surat-keluar.show', $letter));
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $letter->status);
        $this->assertEquals(2, $letter->approvals()->count());
    }

    public function test_show_and_edit_draft(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $letter = $saveAction->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'tujuan' => 'PT Telekomunikasi',
            'perihal' => 'Kerjasama Akses Internet Kampus',
            'isi_surat' => '<p>Pengajuan bandwidth kampus...</p>',
            'sifat' => 'biasa',
        ]);

        $this->actingAs($this->creator)->get(route('surat-keluar.show', $letter))->assertOk();
        $this->actingAs($this->creator)->get(route('surat-keluar.edit', $letter))->assertOk();

        // Update draft
        $updateResponse = $this->actingAs($this->creator)->put(route('surat-keluar.update', $letter), [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'PT Telekomunikasi Indonesia Tbk',
            'perihal' => 'Kerjasama Akses Internet Kampus 2026',
            'isi_surat' => '<p>Pengajuan bandwidth kampus ditingkatkan menjadi 1 Gbps...</p>',
            'sifat' => 'penting',
            'catatan_perubahan' => 'Menambahkan rincian kapasitas bandwidth',
        ]);

        $updateResponse->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals('PT Telekomunikasi Indonesia Tbk', $letter->tujuan);
        $this->assertEquals(2, $letter->versions()->count());
    }

    public function test_destroy_draft(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $letter = $saveAction->execute($this->creator, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Target Hapus',
            'perihal' => 'Surat yang akan dihapus',
            'isi_surat' => '<p>Hapus saya</p>',
        ]);

        $response = $this->actingAs($this->creator)->delete(route('surat-keluar.destroy', $letter));
        $response->assertRedirect(route('surat-keluar.index'));
        $this->assertSoftDeleted('surat_keluar', ['id' => $letter->id]);
    }

    public function test_full_approval_workflow_web_cycle(): void
    {
        $saveAction = app(SaveDraftSuratKeluarAction::class);
        $submitAction = app(SubmitSuratKeluarAction::class);

        $letter = $saveAction->execute($this->creator, [
            'template_id' => $this->template->id,
            'klasifikasi_id' => $this->klasifikasi->id,
            'org_unit_id' => $this->unit->id,
            'penandatangan_id' => $this->signer->id,
            'tujuan' => 'Kepala Badan Riset',
            'perihal' => 'Pemberitahuan Kerjasama Riset Terapan',
            'isi_surat' => '<p>Naskah kerjasama riset...</p>',
        ]);

        // 1. Submit
        $submitResponse = $this->actingAs($this->creator)->post(route('surat-keluar.submit', $letter), [
            'approval_chain' => [
                ['user_id' => $this->reviewer->id, 'urutan' => 1, 'tipe' => 'pemeriksa'],
                ['user_id' => $this->signer->id, 'urutan' => 2, 'tipe' => 'penandatangan'],
            ],
        ]);
        $submitResponse->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $letter->status);

        // 2. Reviewer requests revision
        $reviseResponse = $this->actingAs($this->reviewer)->patch(route('surat-keluar.revise', $letter), [
            'catatan' => 'Perbaiki paragraf kedua naskah kerjasama.',
        ]);
        $reviseResponse->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_REVISI, $letter->status);

        // 3. Creator updates and re-submits
        $updateResponse = $this->actingAs($this->creator)->put(route('surat-keluar.update', $letter), [
            'klasifikasi_id' => $this->klasifikasi->id,
            'tujuan' => 'Kepala Badan Riset',
            'perihal' => 'Pemberitahuan Kerjasama Riset Terapan',
            'isi_surat' => '<p>Naskah kerjasama riset yang telah diperbaiki.</p>',
            'submit_now' => true,
        ]);
        $updateResponse->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $letter->status);

        // 4. Reviewer approves step 1
        $appr1Response = $this->actingAs($this->reviewer)->patch(route('surat-keluar.approve', $letter), [
            'catatan' => 'Pemeriksaan tahap 1 selesai dan disetujui.',
        ]);
        $appr1Response->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_DIPERIKSA, $letter->status);

        // 5. Signer approves step 2 (Final approval -> auto numbering)
        $appr2Response = $this->actingAs($this->signer)->patch(route('surat-keluar.approve', $letter), [
            'catatan' => 'Disetujui untuk ditandatangani dan diterbitkan.',
            'auto_allocate_nomor' => true,
        ]);
        $appr2Response->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_BERNOMOR, $letter->status);
        $this->assertNotNull($letter->nomor_surat);

        // 6. PDF generation
        $pdfResponse = $this->actingAs($this->signer)->get(route('surat-keluar.pdf', $letter));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // 7. Cancel official numbered letter
        $cancelResponse = $this->actingAs($this->admin)->post(route('surat-keluar.cancel', $letter), [
            'alasan_batal' => 'Acara kerjasama ditunda tanpa batas waktu atas arahan pimpinan.',
        ]);
        $cancelResponse->assertRedirect(route('surat-keluar.show', $letter));
        $letter->refresh();
        $this->assertEquals(SuratKeluar::STATUS_DIBATALKAN, $letter->status);
    }
}
