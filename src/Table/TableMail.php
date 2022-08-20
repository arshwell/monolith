<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Table;

abstract class TableMail extends Table {
    const MAILS_PER_DELIVERING = 100;

    final static function deliver (): void {

    }
}
