<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default format naskah
    |--------------------------------------------------------------------------
    |
    | Nilai bawaan dipakai kalau project belum memilih template universitas.
    | Semua ukuran dalam sentimeter, ukuran huruf dalam poin (pt).
    |
    */
    'defaults' => [
        'font_family' => 'Times New Roman',
        'font_size' => 12,
        'line_height' => 1.5,
        'margin' => ['top' => 3, 'right' => 3, 'bottom' => 3, 'left' => 4],
        'paragraph_indent' => 1.27,
        'paragraph_spacing' => 0.4,
        'heading_case' => 'uppercase',
        'chapter_start_new_page' => true,
        'page_number_position' => 'bottom-center',
        'cover' => [
            'uppercase_title' => true,
            'include_logo' => true,
            'include_advisor' => true,
            'city' => null,
        ],
        'citation_style' => 'apa',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kebijakan template berbayar
    |--------------------------------------------------------------------------
    |
    | Template premium hanya bisa dipakai pengguna dengan langganan aktif.
    | Set false kalau mau membuka semua template untuk semua orang.
    |
    */
    'premium_requires_subscription' => true,

];
