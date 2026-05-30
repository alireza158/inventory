<?php

namespace App\Support;

class Currency
{
    public const RIAL_PER_TOMAN = 10;

    public static function toRial(int|float|string|null $toman): int
    {
        return max(0, (int) $toman) * self::RIAL_PER_TOMAN;
    }

    public static function formatRial(int|float|string|null $toman): string
    {
        return number_format(self::toRial($toman)) . ' ریال';
    }

    public static function formatRialNumber(int|float|string|null $toman): string
    {
        return number_format(self::toRial($toman));
    }

    public static function formatRawRial(int|float|string|null $rial): string
    {
        return number_format(max(0, (int) $rial)) . ' ریال';
    }

    public static function formatRawRialNumber(int|float|string|null $rial): string
    {
        return number_format(max(0, (int) $rial));
    }

    public static function rialToToman(int|float|string|null $rial): int
    {
        return max(0, (int) floor(((int) $rial) / self::RIAL_PER_TOMAN));
    }

    public static function rialInputToToman(mixed $value): int
    {
        $digits = preg_replace('/[^0-9]/', '', self::englishDigits((string) ($value ?? '')));
        if ($digits === '' || $digits === null) {
            return 0;
        }

        return max(0, (int) floor(((int) $digits) / self::RIAL_PER_TOMAN));
    }

    private static function englishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
