<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Table;

/*
 * Class used for maintenance configuration.
*/
abstract class TableTranslation extends Table {

    abstract static function langsPerWebGroup (): array;
}
