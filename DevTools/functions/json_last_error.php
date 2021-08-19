<?php

use Arsh\Core\ENV;

/**
 * It prints received variable in the proper way.

 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
function _json_last_error (): void {
    if (ENV::supervisor() == false) {
        return;
    }

    switch (json_last_error()) {
        case JSON_ERROR_NONE: {
            throw new Exception('JSON_LAST_ERROR: No errors');
            break;
        }
        case JSON_ERROR_DEPTH: {
            throw new Exception('JSON_LAST_ERROR: Maximum stack depth exceeded');
            break;
        }
        case JSON_ERROR_STATE_MISMATCH: {
            throw new Exception('JSON_LAST_ERROR: Underflow or the modes mismatch');
            break;
        }
        case JSON_ERROR_CTRL_CHAR: {
            throw new Exception('JSON_LAST_ERROR: Unexpected control character found');
            break;
        }
        case JSON_ERROR_SYNTAX: {
            throw new Exception('JSON_LAST_ERROR: Syntax error, malformed JSON');
            break;
        }
        case JSON_ERROR_UTF8: {
            throw new Exception('JSON_LAST_ERROR: Malformed UTF-8 characters, possibly incorrectly encoded');
            break;
        }
        default: {
            throw new Exception('JSON_LAST_ERROR: Unknown error');
            break;
        }
    }
}
