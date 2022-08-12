<?php

use Arsavinel\Arshwell\ENV;

/**
 * Returns execution time for action inside the closure.

 * @package https://github.com/arsavinel/ArshWell
 */
function _execution_time (closure $function): int {
    if (ENV::supervisor() == false) {
        return NULL;
    }

    $time = microtime(true);

        $function();

    return (microtime(true) - $time);
}
