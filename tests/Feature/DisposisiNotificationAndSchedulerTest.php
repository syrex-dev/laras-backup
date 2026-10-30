<?php

namespace Tests\Feature;

use App\Domain\Disposisi\Actions\CreateDisposisiAction;
use App\Domain\Disposisi\Models\Disposisi;
use App\Domain\Disposisi\Notifications\DisposisiDeadlineReminderNotification;
use App\Domain\Disposisi\Notifications\DisposisiOverdueEscalationNotification;
use App\Domain\Disposisi\Notifications\DisposisiReceivedNotification;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratMasuk\Models\SuratMasuk;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisposisiNotificationAndSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private User $direktur;
    private User $kajur;
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

        $klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.02',
            'nama' => 'Kerjasama Industri',
            'keterangan' => 'Kemitraan',
        ]);

        $this->suratMasuk = SuratMasuk::create([
            'nomor_agenda' => 'SM-2026-000400',
            'nomor_surat' => '400/IND/2026',
            'klasifikasi_id' => $klasifikasi->id,
            'tanggal_surat' => '2026-11-01',
            'tanggal_diterima' => '2026-11-01',
            'pengirim' => 'PT Industri Teknologi',
            'perihal' => 'Tawaran Hibah Perangkat Server AI',
            'sifat' => 'sangat_segera',
            'jenis' => 'fisik',
            'status' => 'terdaftar',
            'user_id' => $this->direktur->id,
        ]);
    }

    public function test_disposition_creation_dispatches_queued_notification_to_recipient(): void
    {
        Notification::fake();

        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Koordinasikan penerimaan server AI',
            'recipients' => [
                ['user_id' => $this->kajur->id],
            ],
        ]);

        Notification::assertSentTo(
            $this->kajur,
            DisposisiReceivedNotification::class,
            function ($notification) use ($disposisi) {
                return $notification->disposisi->id === $disposisi->id;
            }
        );
    }

    public function test_in_app_database_notification_is_stored_in_database(): void
    {
        // Tanpa fake agar tersimpan ke tabel notifications secara riil
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Segera ambil tindakan',
            'recipients' => [
                ['user_id' => $this->kajur->id],
            ],
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->kajur->id,
        ]);

        $notificationRow = DB::table('notifications')
            ->where('notifiable_id', $this->kajur->id)
            ->first();

        $this->assertNotNull($notificationRow);
        $payload = json_decode($notificationRow->data, true);
        $this->assertEquals('disposisi_received', $payload['type']);
        $this->assertEquals($disposisi->id, $payload['disposisi_id']);
    }

    public function test_scheduler_sends_h_minus_1_reminder_and_is_strictly_idempotent(): void
    {
        Carbon::setTestNow('2026-11-01 08:00:00');

        // Buat disposisi dengan tenggat 2026-11-03
        $action = new CreateDisposisiAction();
        $disposisi = $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Tenggat 3 November',
            'tenggat_waktu' => '2026-11-03',
            'recipients' => [
                ['user_id' => $this->kajur->id],
            ],
        ]);

        // Manipulasi waktu ke H-1 (2026-11-02 07:00:00)
        Carbon::setTestNow('2026-11-02 07:00:00');

        // Jalankan scheduler pertama kali
        $this->artisan('app:check-disposisi-deadlines')
            ->expectsOutputToContain('1 pengingat dikirim')
            ->assertSuccessful();

        // Buktikan notifikasi pengingat tersimpan
        $reminderCount = DB::table('notifications')
            ->where('notifiable_id', $this->kajur->id)
            ->where('data', 'like', '%"type":"disposisi_deadline_reminder"%')
            ->count();
        $this->assertEquals(1, $reminderCount);

        // Jalankan scheduler KEDUA KALI pada hari yang sama (Uji Idempoten)
        $this->artisan('app:check-disposisi-deadlines')
            ->expectsOutputToContain('0 pengingat dikirim')
            ->assertSuccessful();

        // Jumlah notifikasi TIDAK BERTAMBAH (tetap 1)
        $reminderCountAfter = DB::table('notifications')
            ->where('notifiable_id', $this->kajur->id)
            ->where('data', 'like', '%"type":"disposisi_deadline_reminder"%')
            ->count();
        $this->assertEquals(1, $reminderCountAfter);
    }

    public function test_scheduler_escalates_overdue_disposition_to_supervisor_and_is_idempotent(): void
    {
        Carbon::setTestNow('2026-11-01 08:00:00');

        // Disposisi dengan tenggat 2026-11-03
        $action = new CreateDisposisiAction();
        $action->execute($this->direktur, $this->suratMasuk, [
            'instruksi' => 'Tenggat 3 November',
            'tenggat_waktu' => '2026-11-03',
            'recipients' => [
                ['user_id' => $this->kajur->id],
            ],
        ]);

        // Manipulasi waktu ke hari setelah tenggat (Terlambat: 2026-11-04 07:00:00)
        Carbon::setTestNow('2026-11-04 07:00:00');

        // Jalankan scheduler pertama kali saat terlambat
        $this->artisan('app:check-disposisi-deadlines')
            ->expectsOutputToContain('1 eskalasi dikirim')
            ->assertSuccessful();

        // Buktikan eskalasi dikirim ke atasan (Direktur)
        $escalationCount = DB::table('notifications')
            ->where('notifiable_id', $this->direktur->id)
            ->where('data', 'like', '%"type":"disposisi_overdue_escalation"%')
            ->count();
        $this->assertEquals(1, $escalationCount);

        // Jalankan scheduler KEDUA KALI pada hari yang sama (Uji Idempoten)
        $this->artisan('app:check-disposisi-deadlines')
            ->expectsOutputToContain('0 eskalasi dikirim')
            ->assertSuccessful();

        // Jumlah notifikasi eskalasi tetap 1
        $escalationCountAfter = DB::table('notifications')
            ->where('notifiable_id', $this->direktur->id)
            ->where('data', 'like', '%"type":"disposisi_overdue_escalation"%')
            ->count();
        $this->assertEquals(1, $escalationCountAfter);
    }
}
