<?php

namespace Arshwell\Monolith;

/**
 * Class for math functions and calculations.
 *
 * It has routine functions.

 * @package https://github.com/arshwell/monolith
*/
final class Math {
    static function factorial (int $number): int {
        return array_product(range(1, $number));
    }

    static function nthPermutation (array $array, int $nth): array {
        $count = count($array);

        for ($i = 0; $i < $count; $i++) {
            $item   = $nth % $count;
            $nth    = floor($nth / $count);
            $result[] = $array[$item];
            array_splice($array, $item, 1);
        }

        return $result;
    }

    static function resizeKeepingRatio (int $size1, int $size2, int $newSize1): int {
        return $size2 * ($newSize1 / $size1); // returns newSize2
    }

    static function _shuffle_chars (string $text, int $key = 0, string $flag = NULL, string $chars = NULL): string {
        $str = "01234aAbBcCdDeEfFgGhHiIjJkKlLmM<[{(:*.+!=\n \t\r%?-,#;)}]>NnOoPpQqRrSsTtUuVvWwXxYyZz56789";

        if ($flag == 'without') {
            $str = str_replace(str_split($chars), '', $str);
        }
        else if ($flag == 'only') {
            $str = $chars;
        }

        $str = implode('', Math::nthPermutation(str_split($str), $key));

        $text_length = strlen($text);
        $str_length = strlen($str);

        for ($i = 0; $i < $text_length; $i++) {
            if (($char_position = strpos($str, $text[$i])) !== false) {
                $text[$i] = $str[$str_length - 1 - $char_position];
            }
        }
        return strrev($text);
    }
}
