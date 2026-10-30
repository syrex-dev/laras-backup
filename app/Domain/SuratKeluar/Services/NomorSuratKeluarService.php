<?php

namespace App\Domain\SuratKeluar\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Instansi\Models\SystemSetting;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratMasuk\Models\NumberingSequence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NomorSuratKeluarService
{
    public const DEFAULT_PATTERN = '{urut}/{kode_unit}/{kode_klasifikasi}/{bulan_romawi}/{tahun}';

    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(AuditLogger::class);
    }

    /**
     * Konversi angka bulan ke angka romawi (I - XII)
     */
    public static function toRoman(int $month): string
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $map[$month] ?? (string) $month;
    }

    /**
     * Memformat nomor surat keluar berdasarkan pola token
     */
    public function formatNomor(int $sequenceNumber, array $context, string $pattern): string
    {
        $formatted = $pattern;

        // Tangani padding urutan, e.g. {urut:4} -> 0001, {urut} -> 1
        if (preg_match('/\{(?:urut|nomor|number):(\d+)\}/', $formatted, $matches)) {
            $padLength = (int) $matches[1];
            $padded = str_pad((string) $sequenceNumber, $padLength, '0', STR_PAD_LEFT);
            $formatted = str_replace($matches[0], $padded, $formatted);
        } else {
            $formatted = str_replace(['{urut}', '{nomor}', '{number}'], (string) $sequenceNumber, $formatted);
        }

        $kodeUnit = $context['kode_unit'] ?? 'PUSAT';
        $kodeKlasifikasi = $context['kode_klasifikasi'] ?? 'UM';
        $bulanRomawi = $context['bulan_romawi'] ?? self::toRoman((int) date('n'));
        $bulan = $context['bulan'] ?? date('m');
        $tahun = (string) ($context['tahun'] ?? date('Y'));

        $formatted = str_replace(['{kode_unit}', '{unit}'], $kodeUnit, $formatted);
        $formatted = str_replace(['{kode_klasifikasi}', '{klasifikasi}'], $kodeKlasifikasi, $formatted);
        $formatted = str_replace(['{bulan_romawi}', '{romawi}'], $bulanRomawi, $formatted);
        $formatted = str_replace('{bulan}', $bulan, $formatted);
        $formatted = str_replace(['{tahun}', '{year}'], $tahun, $formatted);

        return $formatted;
    }

    /**
     * Menghasilkan nomor berikutnya secara atomik dengan lockForUpdate
     */
    public function generateNextNomor(
        SuratKeluar $suratKeluar,
        ?Carbon $date = null,
        ?string $customPattern = null
    ): string {
        $effDate = $date ?? ($suratKeluar->tanggal_surat ? Carbon::parse($suratKeluar->tanggal_surat) : now());
        $year = (int) $effDate->format('Y');
        $month = (int) $effDate->format('n');

        $unitCode = $suratKeluar->orgUnit?->code ?: 'PUSAT';
        $klasifikasiCode = $suratKeluar->klasifikasi?->kode_klasifikasi ?: 'UM';

        $pattern = $customPattern
            ?: ($suratKeluar->template?->metadata['format_nomor'] ?? null)
            ?: SystemSetting::get('format_nomor_surat_keluar', self::DEFAULT_PATTERN);

        // Ambil nilai berikutnya secara atomik dengan row lock
        $nextSeq = NumberingSequence::nextVal(
            type: 'surat_keluar',
            year: $year,
            unitCode: $unitCode,
            prefix: 'SK',
            formatPattern: $pattern
        );

        $context = [
            'kode_unit' => $unitCode,
            'kode_klasifikasi' => $klasifikasiCode,
            'bulan_romawi' => self::toRoman($month),
            'bulan' => $effDate->format('m'),
            'tahun' => $year,
        ];

        return $this->formatNomor($nextSeq, $context, $pattern);
    }

    /**
     * Mengalokasikan nomor resmi pada naskah keluar (HANYA pada persetujuan akhir)
     */
    public function allocateNomor(
        SuratKeluar $suratKeluar,
        ?User $actor = null,
        ?Carbon $date = null,
        ?string $customPattern = null
    ): SuratKeluar {
        if ($suratKeluar->status === SuratKeluar::STATUS_BERNOMOR || ! empty($suratKeluar->nomor_surat)) {
            throw ValidationException::withMessages([
                'nomor_surat' => 'Surat keluar ini sudah memiliki nomor resmi dan tidak dapat dialokasikan ulang.',
            ]);
        }

        if ($suratKeluar->status === SuratKeluar::STATUS_DIBATALKAN) {
            throw ValidationException::withMessages([
                'status' => 'Surat keluar yang telah dibatalkan tidak dapat dialokasikan nomor.',
            ]);
        }

        if ($suratKeluar->status !== SuratKeluar::STATUS_DISETUJUI) {
            throw ValidationException::withMessages([
                'status' => "Nomor hanya dapat dialokasikan pada naskah yang telah mencapai persetujuan akhir (status 'disetujui'). Status saat ini: '{$suratKeluar->status}'.",
            ]);
        }

        return DB::transaction(function () use ($suratKeluar, $actor, $date, $customPattern) {
            $nomorSurat = $this->generateNextNomor($suratKeluar, $date, $customPattern);
            $effectiveDate = $date ?? ($suratKeluar->tanggal_surat ? Carbon::parse($suratKeluar->tanggal_surat) : now());

            $suratKeluar->update([
                'nomor_surat' => $nomorSurat,
                'status' => SuratKeluar::STATUS_BERNOMOR,
                'status_approval' => 'bernomor',
                'tanggal_surat' => $effectiveDate->toDateString(),
            ]);

            $this->auditLogger->log(
                'surat_keluar.number_allocated',
                $suratKeluar,
                "Mengalokasikan nomor resmi '{$nomorSurat}' pada surat keluar '{$suratKeluar->perihal}'"
            );

            return $suratKeluar->fresh(['orgUnit', 'klasifikasi', 'template', 'approvals']);
        });
    }
}
