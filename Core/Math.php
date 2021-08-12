<?php

namespace Arsh\Core;

use Arsh\Core\Func;

/**
 * Core class for backend programming which has rutine functions.

 * @package App
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
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
}
