<?php

namespace App\Domain\Letters\Services;

use App\Models\KlasifikasiSurat;
use App\Models\ProfilOrganisasi;
use App\Models\SuratKeluar;

class NomorSuratGenerator
{
    /**
     * Generate dynamic nomor surat format:
     * [Nomor Urut]/[Kode Klasifikasi]/[Kode Organisasi]/[Bulan Romawi]/[Tahun]
     */
    public function generate(SuratKeluar $surat): string
    {
        $profil = \App\Domain\Instansi\Models\InstitutionProfile::current();
        $kodePerusahaan = $profil->alias ?? 'POLTEK';

        $klasifikasi = $surat->klasifikasi;
        $kodeJenis = $klasifikasi->kode_klasifikasi ?? 'SRT';

        $tahunSekarang = now()->year;
        $count = SuratKeluar::whereYear('tanggal_surat', $tahunSekarang)->count();
        $nomorUrut = str_pad((string) max(1, $count), 3, '0', STR_PAD_LEFT);

        $bulanRomawi = $this->toRoman(now()->month);

        return "{$nomorUrut}/{$kodeJenis}/{$kodePerusahaan}/{$bulanRomawi}/{$tahunSekarang}";
    }

    /**
     * Generate preview / mock nomor surat.
     */
    public function generateMock(?int $klasifikasiId = null): string
    {
        $profil = \App\Domain\Instansi\Models\InstitutionProfile::current();
        $kodePerusahaan = $profil->alias ?? 'POLTEK';

        $klasifikasi = $klasifikasiId ? KlasifikasiSurat::find($klasifikasiId) : null;
        $kodeJenis = $klasifikasi ? $klasifikasi->kode_klasifikasi : 'SRT';

        $tahunSekarang = now()->year;
        $count = SuratKeluar::whereYear('tanggal_surat', $tahunSekarang)->count() + 1;
        $nomorUrut = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        $bulanRomawi = $this->toRoman(now()->month);

        return "{$nomorUrut}/{$kodeJenis}/{$kodePerusahaan}/{$bulanRomawi}/{$tahunSekarang}";
    }

    /**
     * Konversi angka bulan ke format Romawi.
     */
    public function toRoman(int $month): string
    {
        $romanMonths = [
            1 => 'I',   2 => 'II',   3 => 'III', 4 => 'IV',
            5 => 'V',   6 => 'VI',   7 => 'VII', 8 => 'VIII',
            9 => 'IX',  10 => 'X',    11 => 'XI',  12 => 'XII',
        ];

        return $romanMonths[$month] ?? 'I';
    }
}
