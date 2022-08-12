<?php

namespace Arsh\Core;

use Arsh\Core\Session;

abstract class Language {
    const PAGINATION    = "p-([1-9]\\d*)";
    const LANGUAGES     = array('ro');

    final static function get (): string {
        return (Session::language(static::class) ?: self::default());
    }

    final static function set (string $lang): void {
        Session::setLanguage(static::class, $lang);
    }

    static function default(): string {
        return static::LANGUAGES[0];
    }
}
