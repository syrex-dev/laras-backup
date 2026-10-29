<?php

namespace App\Domain\Letters\Services;

class TemplateParserService
{
    /**
     * Bangun array pengganti (search → replace) untuk semua placeholder template.
     *
     * Mendukung dua gaya penulisan placeholder:
     *   - Bracket-style : [NOMOR_SURAT], [LAMPIRAN], dll.
     *   - Blade-style   : {{ nomor_surat }}, {{lampiran}}, dll.
     */
    public function buildReplacements(array $values): array
    {
        $map = [];

        foreach ($values as $key => $value) {
            $upperKey = strtoupper($key);

            // Bracket-style: [NOMOR_SURAT], [LAMPIRAN], [TUJUAN_SURAT]
            $map["[{$upperKey}]"]     = (string) $value;

            // Blade-style (dengan spasi): {{ nomor_surat }}
            $map["{{ {$key} }}"]      = (string) $value;

            // Blade-style (tanpa spasi): {{nomor_surat}}
            $map["{{{$key}}}"]        = (string) $value;

            // Blade-style kapital (toleransi): {{ NOMOR_SURAT }}
            $map["{{ {$upperKey} }}"] = (string) $value;
            $map["{{{$upperKey}}}"]   = (string) $value;
        }

        return $map;
    }

    /**
     * Parsing dan substitusi konten template HTML.
     */
    public function parse(string $htmlContent, array $values): string
    {
        $replacements = $this->buildReplacements($values);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $htmlContent
        );
    }
}
