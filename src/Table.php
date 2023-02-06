<?php

namespace Arsavinel\Arshwell;

use Arsavinel\Arshwell\Table\TableField;
use Arsavinel\Arshwell\Table\TableFiles;
use Arsavinel\Arshwell\Language;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\DB;

/**
 * Table class for manipulating certain table and its columns.
*/
abstract class Table {
    const IMAGE         = 1;
    const IMAGE_GROUP   = 2;
    const DOC           = 3;
    const DOC_GROUP     = 4;

    const TRANSLATOR = Language::class;

    private static $structures = array();

    private $id_table       = NULL;
    private $columns        = array();
    private $translations   = array();

    private $files      = NULL;
    private $load_files = false; // settled by object constructor
    private $need_files = false; // settled by children's class FILES constant

    final static function isTranslated (): bool {
        return (
            defined(static::class . '::TRANSLATOR') &&
            !empty(((static::class)::TRANSLATOR)::LANGUAGES) &&
            count(((static::class)::TRANSLATOR)::LANGUAGES) > 1 &&
            defined(static::class . '::TRANSLATED') &&
            !empty((static::class)::TRANSLATED)
        );
    }

    final function files (): ?TableFiles {
        return $this->files;
    }

    final function file (string $filekey): ?object {
        return ($this->files ? $this->files->get($filekey) : NULL);
    }

    static function fileAccess (int $id_table, string $file): bool {
        return true;
    }

    final function __construct (array $columns = NULL, bool $load_files = false) {
        if (!isset(self::$structures[static::class])) {
            // Used only by objects, for removing unnecesary data from array before inserting/updating.
            self::$structures[static::class] = array_flip(array_column(self::columns(), 'COLUMN_NAME'));
            if ((static::class)::PRIMARY_KEY) {
                unset(self::$structures[static::class][(static::class)::PRIMARY_KEY]);
            }
        }

        if ($columns) {
            if ((static::class)::PRIMARY_KEY) {
                $this->id_table = $columns[(static::class)::PRIMARY_KEY];
                unset($columns[(static::class)::PRIMARY_KEY]);
            }
            $this->columns = $columns;

            if (defined(static::class ."::TRANSLATOR") && defined(static::class ."::TRANSLATED")) {
                $languages = (static::TRANSLATOR)::LANGUAGES;

                foreach ((static::class)::TRANSLATED as $column) {
                    foreach ($languages as $language) {
                        $values = array();

                        if (isset($this->columns[$column.'_'.$language])) {
                            $values[] = &$this->columns[$column.'_'.$language];
                        }

                        $this->translations[$column] = new TableField(static::class, $this->id_table, $column);
                    }
                }
            }

            if (defined(static::class ."::FILES") && $load_files) {
                $this->files = new TableFiles(static::class, $this->id_table);
            }
        }

        $this->need_files = defined(static::class ."::FILES");
        $this->load_files = $load_files;
    }

    final function id (): ?int {
        return $this->id_table;
    }

    final function add (): int {
        $columns = array_intersect_key($this->columns, self::$structures[static::class]);

        $this->id_table = DB::insert(
            static::class,
            implode(', ', array_keys($columns)),
            rtrim(str_repeat('?, ', count($columns)), ', '),
            array_values($columns)
        );

        if ($this->need_files && $this->load_files) {
            $this->files = new TableFiles(static::class, $this->id_table);
        }

        return $this->id_table;
    }

    final function edit (): bool {
        $columns = array_intersect_key($this->columns, self::$structures[static::class]);

        return (DB::update(array(
            'class' => static::class,
            'set'   => implode(' = ?, ', array_keys($columns)) .' = ?',
            'where' => (static::class)::PRIMARY_KEY .' = '. $this->id_table
        ), array_values($columns)) > 0);
    }

    final function remove (): bool {
        return (DB::delete(static::class, (static::class)::PRIMARY_KEY ." = ?", array($this->id_table)) > 0);
    }

    function translations (string $column): array {
        return ($this->translations[$column])->values();
    }

    function translation (string $column, string $language = NULL): ?string {
        return ($this->translations[$column])->value(($language ?? (static::TRANSLATOR)::get()));
    }

    function translate (string $column, string $language, string $value = NULL): void {
        $this->columns[$column.'_'.$language] = $value;

        ($this->translations[$column])->set($this->columns[$column.'_'.$language], $language);
    }

    final function isset (string $name): bool {
        return (isset($this->columns[$name]) || isset($this->translations[$name]));
    }

    final function __get (string $name) {
        return $this->columns[$name];
    }
    final function __set (string $name, $value): void {
        $this->columns[$name] = $value;
    }

    final function toArray (bool $add_primary_key = false): array {
        if ($add_primary_key) {
            return array_merge(
                array((static::class)::PRIMARY_KEY => $this->id_table),
                $this->columns
            );
        }
        return $this->columns;
    }
    final function toJSON (bool $add_primary_key = false): string {
        if ($add_primary_key) {
            return json_encode(array_merge(
                array((static::class)::PRIMARY_KEY => $this->id_table),
                $this->columns
            ));
        }
        return json_encode($this->columns);
    }
    final function __toString () {
        return static::class;
    }

    protected static function format (array $row): array {
        return $row;
    }

    /* DLQ (Data Query Language) */

        /**
         * @return ?static
         */
        final static function get (int $id, string $columns = NULL): ?Table {
            if (trim($columns) != '*' && (static::class)::PRIMARY_KEY && !preg_match("/(^(\s+)?|.+,(\s+)?)". (static::class)::PRIMARY_KEY ."((\s+)?,.+|$)/", $columns)) {
                $columns = (trim($columns) ? ((static::class)::PRIMARY_KEY .', '. $columns) : (static::class)::PRIMARY_KEY);
            }

            $result = DB::get(static::class, $id, $columns);

            return ($result ? new static(static::format($result), true) : NULL);
        }

        final static function column (string $column, string $where = NULL, array $params = NULL): array {
            return DB::column(static::class, $column, $where, $params);
        }

        final static function field (string $column, string $where = NULL, array $params = NULL): ?string {
            return DB::field(static::class, $column, $where, $params);
        }

        /**
         * @return ?static
         */
        final static function first (array $sql, array $params = NULL): ?Table {
            $sql['class'] = static::class;

            if (trim($sql['columns']) != '*' && (static::class)::PRIMARY_KEY && !preg_match("/(^(\s+)?|.+,(\s+)?)". (static::class)::PRIMARY_KEY ."((\s+)?,.+|$)/", $sql['columns'])) {
                $sql['columns'] = (static::class)::PRIMARY_KEY .', '. $sql['columns'];

                if (!empty($sql['join'])) {
                    $sql['columns'] = (static::class)::TABLE .'.'. $sql['columns'];
                }
            }
            if (!isset($sql['files']) || !is_bool($sql['files'])) {
                $sql['files'] = false; // don't load, by default, files in Table object
            }

            $result = DB::first($sql, $params);

            return ($result ? new static(static::format($result), $sql['files']) : NULL);
        }

        final static function count (string $where = NULL, array $params = NULL): int {
            return DB::count(static::class, $where, $params);
        }

        /**
         * @return static[]
         */
        final static function all (string $columns = NULL, string $order = NULL): array {
            if (trim($columns) != '*' && (static::class)::PRIMARY_KEY && !preg_match("/(^(\s+)?|.+,(\s+)?)". (static::class)::PRIMARY_KEY ."((\s+)?,.+|$)/", $columns)) {
                $columns = (static::class)::PRIMARY_KEY . ($columns ? (', '. $columns) : '');
            }

            return (array_map(function ($row) use ($columns) {
                return new static(static::format($row), true);
            }, DB::all(static::class, $columns, $order)) ?? array());
        }

        /**
         * @return static[]
         */
        final static function select (array $sql, array $params = NULL): array {
            $sql['class'] = static::class;

            if (trim($sql['columns']) != '*' && (static::class)::PRIMARY_KEY && !preg_match("/(^(\s+)?|.+,(\s+)?)". (static::class)::PRIMARY_KEY ."((\s+)?,.+|$)/", $sql['columns'])) {
                $sql['columns'] = (static::class)::PRIMARY_KEY . ($sql['columns'] ? (', '. $sql['columns']) : '');

                if (!empty($sql['join'])) {
                    $sql['columns'] = (static::class)::TABLE .'.'. $sql['columns'];
                }
            }
            if (!isset($sql['files']) || !is_bool($sql['files'])) {
                $sql['files'] = false; // don't load, by default, files in Table object
            }

            if (!isset($sql['sort'])) {
                return (array_map(function ($row) use ($sql) {
                    return (new static(static::format($row), $sql['files']));
                }, DB::select($sql, $params)) ?? array());
            }
            else {
                $results = DB::select($sql, $params);
                foreach ($results as $i => $result) {
                    foreach ($result as $j => $row) {
                        $results[$i][$j] = new static(static::format($row), $sql['files']);
                    }
                }
                return $results;
            }
        }

    /* DML (Data Manipulation Language) */

        final static function insert (string $columns, $values, array $params = NULL): int {
            return DB::insert(static::class, $columns, $values, $params);
        }

        final static function update (array $sql, array $params = NULL): int {
            $sql['class'] = static::class;

            return DB::update($sql, $params);
        }

        final static function delete (string $where = NULL, array $params = NULL): int {
            return DB::delete(static::class, $where, $params);
    }

    /* DDL (Data Definition Language) */

        final static function columns (bool $add_primary_key = false): array {
            return DB::columnsTable((static::class)::TABLE, $add_primary_key);
        }

        final static function truncate () {
            DB::truncateTable((static::class)::TABLE);
        }
}
