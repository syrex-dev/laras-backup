<?php

namespace App\Domain\SuratKeluar\Services;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Domain\Pengguna\Models\Position;
use App\Models\User;
use Carbon\Carbon;

class TemplateVariableService
{
    /**
     * Daftar variabel yang didukung secara resmi oleh sistem
     */
    public const SUPPORTED_VARIABLES = [
        '{nomor_surat}' => 'Nomor resmi surat dinas',
        '{tanggal_surat}' => 'Tanggal surat dalam bahasa Indonesia (contoh: 30 Oktober 2026)',
        '{perihal}' => 'Pokok atau perihal naskah dinas',
        '{tujuan}' => 'Tujuan penerima naskah dinas',
        '{nama_penandatangan}' => 'Nama lengkap dan gelar penandatangan',
        '{jabatan_penandatangan}' => 'Jabatan penandatangan (disesuaikan dengan kamus istilah)',
        '{unit_pembuat}' => 'Nama unit kerja pembuat surat',
        '{nama_instansi}' => 'Nama resmi instansi',
        '{alamat_instansi}' => 'Alamat lengkap instansi',
        '{email_instansi}' => 'Email resmi instansi',
        '{telepon_instansi}' => 'Nomor telepon resmi instansi',
    ];

    /**
     * Mengganti seluruh token variabel di dalam teks template dengan nilai kontekstual
     */
    public function render(string $content, array $context = []): string
    {
        $institution = InstitutionProfile::first();
        $terminology = $institution->terminology_dict ?? [];

        // Format tanggal bahasa Indonesia
        $tanggalSurat = ! empty($context['tanggal_surat'])
            ? Carbon::parse($context['tanggal_surat'])->locale('id')->isoFormat('D MMMM Y')
            : Carbon::now()->locale('id')->isoFormat('D MMMM Y');

        // Resolusi jabatan penandatangan dengan kamus istilah bila ada
        $rawJabatan = $context['jabatan_penandatangan'] ?? '';
        $jabatanPenandatangan = $this->resolveTerminology($rawJabatan, $terminology);

        $escape = fn ($val) => htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $replacements = [
            '{nomor_surat}' => $escape($context['nomor_surat'] ?? '[Nomor Surat Akan Terbit]'),
            '{tanggal_surat}' => $escape($tanggalSurat),
            '{perihal}' => $escape($context['perihal'] ?? '[Perihal Naskah Dinas]'),
            '{tujuan}' => $escape($context['tujuan'] ?? '[Tujuan Surat / Penerima]'),
            '{nama_penandatangan}' => $escape($context['nama_penandatangan'] ?? '[Nama Penandatangan]'),
            '{jabatan_penandatangan}' => $escape($jabatanPenandatangan ?: '[Jabatan Penandatangan]'),
            '{unit_pembuat}' => $escape($context['unit_pembuat'] ?? '[Unit Kerja]'),
            '{nama_instansi}' => $escape($institution->name ?? 'Politeknik Contoh Indonesia'),
            '{alamat_instansi}' => $escape($institution->address ?? 'Jl. Telekomunikasi No. 1, Bandung'),
            '{email_instansi}' => $escape($institution->email ?? 'persuratan@contoh.ac.id'),
            '{telepon_instansi}' => $escape($institution->phone ?? '(022) 1234567'),
        ];

        return strtr($content, $replacements);
    }

    /**
     * Menerjemahkan istilah jabatan/unit berdasarkan kamus istilah instansi
     */
    protected function resolveTerminology(string $title, array $terminology): string
    {
        if (empty($title)) {
            return '';
        }

        // Cek pemetaan langsung dari kamus istilah
        foreach ($terminology as $key => $term) {
            if (! empty($term) && stripos($title, $key) !== false) {
                return str_ireplace($key, $term, $title);
            }
        }

        return $title;
    }

    /**
     * Mengambil daftar token variabel yang terdapat dalam string teks
     */
    public function extractVariables(string $content): array
    {
        preg_match_all('/\{[a-z0-9_]+\}/i', $content, $matches);
        return array_unique($matches[0] ?? []);
    }

    /**
     * Memvalidasi apakah seluruh token dalam teks dikenali oleh sistem
     */
    public function validateVariables(string $content): array
    {
        $used = $this->extractVariables($content);
        $unsupported = [];

        foreach ($used as $var) {
            if (! array_key_exists($var, self::SUPPORTED_VARIABLES)) {
                $unsupported[] = $var;
            }
        }

        return $unsupported;
    }
}
