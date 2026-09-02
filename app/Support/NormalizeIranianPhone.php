<?php

namespace App\Support;

class NormalizeIranianPhone
{
    public function handle(string $phone): string
    {
        $phone = strtr($phone, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $phone = preg_replace('/[\s-]+/', '', $phone) ?? '';

        return str_starts_with($phone, '+98') ? '0'.substr($phone, 3) : $phone;
    }
}
