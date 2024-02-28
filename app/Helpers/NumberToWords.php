<?php

namespace App\Helpers;

class NumberToWords
{
    public static function convert($number)
    {
        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        $teens = ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        // Convert the number to words
        $words = '';

        $integerPart = (int)$number;
        $decimalPart = intval(($number - $integerPart) * 100);

        if ($integerPart > 0) {
            $words .= self::convertNumber($integerPart) . ' rupees ';
        }

        if ($decimalPart > 0) {
            $words .= 'and ' . self::convertNumber($decimalPart) . ' paise';
        }

        return ucfirst(trim($words));
    }

    private static function convertNumber($number)
    {
        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        $teens = ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $words = '';

        if ($number >= 1000) {
            $words .= self::convertNumber(intval($number / 1000)) . ' thousand ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $words .= $units[intval($number / 100)] . ' hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $words .= $tens[intval($number / 10)];
            $number %= 10;
            if ($number > 0) $words .= ' ';
        }

        if ($number > 0) {
            if ($number < 10) $words .= $units[$number];
            elseif ($number >= 10) $words .= $teens[$number - 10];
        }

        return $words;
    }
}
