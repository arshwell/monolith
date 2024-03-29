<?php

namespace Arshwell\Monolith;

/**
 * Class for time converting.

 * @package https://github.com/arshwell/monolith
*/
final class Time {
    static function readableTime (int $ms, int $precision = 2, string $separator = '', $units = array('ms','s','m','h')): string {
        if ($ms < 1000) {
            return round($ms, $precision).$separator.($units[0] ?? 'ms');
        }

        $seconds = $ms / 1000;
        if ($seconds < 60) {
            return round($seconds, $precision).$separator.($units[1] ?? 's');
        }

        $minutes = $seconds / 60;
        if ($minutes < 60) {
            return round($minutes, $precision).$separator.($units[2] ?? 'm');
        }

        $hours = $minutes / 60;
        return round($hours, $precision).$separator.($units[3] ?? 'h');
    }

    /**
     * Returns shorter date if about today.
     */
    static function readableDate (int $time, array $translate = array()): string {
        return str_replace(
            array_keys($translate),
            array_values($translate),
            date(
                (date('Ymd', $time) != date('Ymd') ? "d F " : '') .
                (date('Y', $time) != date('Y') ? "Y " : '') .
                (date('Ymd H:i', $time) != date('Ymd H:i') ? "H:i" : '\n\o\w'),
                $time
            )
        );
    }

    static function secondsToDate (int $seconds): string {
        return self::readableTime($seconds.'000');
    }

    /**
     * Convert ISO 8601 values like P2DT15M33S
     * to a total value of seconds.
     *
     * @param string $ISO8601
     */
    static function ISO8601ToSeconds (string $ISO8601): int {
        $interval = new \DateInterval($ISO8601);

        return ($interval->d * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
    }
}
