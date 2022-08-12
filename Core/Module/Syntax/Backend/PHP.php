<?php

namespace Arsavinel\Arshwell\Module\Syntax\Backend;

final class PHP {

    static function validation (array $validation): array {
        foreach ($validation as $key => $value) {
            $validation[$key] = ("Arsavinel\Arshwell\Module\Syntax\Backend\PHP\Validation::{$key}")($value);
        }

        return $validation;
    }
}
