<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'university',
        'degree_level',
        'description',
        'price',
        'is_active',
        'is_premium',
        'config',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'sort' => 'integer',
            'config' => 'array',
        ];
    }

    /**
     * Gabung config template dengan default global.
     * Config template menimpa default.
     */
    public function resolvedConfig(): array
    {
        $defaults = config('template.defaults', []);

        return array_replace_recursive($defaults, (array) $this->config);
    }

    /**
     * CSS inline untuk Dompdf dari resolved config.
     */
    public function toCss(): string
    {
        $cfg = $this->resolvedConfig();
        $m = $cfg['margin'];

        $css = [];
        $css[] = "@page { margin: {$m['top']}cm {$m['right']}cm {$m['bottom']}cm {$m['left']}cm; }";
        $css[] = "body { font-family: '{$cfg['font_family']}', Times, serif; font-size: {$cfg['font_size']}pt; line-height: {$cfg['line_height']}; color: #000; }";
        $css[] = "p { text-align: justify; line-height: {$cfg['line_height']}; margin: 0 0 {$cfg['paragraph_spacing']}cm; text-indent: {$cfg['paragraph_indent']}cm; orphans: 2; widows: 2; }";
        $css[] = '.no-indent, .cover p { text-indent: 0; }';

        if ($cfg['heading_case'] === 'uppercase') {
            $css[] = 'h2 { text-transform: uppercase; }';
        }

        if ($cfg['chapter_start_new_page']) {
            $css[] = '.chapter { page-break-before: always; }';
        }

        return implode("\n", $css);
    }

    /** Scope: template aktif, urut sesuai kolom sort. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    /**
     * Siapkan template bawaan kalau tabel masih kosong.
     */
    public static function seedDefaults(): void
    {
        $templates = [
            [
                'name' => 'Format Umum',
                'slug' => 'umum',
                'university' => null,
                'degree_level' => 'all',
                'description' => 'Format standar dengan margin kiri 4 cm, Times New Roman 12 pt, spasi 1,5.',
                'price' => 0,
                'is_premium' => false,
                'sort' => 0,
                'config' => null,
            ],
            [
                'name' => 'Universitas Indonesia',
                'slug' => 'ui',
                'university' => 'Universitas Indonesia',
                'degree_level' => 'all',
                'description' => 'Format UI: margin kiri 4 cm, spasi 2, heading rata tengah.',
                'price' => 0,
                'is_premium' => false,
                'sort' => 1,
                'config' => [
                    'line_height' => 2.0,
                    'heading_case' => 'uppercase',
                ],
            ],
            [
                'name' => 'UGM',
                'slug' => 'ugm',
                'university' => 'Universitas Gadjah Mada',
                'degree_level' => 'all',
                'description' => 'Format UGM: margin kiri 4 cm, font 12 pt, spasi 2.',
                'price' => 0,
                'is_premium' => false,
                'sort' => 2,
                'config' => [
                    'line_height' => 2.0,
                ],
            ],
            [
                'name' => 'ITB',
                'slug' => 'itb',
                'university' => 'Institut Teknologi Bandung',
                'degree_level' => 'all',
                'description' => 'Format ITB: margin kiri 4 cm, spasi 1,5, heading rata kiri.',
                'price' => 0,
                'is_premium' => false,
                'sort' => 3,
                'config' => [
                    'heading_case' => 'titlecase',
                ],
            ],
            [
                'name' => 'Format Premium — APA 7th',
                'slug' => 'apa-premium',
                'university' => null,
                'degree_level' => 'all',
                'description' => 'Template premium dengan format APA 7th edition, spasi 2, margin rata, heading bertingkat.',
                'price' => 50000,
                'is_premium' => true,
                'sort' => 10,
                'config' => [
                    'line_height' => 2.0,
                    'paragraph_indent' => 1.27,
                    'paragraph_spacing' => 0,
                    'heading_case' => 'titlecase',
                    'citation_style' => 'apa7',
                    'cover' => [
                        'uppercase_title' => false,
                        'include_logo' => false,
                        'include_advisor' => true,
                    ],
                ],
            ],
        ];

        foreach ($templates as $data) {
            static::firstOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}