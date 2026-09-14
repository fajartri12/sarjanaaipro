<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider pembayaran aktif
    |--------------------------------------------------------------------------
    | "manual" = transfer & konfirmasi admin. Ganti ke midtrans/xendit begitu
    | kredensial gateway tersedia. Business logic tidak perlu diubah.
    */

    'default' => env('PAYMENT_PROVIDER', 'manual'),

    'providers' => [

        /*
        | Kanal transfer manual. Isi `number` dan `holder` di bawah ini.
        | Kanal yang nomornya masih kosong otomatis disembunyikan dari halaman
        | instruksi, jadi tidak perlu menghapus barisnya kalau belum dipakai.
        */
        'manual' => [
            'driver' => 'manual',

            'channels' => [
                [
                    'name' => 'Bank Mandiri',
                    'type' => 'bank',
                    'number' => '1370001234567',
                    'holder' => 'Yayasan Sarjana AI',
                ],
                [
                    'name' => 'Bank Jatim',
                    'type' => 'bank',
                    'number' => '0011002233445',
                    'holder' => 'Yayasan Sarjana AI',
                ],
                [
                    'name' => 'Bank Jago',
                    'type' => 'bank',
                    'number' => '50001234567',
                    'holder' => 'Yayasan Sarjana AI',
                ],
                [
                    'name' => 'OVO',
                    'type' => 'ewallet',
                    'number' => '081234567890',
                    'holder' => 'Yayasan Sarjana AI',
                ],
                [
                    'name' => 'GoPay',
                    'type' => 'ewallet',
                    'number' => '081234567891',
                    'holder' => 'Yayasan Sarjana AI',
                ],
            ],

            // Link WhatsApp admin untuk kirim bukti transfer, mis.
            // 'https://wa.me/628123456789'. Kosongkan kalau tidak dipakai.
            'contact' => 'https://wa.me/628123456789',
        ],

        'midtrans' => [
            'driver' => 'midtrans',
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
            'base_url' => env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://api.midtrans.com/v2'
                : 'https://api.sandbox.midtrans.com/v2',
        ],

        'xendit' => [
            'driver' => 'xendit',
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
            'base_url' => 'https://api.xendit.co',
        ],

    ],

];
