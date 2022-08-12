<?php

use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\ENV;

/**
 * It prints received variable in the proper way.

 * @package https://github.com/arsavinel/ArshWell
 */
function _vd ($variable, string $description = NULL): void {
    if (ENV::supervisor() == false) {
        return;
    }

    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    echo PHP_EOL;
    echo('<span style="font: 9px/12px monospace; margin-bottom: 2px; padding: 0px 2px 1px 0px; border-bottom: 1px solid rgba(0,0,0,0.1);">'.
        PHP_EOL .'  '. Folder::shorter($bt[0]['file']).': '.$bt[0]['line'].
        PHP_EOL .'  '. '<span style="color: blue;">('. gettype($variable) .')</span>'.
        ($description ? PHP_EOL .'  = <span style="font-size: 10px; color: red;">'. $description .'</span>' : '').
        PHP_EOL.
    '</span>');

    echo PHP_EOL . '<pre style="color: inherit; margin-top: 1px;">';
        if (is_array($variable)) {
            print_r($variable);
        }
        else if (is_object($variable) || is_bool($variable) || is_null($variable) || is_resource($variable)) {
            var_dump($variable);
        }
        else {
            echo htmlentities($variable, ENT_IGNORE | ENT_NOQUOTES);
        }
    echo '</pre>' . PHP_EOL;
}
