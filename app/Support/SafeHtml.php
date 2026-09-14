<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizer HTML naskah skripsi.
 *
 * Isi section draft disimpan sebagai HTML dan dirender balik lewat `{!! !!}`
 * di editor dan di ekspor PDF/DOCX. Tanpa penyaringan, HTML apa pun yang
 * lolos ke kolom `content` akan ikut tereksekusi di halaman pengguna.
 *
 * Hanya tag yang benar-benar dipakai editor yang diizinkan; sisanya dibuang.
 */
class SafeHtml
{
    /** Tag yang boleh muncul di naskah. */
    private const ALLOWED = 'p,br,strong,em,u,b,i,ul,ol,li,h2,h3,h4,blockquote,sup,sub';

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('AutoFormat.RemoveEmpty', true);
        // Tautan tidak diizinkan, jadi tidak perlu Cache.DefinitionID khusus.
        $config->set('Cache.DefinitionImpl', null);

        return trim((new HTMLPurifier($config))->purify($html));
    }
}
