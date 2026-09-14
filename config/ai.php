<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    | "null" dipakai saat belum ada API key: seluruh fitur AI tetap bisa dites
    | karena provider null mengembalikan output contoh yang bentuknya sama.
    |
    | Pakai `?:` bukan `??`: "null" adalah kata kunci di .env, jadi Dotenv
    | mengubahnya menjadi PHP null. Tanpa `?:`, AI_PROVIDER=null bikin alias
    | provider jadi kosong dan aplikasi meledak alih-alih memakai contoh.
    */

    'default' => env('AI_PROVIDER') ?: 'null',

    'providers' => [

        'null' => [
            'driver' => 'null',
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('AI_TIMEOUT', 120),
        ],

        'openrouter' => [
            'driver' => 'openai', // openrouter kompatibel dengan API OpenAI
            'key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
            'timeout' => (int) env('AI_TIMEOUT', 120),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Harga untuk estimasi biaya (rupiah per 1 juta token)
    |--------------------------------------------------------------------------
    | Dipakai AiUsageRecorder supaya margin SaaS kelihatan di admin.
    | Tambahkan model baru di sini saat provider berganti.
    */

    'pricing' => [
        'gpt-4o-mini' => ['input' => 3_600, 'output' => 14_400],
        'gpt-4o' => ['input' => 36_000, 'output' => 144_000],
        'openai/gpt-4o-mini' => ['input' => 3_600, 'output' => 14_400],
        // Endpoint sendiri, bukan per-token OpenAI. Isi sesuai biaya langganan;
        // angka ini hanya untuk laporan margin admin, tidak menagih siapa pun.
        'Combo-Muncrat' => ['input' => 0, 'output' => 0],
        'default' => ['input' => 5_000, 'output' => 15_000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas kuota default kalau plan tidak mendefinisikan limitnya sendiri
    |--------------------------------------------------------------------------
    */

    'default_limits' => [
        'generate_titles' => 5,
        'ai_chat' => 10,
        'projects' => 1,
    ],

];
