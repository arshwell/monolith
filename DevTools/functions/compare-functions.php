<?php

use Arsh\Core\ENV;

/**
 * Returns array representing how many times every closure was fastest.

 * @package App/DevTools
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 */
function _compare_functions (array $funcs, int $counter = 1000): array {
    if (ENV::supervisor() == false) {
        return NULL;
    }

    $score = array();
    $speed = array();
    foreach ($funcs as $name => $func) {
        $score[$name] = 0;
        $speed[$name] = 0;
    }

    for ($i = 0; $i < $counter; $i++) {
        foreach ($speed as &$val) {
            $val = 0;
            unset($val);
        }

        foreach ($funcs as $name => $func) {
            $time = microtime(true);

                $func();

            $speed[$name] = microtime(true) - $time;
        }

        foreach (array_keys($speed, min($speed)) as $key) {
            $score[$key]++;
        }
    }

    return $score;
}
