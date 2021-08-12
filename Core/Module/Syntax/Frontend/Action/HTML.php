<?php

namespace Arsh\Core\Module\Syntax\Frontend\Action;

class HTML {

    static function icon (string $icon = NULL): ?string {
        return $icon;
    }

    static function text (string $text): string {
        return $text;
    }

    static function href (string $href): string {
        return $href;
    }

    static function type (string $name): string {
        return $name;
    }

    static function class (string $class): string {
        return $class;
    }

    static function disabled (bool $disabled): bool {
        return $disabled;
    }

    /**
     * (closure|bool) $hidden
    */
    static function hidden ($hidden) {
        return $hidden;
    }

    static function values (array $values): array {
        return $values;
    }
}
