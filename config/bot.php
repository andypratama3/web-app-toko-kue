<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deskripsi produk dalam katalog WhatsApp
    |--------------------------------------------------------------------------
    |
    | full       : tampilkan deskripsi penuh produk.
    | only_after_berisi : tampilkan hanya bagian setelah kata "Berisi:"
    |                    (untuk tumpeng / kue mix / hampers — R-13).
    | off        : jangan tampilkan deskripsi sama sekali.
    | default    : only_after_berisi (R-13 aktif) — balik dengan BOT_DESCRIPTION_MODE=full.
    |
    */

    'description_mode' => env('BOT_DESCRIPTION_MODE', 'only_after_berisi'),

    'admin_phone' => env('BOT_ADMIN_PHONE'),

];
