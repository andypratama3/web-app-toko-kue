<?php

namespace App\Services\WhatsApp;

use App\Models\Region;
use App\Support\Phone;

/**
 * Satu sumber nomor admin/owner (D1): BOT_ADMIN_PHONE diutamakan,
 * regions.owner_phone hanya fallback. Dipakai sambutan pelanggan dan
 * seluruh notifikasi operasional agar tujuan pesan selalu sama.
 */
class AdminContactResolver
{
    public function phoneFor(?Region $region): ?string
    {
        $configured = trim((string) config('bot.admin_phone'));
        if ($configured !== '') {
            $normalized = Phone::normalize($configured);
            if (mb_strlen($normalized) >= 10) {
                return $normalized;
            }
        }

        $owner = trim((string) $region?->owner_phone);
        if ($owner === '') {
            return null;
        }

        $normalized = Phone::normalize($owner);

        return mb_strlen($normalized) >= 10 ? $normalized : null;
    }
}
