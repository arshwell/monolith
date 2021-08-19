<?php

namespace Arsh\Core;

/**
 * Helper class for adding certain pieces of HTML.

 * @package Arsh/Core/Core
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
*/
final class HTML {
    static function iFrame (string $url): string {
        return '<iframe src="'. $url .'"></iframe>';
    }

    /**
    * Made to be used in form, against CSRF attacks.
    * It uses a Session token, which will be searched for, at validation time.

    * @return input (a hidden one), with token value
    */
    public static function formToken (): string {
        return '<input type="hidden" name="form_token" value="'. Session::token('form') .'" />';
    }
}
