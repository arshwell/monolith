<?php

namespace Arshwell\Monolith\Table;

use Arshwell\Monolith\Table;
use Exception;

/*
 * Class used in hooking functions on updating project (release/rollback).
*/
abstract class TableMigration extends Table {
    const GO_FORWARD = 1;
    const GO_BACKWARD = 2;

    abstract static function migrations (): array;

    /**
     * @param int $migration (at which version to migrate)
     */
    final static function migrate (int $migration = NULL): array {
        $logs = array();

        foreach (static::migrations() as $name => $function) {
            $logs[$name] = (string)$function();
        }

        return $logs;
    }

    /**
     * If you would like to see which migrations have run thus far.
     */
    final static function status () {

    }

    /**
     * Will rollback all of your application's migrations.
     */
    final static function reset () {

    }

    /**
     * Will rollback all of your migrations and then execute the migrate command.
     *
     * @param int $migration (until which version to migrate, after reset)
     */
    final static function refresh (int $migration = NULL) {

    }
}
