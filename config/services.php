<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp' => [
        'phone_number_id' => env('META_PHONE_NUMBER_ID'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'webhook_secret' => env('META_WEBHOOK_SECRET'),
        'graph_version' => env('META_VERSION', 'v21.0'),
        'bot_name' => env('META_BOT_NAME', 'Kue Pandan Asli'),
        'default_region' => env('META_DEFAULT_REGION', 'Denpasar'),
        // Zona waktu bisnis untuk parsing tanggal & sapaan bot.
        // Penyimpanan DB tetap UTC; ini hanya untuk "hari ini/besok" yang benar.
        'timezone' => env('BOT_TIMEZONE', 'Asia/Jakarta'),
        // ID WhatsApp Business Account (untuk ambil daftar template broadcast)
        'waba_id' => env('META_WABA_ID'),
        // Berapa menit tanpa aktivitas sampai percakapan tengah-order dianggap buntu dan direset
        'session_expire_minutes' => (int) env('META_SESSION_EXPIRE_MINUTES', 60),
        // Jeda (detik) antar pengiriman broadcast agar tidak kena rate limit Meta
        'broadcast_interval_seconds' => (int) env('BROADCAST_INTERVAL_SECONDS', 1),
        // Berapa recipient diproses per-batch pada job broadcast
        'broadcast_batch_size' => (int) env('BROADCAST_BATCH_SIZE', 50),
        // Template approved Meta untuk notifikasi ke nomor owner cabang.
        // Bila diisi, notifikasi owner dikirim via template (bisa menembus
        // jendela 24 jam). Bila kosong, fallback ke pesan teks biasa.
        // Isi 1 variabel body ({{1}}) dengan format pesanan.
        'owner_notify_template' => env('META_OWNER_NOTIFY_TEMPLATE'),
        'owner_notify_template_lang' => env('META_OWNER_NOTIFY_TEMPLATE_LANG', 'id'),
    ],

];
