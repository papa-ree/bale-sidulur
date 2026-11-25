<?php

namespace Bale\BaleSidulur\Support;

class BlindIndex
{
    /**
     * Hash deterministik untuk verifikasi NIK/KK/HP pada DB tenant.
     * Normalisasi HARUS identik dengan bale/sidulur admin.
     */
    public static function make(?string $value): ?string
    {
        $normalized = static::normalize($value);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('sidulur.index_key'));
    }

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
