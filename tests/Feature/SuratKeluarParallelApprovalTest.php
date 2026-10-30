<?php

namespace Tests\Feature;

use App\Domain\SuratKeluar\Actions\ApproveSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Models\SuratKeluarApproval;
use App\Domain\SuratKeluar\Models\TemplateSurat;
use App\Models\KlasifikasiSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuratKeluarParallelApprovalTest extends TestCase
{
    public function test_parallel_approval_concurrency_on_postgresql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Uji konkurensi persetujuan ganda memerlukan koneksi database PostgreSQL dengan row-level lockForUpdate().');
        }

        $exitCode = Artisan::call('app:test-parallel-approval');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode, "Uji persetujuan ganda gagal!\nOutput:\n{$output}");
        $this->assertStringContainsString('Hanya satu approval dan satu nomor surat yang berhasil', $output);
    }
}
