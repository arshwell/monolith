<?php

use Arsh\Core\ENV;

/**
 * It prints received variable in the proper way.

 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
function _br (int $repeats = 1): void {
    if (ENV::supervisor() == false) {
        return;
    }

    echo PHP_EOL . str_repeat('<br>', max(1, $repeats)) . PHP_EOL;
}
