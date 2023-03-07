<?php

namespace ArshWell\Monolith\Module;

use ArshWell\Monolith\DB;

final class Backend {

    static function buildDB (array $db, array $features, array $fields, bool $remove_outdated_lg_columns = false): void {
        DB::connect($db['conn']);

        foreach (array_filter(array_column($fields, 'DB')) as $db_field) {
            $class = $db['table'];

            // if column is in another table
            if (isset($db_field['table'])) {
                $class = $db_field['table'];
            }

            if (class_exists($class)) {
                self::updateTableColumn(
                    $class,
                    array(
                        'key'       => $db_field['column'],
                        'type'      => $db_field['type'],
                        'length'    => $db_field['length'] ?? NULL,
                        'null'      => $db_field['null'] ?? false,
                        'default'   => $db_field['default'] ?? NULL
                    ),
                    NULL, // ADD or MODIFY COLUMN
                    $remove_outdated_lg_columns
                );

                // if column is in another table
                if (isset($db_field['table'])) {
                    DB::alterTable(
                        ($class)::TABLE,
                        NULL, // ADD or MODIFY COLUMN
                        ($db['table'])::PRIMARY_KEY,
                        'INT('. self::lengthDB('INT') .') NOT NULL AFTER '. ($class)::PRIMARY_KEY
                    );
                }
            }

            // if getting values from another table
            if (!empty($db_field['from']) && class_exists($db_field['from']['table'])) {
                self::updateTableColumn(
                    $db_field['from']['table'],
                    array(
                        'key' => $db_field['from']['column']
                    ),
                    'ADD',
                    $remove_outdated_lg_columns
                );
            }
        }

        if (!empty($features['order'])) {
            DB::alterTable(
                ($db['table'])::TABLE,
                NULL, // ADD or MODIFY COLUMN
                ($features['order']['column'] ?? 'order'),
                "SMALLINT(4) DEFAULT 0 NOT NULL AFTER ".($db['table'])::PRIMARY_KEY
            );
        }
    }

    /**
     * $alter = NULL |=> ADD or MODIFY COLUMN
     */
    private static function updateTableColumn (string $class, array $column, string $alter = NULL, bool $remove_outdated_lg_columns = false) {
        DB::createTable(($class)::TABLE, array(
            "`". ($class)::PRIMARY_KEY ."` INT NOT NULL AUTO_INCREMENT",
            "PRIMARY KEY (`". ($class)::PRIMARY_KEY ."`)"
        ));

        $tb_columns = array_column(DB::columnsTable(($class)::TABLE), 'COLUMN_NAME');
        $languages = (defined("{$class}::TRANSLATED") && in_array($column['key'], ($class)::TRANSLATED) ? (($class)::TRANSLATOR)::LANGUAGES : array(NULL));

        foreach ($languages as $language) {
            $column['name'] = $column['key'] . ($language ? '_'.$language : '');

            DB::alterTable(
                ($class)::TABLE,
                $alter,
                $column['name'],
                self::alterTableValue($column)
            );

            // copying content from another language column
            if ($language && !in_array($column['name'], $tb_columns)) {
                foreach (array_merge(array((($class)::TRANSLATOR)::default()), $languages) as $lg) {
                    if (in_array($column['key'].'_'.$lg, $tb_columns)) {
                        ($class)::update(array('set' => $column['name'] .' = '. $column['key'].'_'.$lg));
                        break;
                    }
                }
            }
        }

        $action = 'DROP COLUMN';
        $value = NULL;

        if ($remove_outdated_lg_columns == false) {
            $action = 'MODIFY COLUMN';
            $column['null'] = true; // if are hidden, should be optional
            $value = self::alterTableValue($column);
        }

        if (defined("{$class}::TRANSLATED") && in_array($column['key'], ($class)::TRANSLATED)) {
            foreach ($tb_columns as $tb_column) {
                if (preg_match("/{$column['key']}_([a-z]{2})/", $tb_column, $matches)
                && !in_array($matches[1], (($class)::TRANSLATOR)::LANGUAGES)) {
                    DB::alterTable(
                        ($class)::TABLE,
                        $action,
                        $tb_column,
                        $value
                    );
                }
            }
        }
    }

    private static function lengthDB (string $db_type): ?string {
        switch (trim(strtoupper($db_type))) {
            case 'INT': {
                return 11;
            }
            case 'TINYINT': {
                return 1;
            }
            case 'VARCHAR': {
                return 200;
            }
            case 'TEXT': {
                return 10000;
            }
            default: {
                return NULL;
            }
        }
    }

    private static function alterTableValue (array $column): string {
        return (
            ($column['type'] ?? 'VARCHAR') .
            '('. ($column['length'] ?? self::lengthDB($column['type'] ?? 'VARCHAR')) .')'.
            (isset($column['default']) ? ' DEFAULT '.$column['default'] : '') .
            (($column['null'] ?? false) ? ' NULL' : ' NOT NULL')
        );
    }
}
