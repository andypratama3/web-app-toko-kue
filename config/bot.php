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
    |
    */

    'description_mode' => env('BOT_DESCRIPTION_MODE', 'full'),

];
