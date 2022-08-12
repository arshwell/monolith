<?php

use Arsavinel\Arshwell\ENV;

/**
 * It prints received variable in the proper way.

 * @package https://github.com/arsavinel/ArshWell
 */
function _br (int $repeats = 1): void {
    if (ENV::supervisor() == false) {
        return;
    }

    echo PHP_EOL . str_repeat('<br>', max(1, $repeats)) . PHP_EOL;
}
