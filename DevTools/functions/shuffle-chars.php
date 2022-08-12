<?php

use Arsavinel\Arshwell\Math;

function _shuffle_chars (string $text, int $key = 0, string $flag = NULL, string $chars = NULL): string {
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
