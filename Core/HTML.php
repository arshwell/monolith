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

    public static function convertLinksToHyperlinks(string $text): string {
        return preg_replace(
            '/(https?:\/\/)?(www\.)?(?<=\s|\A)([0-9a-zA-Z\-\.]+\.[a-zA-Z0-9\/]{2,})[-a-zA-Z0-9()@:%_\+.~#?&\/=]*/',
            '<a href="//$0" style="font-weight: normal;" target="_blank" title="$0">$0</a>',
            $text
        );
    }
}
