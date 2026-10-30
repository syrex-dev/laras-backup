<?php

namespace App\Domain\SuratMasuk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberingSequence extends Model
{
    use HasFactory;

    protected $table = 'numbering_sequences';

    protected $fillable = [
        'type',
        'year',
        'unit_code',
        'prefix',
        'last_number',
        'format_pattern',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];

    /**
     * Menghasilkan nomor agenda/surat berikutnya secara atomik di dalam DB transaction
     * dengan lockForUpdate() untuk mencegah race condition atau nomor ganda.
     */
    public static function nextNumber(
        string $type,
        int $year,
        string $unitCode = 'PUSAT',
        string $prefix = 'SM',
        string $formatPattern = '{prefix}-{year}-{number:4}'
    ): string {
        return DB::transaction(function () use ($type, $year, $unitCode, $prefix, $formatPattern) {
            // Pastikan baris sudah ada tanpa risiko aborted transaction di PostgreSQL/SQLite
            DB::statement('
                INSERT INTO numbering_sequences (type, year, unit_code, prefix, last_number, format_pattern, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (type, year, unit_code) DO NOTHING
            ', [$type, $year, $unitCode, $prefix, $formatPattern]);

            $sequence = static::where('type', $type)
                ->where('year', $year)
                ->where('unit_code', $unitCode)
                ->lockForUpdate()
                ->firstOrFail();

            $nextVal = $sequence->last_number + 1;
            $sequence->update(['last_number' => $nextVal]);

            $formatted = $sequence->format_pattern;
            $formatted = str_replace('{prefix}', $sequence->prefix, $formatted);
            $formatted = str_replace('{year}', (string) $sequence->year, $formatted);
            $formatted = str_replace('{unit}', $sequence->unit_code, $formatted);

            if (preg_match('/\{number:(\d+)\}/', $formatted, $matches)) {
                $padLength = (int) $matches[1];
                $padded = str_pad((string) $nextVal, $padLength, '0', STR_PAD_LEFT);
                $formatted = str_replace($matches[0], $padded, $formatted);
            } else {
                $formatted = str_replace('{number}', (string) $nextVal, $formatted);
            }

            return $formatted;
        });
    }
}
