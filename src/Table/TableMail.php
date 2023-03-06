<?php

namespace ArshWell\Monolith\Table;

use ArshWell\Monolith\Table;

abstract class TableMail extends Table {
    const MAILS_PER_DELIVERING = 100;

    final static function deliver (): void {

    }
}
