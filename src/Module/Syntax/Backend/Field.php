<?php

namespace ArshWell\Monolith\Module\Syntax\Backend;

final class Field {

    static function DB (array $db = NULL): ?array {
        return $db;
    }

    static function PHP (array $PHP): array {
        return $PHP;
    }
}
