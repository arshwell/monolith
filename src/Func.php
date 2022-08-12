<?php

namespace Arsavinel\Arshwell;

/**
 * Class for backend programming which has routine functions.

 * @package https://github.com/arsavinel/ArshWell
*/
final class Func {

    static function rShuffle (array $list): array {
        $keys = array_keys($list);
        shuffle($keys);

        $random = array();
        foreach ($keys as $key) {
            $random[$key] = (!is_array($list[$key]) ? $list[$key] : self::rShuffle($list[$key]));
        }

        return $random;
    }

    static function keyFromBiggest (array $array): ?string {
        $max = NULL;
        $biggest = NULL;

        foreach ($array as $key => $number) {
            if (($max ?? $number) <= $number) {
                $max = $number;
                $biggest = $key;
            }
        }

        return $biggest;
    }

    static function keyFromSmallest (array $array): ?string {
        $min = NULL;
        $smallest = NULL;

        foreach ($array as $key => $number) {
            if (($min ?? $number) >= $number) {
                $min = $number;
                $smallest = $key;
            }
        }

        return $smallest;
    }

    static function closestUp (int $number, array $array): ?int {
        sort($array);

        foreach ($array as $a) {
            if ($a >= $number) {
                return $a;
            }
        }
        return NULL;
    }

    static function closestDown (int $number, array $array): ?int {
        rsort($array);

        foreach ($array as $a) {
            if ($a <= $number) {
                return $a;
            }
        }
        return NULL;
    }

    static function rUnique (array $array): array {
        return array_intersect_key($array, array_unique(array_map('serialize', $array)));
    }

    /**
     * Convert a multi-dimensional array into a single-dimensional array.
     * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
     *
     * @param array $array The multi-dimensional array.
     */
    static function arrayFlatten (array $array, bool $preserve_keys = false): array {
        $result = array();

        if ($preserve_keys == false) {
            $array = array_values($array);
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($preserve_keys == false) {
                    $value = array_values($value);
                }

                $result = array_merge($result, self::arrayFlatten($value));
            }
            else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    /**
     * Convert a multi-dimensional array into a single-dimensional array, keeping tree keys.
     *
     * @param string $prefix
     * @param mixed $value
     * @param string $separator
     * @param bool $add_subarrays
     *
     * @return array
     */
    static function arrayFlattenTree (array $value, string $prefix = NULL, string $separator = '-', bool $add_subarrays = false): array {
        $array = array();

        if (!self::isAssoc($value)) {
            $array[$prefix] = $value;
        }
        else {
            foreach ($value as $k => $v) {
                $newprefix = ($prefix ? $prefix.$separator.$k : $k);

                if (is_array($v)) {
                    // NOTE: array_merge_recursive is not good
                    $array = array_replace_recursive($array, self::arrayFlattenTree($v, $newprefix, $separator, $add_subarrays));
                }
                else if ($prefix) {
                    if ($add_subarrays) {
                        $array[$prefix][$k] = $v;
                    }
                    $array[$newprefix] = $v;
                }
            }
        }

        return $array;
    }

    static function isAssoc (array $array): bool {
        return (array_keys($array) !== range(0, count($array) - 1));
    }

    static function hasValidJSON (string $file): bool {
        json_decode(file_get_contents($file));

        return (json_last_error() == JSON_ERROR_NONE);
    }
}
