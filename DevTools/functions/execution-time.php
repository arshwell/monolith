<?php

use Arsh\Core\ENV;

/**
 * Returns execution time for action inside the closure.

 * @package App/DevTools
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 */
function _execution_time (closure $function): int {
    if (ENV::supervisor() == false) {
        return NULL;
    }

    $time = microtime(true);

        $function();

    return (microtime(true) - $time);
}
