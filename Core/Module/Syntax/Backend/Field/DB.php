<?php

namespace Arsh\Core\Module\Syntax\Backend\Field;

final class DB {

    static function column (string $column): string {
        return $column;
    }

    static function type (string $type): string {
        return $type;
    }

    static function from (array $table): array {
        return $table;
    }
}
