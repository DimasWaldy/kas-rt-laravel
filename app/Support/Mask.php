<?php

namespace App\Support;

class Mask
{
    public static function phone(?string $phone): string
    {
        $phone = trim($phone ?? '');
        if ($phone === '') {
            return '-';
        }

        return str_repeat('*', max(0, strlen($phone) - 4)).substr($phone, -4);
    }

    public static function nik(?string $nik): string
    {
        $nik = trim($nik ?? '');
        if ($nik === '') {
            return '-';
        }

        if (preg_match('/\A[0-9]{16}\z/', $nik)) {
            return substr($nik, 0, 4).'********'.substr($nik, -4);
        }

        return '********'.substr($nik, -2);
    }
}
