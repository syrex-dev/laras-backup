<?php

namespace Tests\Feature;

use App\Domain\SuratMasuk\Models\NumberingSequence;
use App\Domain\SuratMasuk\Services\AgendaNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratMasukConcurrencyNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_number_increments_sequentially(): void
    {
        $service = new AgendaNumberService;

        $num1 = $service->generate(2026, 'PUSAT');
        $num2 = $service->generate(2026, 'PUSAT');
        $num3 = $service->generate(2026, 'PUSAT');

        $this->assertEquals('SM-2026-0001', $num1);
        $this->assertEquals('SM-2026-0002', $num2);
        $this->assertEquals('SM-2026-0003', $num3);

        $seq = NumberingSequence::where('type', 'surat_masuk')
            ->where('year', 2026)
            ->where('unit_code', 'PUSAT')
            ->first();

        $this->assertNotNull($seq);
        $this->assertEquals(3, $seq->last_number);
    }

    public function test_sequences_are_isolated_by_year_and_unit(): void
    {
        $service = new AgendaNumberService;

        $pusat2026 = $service->generate(2026, 'PUSAT');
        $pusat2027 = $service->generate(2027, 'PUSAT');
        $jtk2026 = $service->generate(2026, 'JTK');

        $this->assertEquals('SM-2026-0001', $pusat2026);
        $this->assertEquals('SM-2027-0001', $pusat2027);
        $this->assertEquals('SM-2026-0001', $jtk2026);
    }

    public function test_consecutive_generation_under_stress_produces_no_duplicates(): void
    {
        $service = new AgendaNumberService;
        $generatedNumbers = [];
        $totalIterations = 50;

        for ($i = 0; $i < $totalIterations; $i++) {
            $num = $service->generate(2026, 'DIR');
            $generatedNumbers[] = $num;
        }

        // Buktikan jumlah nomor tepat 50
        $this->assertCount($totalIterations, $generatedNumbers);

        // Buktikan semua nomor unik (tidak ada duplikasi)
        $uniqueNumbers = array_unique($generatedNumbers);
        $this->assertCount($totalIterations, $uniqueNumbers, 'Ditemukan nomor agenda duplikat!');

        // Buktikan format nomor pertama dan terakhir
        $this->assertEquals('SM-2026-0001', $generatedNumbers[0]);
        $this->assertEquals('SM-2026-0050', $generatedNumbers[49]);
    }
}
