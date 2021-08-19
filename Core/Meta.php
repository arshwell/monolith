<?php

namespace Arsh\Core;

/**
 * Helper class for organizing meta tags from <head> section of html.

 * @package Arsh/Core/Core
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
*/
final class Meta {
    private static $metas = array();

    static function set (string $meta, string $value) {
        self::$metas[$meta] = $value;
    }

    static function exists (string $meta): bool {
        return isset(self::$metas[$meta]);
    }
    static function get (string $meta): string {
        return self::$metas[$meta];
    }

    // get wanted metas
    static function array (array $metas): array {
        return array_intersect_key(self::$metas, array_flip($metas));
    }
}
