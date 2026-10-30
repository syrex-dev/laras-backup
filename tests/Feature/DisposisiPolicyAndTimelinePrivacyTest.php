<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\ActivityLog;
use App\Domain\Disposisi\Actions\FollowupDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Models\DisposisiRecipient;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DisposisiPolicyAndTimelinePrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_with_surat_masuk_lihat_not_involved_does_not_see_others_disposisi_in_timeline(): void
    {
        $unitPusat = OrgUnit::create(['code' => 'PUSAT', 'name' => 'Kantor Pusat', 'type' => 'administrative']);
        $posPimpinan = Position::create(['name' => 'Direktur', 'org_unit_id' => $unitPusat->id, 'level' => 1]);
        $posKajur = Position::create(['name' => 'Ketua Jurusan', 'org_unit_id' => $unitPusat->id, 'level' => 2]);
        $posStafLain = Position::create(['name' => 'Staf Arsip', 'org_unit_id' => $unitPusat->id, 'level' => 4]);

        $pimpinan = User::factory()->create(['position_id' => $posPimpinan->id]);
        $kajur = User::factory()->create(['position_id' => $posKajur->id]);
        $uninvolvedUser = User::factory()->create(['position_id' => $posStafLain->id]);

        $uninvolvedUser->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat']);
        $kajur->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat']);
        $pimpinan->givePermissionTo(['surat-masuk.lihat', 'disposisi.lihat', 'disposisi.buat']);

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'HM.01', 'nama' => 'Humas']);

        $suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-0099',
            'nomor_surat' => 'B/123/TEST/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unitPusat->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Kementerian Keuangan',
            'perihal' => 'Evaluasi Anggaran Internal Rahasia',
            'sifat' => 'rahasia',
            'jenis' => 'umum',
            'status' => 'diproses',
            'user_id' => $pimpinan->id,
        ]);

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $suratMasuk->id,
            'pemberi_id' => $pimpinan->id,
            'pengirim_id' => $pimpinan->id,
            'pemberi_position_id' => $posPimpinan->id,
            'instruksi' => 'Instruksi Khusus: Siapkan laporan anggaran rahasia untuk pimpinan',
            'catatan' => 'Jangan bocorkan ke pihak luar atau staf umum.',
            'prioritas' => 'sangat_segera',
            'status' => Disposisi::STATUS_DIDISPOSISIKAN,
        ]);

        DisposisiRecipient::create([
            'disposisi_id' => $disposisi->id,
            'recipient_user_id' => $kajur->id,
            'recipient_position_id' => $posKajur->id,
            'recipient_unit_id' => $unitPusat->id,
            'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
        ]);

        // 1. Pengguna yang tidak terlibat membuka halaman detail surat masuk
        $response = $this->actingAs($uninvolvedUser)->get(route('surat-masuk.show', $suratMasuk));
        $response->assertOk();

        // Verifikasi bahwa disposisiChain yang dikirimkan ke Inertia KOSONG untuk uninvolvedUser
        $response->assertInertia(fn ($page) => $page
            ->component('SuratMasuk/Show')
            ->has('disposisiChain', 0)
        );

        // 2. Sebaliknya, penerima (Kajur) membuka detail surat masuk yang sama
        $responseKajur = $this->actingAs($kajur)->get(route('surat-masuk.show', $suratMasuk));
        $responseKajur->assertOk();
        $responseKajur->assertInertia(fn ($page) => $page
            ->component('SuratMasuk/Show')
            ->has('disposisiChain', 1)
            ->where('disposisiChain.0.instruksi', 'Instruksi Khusus: Siapkan laporan anggaran rahasia untuk pimpinan')
        );
    }

    public function test_disposisi_policy_ancestor_recursion_depth_limit_20(): void
    {
        Log::spy();

        $unit = OrgUnit::create(['code' => 'UNIT', 'name' => 'Unit Kerja', 'type' => 'administrative']);
        $pos = Position::create(['name' => 'Staf', 'org_unit_id' => $unit->id, 'level' => 4]);

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.01', 'nama' => 'Umum']);

        $rootSender = User::factory()->create(['position_id' => $pos->id]);
        $rootSender->givePermissionTo('disposisi.lihat');

        $suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-9999',
            'nomor_surat' => 'B/RECURSION/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unit->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Instansi Luar',
            'perihal' => 'Uji Rekursi Disposisi',
            'sifat' => 'biasa',
            'jenis' => 'umum',
            'status' => 'diproses',
            'user_id' => $rootSender->id,
        ]);

        // Buat rantai disposisi sedalam 22 tingkat
        $currentParent = null;
        $allDisposisi = [];

        for ($depth = 1; $depth <= 22; $depth++) {
            $sender = ($depth === 1) ? $rootSender : User::factory()->create(['position_id' => $pos->id]);
            $recipientUser = User::factory()->create(['position_id' => $pos->id]);

            $d = Disposisi::create([
                'surat_masuk_id' => $suratMasuk->id,
                'parent_disposisi_id' => $currentParent?->id,
                'pemberi_id' => $sender->id,
                'pengirim_id' => $sender->id,
                'pemberi_position_id' => $pos->id,
                'instruksi' => "Disposisi Level {$depth}",
                'status' => Disposisi::STATUS_DIDISPOSISIKAN,
            ]);

            DisposisiRecipient::create([
                'disposisi_id' => $d->id,
                'recipient_user_id' => $recipientUser->id,
                'recipient_position_id' => $pos->id,
                'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
            ]);

            $currentParent = $d;
            $allDisposisi[] = $d;
        }

        $deepestDisposisi = end($allDisposisi);

        $canView = Gate::forUser($rootSender)->allows('view', $deepestDisposisi);
        $this->assertFalse($canView);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'batas penelusuran leluhur terlampaui (> 20)');
            });
    }

    public function test_admin_action_on_behalf_logs_atas_nama_in_audit_log(): void
    {
        $unit = OrgUnit::create(['code' => 'UNIT', 'name' => 'Unit Kerja', 'type' => 'administrative']);
        $pos = Position::create(['name' => 'Staf TU', 'org_unit_id' => $unit->id, 'level' => 4]);

        $recipientUser = User::factory()->create(['name' => 'Budi Staf Penerima', 'position_id' => $pos->id]);
        $admin = User::factory()->create(['name' => 'Admin Pengelola']);
        $admin->givePermissionTo(['disposisi.lihat', 'disposisi.tindaklanjut', 'disposisi.kelola']);

        $klasifikasi = KlasifikasiSurat::create(['kode_klasifikasi' => 'UM.02', 'nama' => 'Umum']);

        $suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-8888',
            'nomor_surat' => 'B/ATAS-NAMA/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'unit_tujuan_id' => $unit->id,
            'tanggal_surat' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'pengirim' => 'Pengirim Dinas',
            'perihal' => 'Pengujian Tindak Lanjut Atas Nama',
            'sifat' => 'biasa',
            'jenis' => 'umum',
            'status' => 'diproses',
            'user_id' => $recipientUser->id,
        ]);

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $suratMasuk->id,
            'pemberi_id' => $admin->id,
            'pengirim_id' => $admin->id,
            'instruksi' => 'Tindak lanjuti segera',
            'status' => Disposisi::STATUS_DIDISPOSISIKAN,
        ]);

        $recipient = DisposisiRecipient::create([
            'disposisi_id' => $disposisi->id,
            'recipient_user_id' => $recipientUser->id,
            'status' => DisposisiRecipient::STATUS_BELUM_DIBACA,
        ]);

        $action = app(FollowupDisposisiAction::class);
        $action->execute($admin, $recipient, [
            'status' => DisposisiRecipient::STATUS_SELESAI,
            'catatan' => 'Penyelesaian diintervensi oleh Admin atas nama staf.',
        ]);

        $lastLog = ActivityLog::where('action', 'disposisi.followup')->latest('id')->first();
        $this->assertNotNull($lastLog);
        $this->assertStringContainsString('atas nama', $lastLog->description);
        $this->assertStringContainsString('Budi Staf Penerima', $lastLog->description);
        $this->assertEquals('Budi Staf Penerima', $lastLog->properties['atas_nama']);
    }
}
