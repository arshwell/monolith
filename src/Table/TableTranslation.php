<?php

namespace Arshwell\Monolith\Table;

use Arshwell\Monolith\Table;

/*
 * Class used for maintenance configuration.
*/
abstract class TableTranslation extends Table {

    abstract static function langsPerWebGroup (): array;
}
