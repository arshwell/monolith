<?php

namespace ArshWell\Monolith\Table;

use ArshWell\Monolith\Table;

/*
 * Class used for maintenance configuration.
*/
abstract class TableTranslation extends Table {

    abstract static function langsPerWebGroup (): array;
}
