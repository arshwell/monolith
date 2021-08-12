<?php

namespace Arsh\Core\Module\Syntax\Backend;

final class PHP {

    static function validation (array $validation): array {
        foreach ($validation as $key => $value) {
            $validation[$key] = ("Arsh\Core\Module\Syntax\Backend\PHP\Validation::{$key}")($value);
        }

        return $validation;
    }
}
