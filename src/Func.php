<?php

namespace Arshwell\Monolith;

/**
 * Class for backend programming which has routine functions.

 * @package https://github.com/arshwell/monolith
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

                // NOTE: array_merge is not good (it doesn't preserve keys)
                $result = array_replace($result, self::arrayFlatten($value, $preserve_keys));
            }
            else {
                // NOTE: array_merge is not good (it doesn't preserve keys)
                $result = array_replace($result, array($key => $value));
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

        if (!self::isAssoc($value, false)) {
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

    static function isAssoc (array $array, bool $check_if_zero_indexed = true): bool {
        if ($check_if_zero_indexed) {
            return (array_keys($array) !== range(0, count($array) - 1));
        }

        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    static function hasSubarrays (array $array): bool {
        return count(array_filter(array_keys($array), 'is_array')) > 0;
    }

    static function hasValidJSON (string $file): bool {
        json_decode(file_get_contents($file));

        return (json_last_error() == JSON_ERROR_NONE);
    }
}
