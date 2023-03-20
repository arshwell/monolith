<?php

namespace Arshwell\Monolith\Table;

use Arshwell\Monolith\Table;

abstract class TableMail extends Table {
    const MAILS_PER_DELIVERING = 100;

    final static function deliver (): void {

    }
}
