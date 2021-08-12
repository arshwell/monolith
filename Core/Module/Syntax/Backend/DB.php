<?php

namespace Arsh\Core\Module\Syntax\Backend;

final class DB {

    static function conn (string $conn): string {
        return $conn;
    }

    static function table (string $table): string {
        return $table;
    }
}
