<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    protected HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Direktori cache serialisasi HTMLPurifier
        $cacheDir = storage_path('app/purifier');
        if (! file_exists($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        // Whitelist elemen dan atribut yang diizinkan untuk editor dokumen dinas
        $config->set(
            'HTML.Allowed',
            'p,br,b,strong,i,em,u,s,strike,h1,h2,h3,h4,h5,h6,ul,ol,li,' .
            'table[border|cellpadding|cellspacing|width|class|style],thead,tbody,tr,' .
            'th[colspan|rowspan|align|width|style|class],td[colspan|rowspan|align|width|style|class],' .
            'blockquote,pre,code,hr,span[style|class],div[style|class],sub,sup,a[href|title|target]'
        );

        // Skema URI yang diizinkan (blokir javascript:, data:, vbscript:, dll.)
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        $config->set('URI.DisableExternalResources', true);

        // Batasi properti CSS yang aman (blokir behavior, expression, url, fixed/absolute position berbahaya)
        $config->set('CSS.AllowedProperties', [
            'text-align' => true,
            'font-weight' => true,
            'font-style' => true,
            'text-decoration' => true,
            'font-size' => true,
            'font-family' => true,
            'color' => true,
            'background-color' => true,
            'margin' => true,
            'margin-left' => true,
            'margin-right' => true,
            'margin-top' => true,
            'margin-bottom' => true,
            'padding' => true,
            'padding-left' => true,
            'padding-right' => true,
            'padding-top' => true,
            'padding-bottom' => true,
            'line-height' => true,
            'border' => true,
            'border-collapse' => true,
            'width' => true,
            'height' => true,
        ]);

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Membersihkan string HTML dari potensi serangan XSS (Script, Iframe, Object, Event Handler, javascript:)
     */
    public function sanitize(?string $dirtyHtml): string
    {
        if ($dirtyHtml === null || $dirtyHtml === '') {
            return '';
        }

        return $this->purifier->purify($dirtyHtml);
    }
}
