<?php

namespace App\Services\Otp;

use InvalidArgumentException;

final class IranianMobile
{
    public static function normalize(string $value): string
    {
        $english = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/[^0-9+]/', '', $english) ?? '';

        if (preg_match('/^09\d{9}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^(?:\+98|0098)9\d{9}$/', $digits)) {
            return '0'.substr($digits, -10);
        }

        if (preg_match('/^989\d{9}$/', $digits)) {
            return '0'.substr($digits, 2);
        }

        throw new InvalidArgumentException('شماره موبایل معتبر ایرانی وارد کنید.');
    }

    public static function display(string $normalized): string
    {
        return self::normalize($normalized);
    }
}
