<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\ActivityLog;
use App\Domain\Audit\Services\AuditLogger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_can_be_recorded_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'tu@poltek.ac.id',
            'name' => 'Staf TU',
        ]);

        $log = AuditLogger::log(
            action: 'surat_masuk.created',
            subject: 'SuratMasuk#1',
            description: 'Mendaftarkan surat masuk baru dengan agenda SM-2026-0001',
            properties: ['nomor_agenda' => 'SM-2026-0001', 'pengirim' => 'Kementerian'],
            causer: $user
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'action' => 'surat_masuk.created',
            'causer_id' => $user->id,
            'description' => 'Mendaftarkan surat masuk baru dengan agenda SM-2026-0001',
        ]);
    }

    public function test_updating_activity_log_via_eloquent_is_forbidden_and_throws_exception(): void
    {
        $log = ActivityLog::record(
            action: 'auth.login',
            subject: 'User#1',
            description: 'Login pengguna berhasil'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi UPDATE dilarang.');

        $log->update([
            'description' => 'Mencoba memalsukan deskripsi log audit',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'description' => 'Login pengguna berhasil',
        ]);
    }

    public function test_deleting_activity_log_via_eloquent_is_forbidden_and_throws_exception(): void
    {
        $log = ActivityLog::record(
            action: 'auth.logout',
            subject: 'User#1',
            description: 'Logout pengguna berhasil'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi DELETE dilarang.');

        $log->delete();

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
        ]);
    }

    public function test_updating_activity_log_via_raw_database_query_is_rejected_by_trigger(): void
    {
        $log = ActivityLog::record(
            action: 'security.check',
            subject: 'System',
            description: 'Pemeriksaan keamanan awal'
        );

        $this->expectException(\Throwable::class);

        DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['description' => 'Modifikasi langsung query SQL']);

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'description' => 'Pemeriksaan keamanan awal',
        ]);
    }

    public function test_deleting_activity_log_via_raw_database_query_is_rejected_by_trigger(): void
    {
        $log = ActivityLog::record(
            action: 'security.check',
            subject: 'System',
            description: 'Pemeriksaan log penghapusan'
        );

        $this->expectException(\Throwable::class);

        DB::table('activity_logs')
            ->where('id', $log->id)
            ->delete();

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
        ]);
    }

    public function test_truncating_activity_log_via_raw_database_query_is_rejected_by_trigger(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Perlindungan statement-level TRUNCATE trigger spesifik untuk PostgreSQL.');
        }

        ActivityLog::record(
            action: 'security.check',
            subject: 'System',
            description: 'Uji perlindungan TRUNCATE audit log'
        );

        $this->expectException(\Throwable::class);

        DB::statement('TRUNCATE activity_logs');
    }
}
