<?php

use Arshwell\Monolith\ENV;

// verify because could be already user-defined
if (function_exists('_br') == false) {
    /**
     * It prints received variable in the proper way.

    * @package https://github.com/arshwell/monolith
    */
    function _br (int $repeats = 1): void {
        if (ENV::supervisor() == false) {
            return;
        }

        echo PHP_EOL . str_repeat('<br>', max(1, $repeats)) . PHP_EOL;
    }
}
