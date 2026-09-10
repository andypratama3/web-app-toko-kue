<?php

namespace App\Support;

class Phone
{
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (substr($phone, 0, 2) === '62') {
            return $phone;
        }

        if (substr($phone, 0, 1) === '0') {
            return '62' . substr($phone, 1);
        }

        return '62' . $phone;
    }
}