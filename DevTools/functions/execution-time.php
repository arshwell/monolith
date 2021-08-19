<?php

use Arsh\Core\ENV;

/**
 * Returns execution time for action inside the closure.

 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
function _execution_time (closure $function): int {
    if (ENV::supervisor() == false) {
        return NULL;
    }

    $time = microtime(true);

        $function();

    return (microtime(true) - $time);
}
