<?php

namespace Arshwell\Monolith\Module\Syntax\Backend\PHP;

use closure;

final class Validation {

    static function class (string $class): string {
        return $class;
    }

    static function valid (closure $function): closure {
        return $function;
    }
}
