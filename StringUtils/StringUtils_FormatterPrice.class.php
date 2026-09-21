<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class StringUtils_FormatterPrice {

    public static function FormatPricePrecisionPowered($value, $precision, $precision_power10) {
        // важно: отрицательные оно не хавает
        if ($value > 0) {
            // важно: без round не сработает $value = 0.059999999999999; $p = 3;, а во float может быть такая хуйня

            // ручное округление вместо round() дало -20ns
            $n = (int) ($value * $precision_power10 + 0.5);

            if ($precision == 0) {
                // upd: даже форматировать в string не надо, это -2 ns
                return (string) $n;
            } elseif ($n < $precision_power10) { // тут только сравнение по int, без strlen
                return '0.' . str_pad((string) $n, $precision, '0', STR_PAD_LEFT);
            } else {
                // сюда попадаем только когда len > precision → тут уже нужен strlen
                $s = (string) $n;
                return substr_replace($s, '.', strlen($s) - $precision, 0);
            }
        } else {
            return 0;
        }
    }

    public static function FormatPricePrecision($value, $precision) {
        // важно: отрицательные оно не хавает
        if ($value > 0) {
            // важно: не лепить в один метод, это даст +10 ns/call, я проверял
            // важно: без round не сработает $value = 0.059999999999999; $p = 3;, а во float может быть такая хуйня

            // ручное округление вместо round() дало -20ns
            $power = 10 ** $precision;
            $n = (int) ($value * $power + 0.5);

            if ($precision == 0) {
                // upd: даже форматировать в string не надо, это -2 ns
                return (string) $n;
            } elseif ($n < $power) { // тут только сравнение по int, без strlen
                return '0.' . str_pad((string) $n, $precision, '0', STR_PAD_LEFT);
            } else {
                // сюда попадаем только когда len > precision → тут уже нужен strlen
                $s = (string) $n;
                return substr_replace($s, '.', strlen($s) - $precision, 0);
            }
        } else {
            return 0;
        }
    }

}