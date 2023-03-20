<?php

namespace Arshwell\Monolith\Module\Syntax\Frontend\Feature;

final class HTML {

    /**
     * (array|string) $icon
    */
    static function icon ($icon = NULL) {
        return $icon;
    }

    static function text (string $text): string {
        return $text;
    }

    /**
     * (closure|bool) $hidden
    */
    static function href ($href) {
        return $href;
    }

    static function target (string $target): string {
        return $target;
    }

    static function type (string $name): string {
        return $name;
    }

    static function title (string $title = NULL): ?string {
        return $title;
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
