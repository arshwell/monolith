<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\Table\TableField;
use Arshwell\Monolith\Table\TableFiles;
use Arshwell\Monolith\DB;

/**
 * Table class for manipulating certain table and its columns.
*/
abstract class Table {
    const DB_CONN = null;

    const FILES_NAMESPACE = null;

    const FILE_IMAGE        = 1;
    const FILE_IMAGE_GROUP  = 2;
    const FILE_DOC          = 3;
    const FILE_DOC_GROUP    = 4;

    private static $structures = array();

    private $id_table       = NULL;
    private $columns        = array();
    private $translations   = array();

    private $files = NULL;
    private $addTableFiles = false; // settled by constructor, via object parameter
    private $usesTableFiles = false; // settled by constructor, via children's class FILES constant

    /**
     * Get number of translation times of the table or a certain column.
     *
     * Also it lets the user know if a table has translated columns.
     */
    final static function translationTimes (string $column = NULL): int {
        $count = 0;

        $isTableTranslated = (
            defined(static::class . '::TRANSLATOR') &&
            !empty(((static::class)::TRANSLATOR)::LANGUAGES)
        );

        if ($isTableTranslated) {
            $count = count(((static::class)::TRANSLATOR)::LANGUAGES);

            if ($column != NULL) {
                $isColumnTranslated = (
                    defined(static::class . '::TRANSLATED') &&
                    !empty((static::class)::TRANSLATED) &&
                    in_array($column, (static::class)::TRANSLATED)
                );

                if ($isColumnTranslated == false) {
                    return 0;
                }
            }
        }

        return $count;
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

    final function __construct (array $columns = NULL, bool $addTableFiles = false, string $fileStorageKey = null) {
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
                $languages = ((static::class)::TRANSLATOR)::LANGUAGES;

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

            if (defined(static::class ."::FILES") && $addTableFiles) {
                $this->files = new TableFiles(static::class, $this->id_table, $fileStorageKey);
            }
        }

        $this->usesTableFiles = defined(static::class ."::FILES");
        $this->addTableFiles = $addTableFiles;
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

        if ($this->usesTableFiles && $this->addTableFiles) {
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
        return ($this->translations[$column])->value(($language ?? ((static::class)::TRANSLATOR)::get()));
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

            return ($result ? new static(static::format($result), $sql['files'], $sql['fileStorageKey'] ?? (defined(static::class ."::FILESTORAGE_KEY") ? (static::class)::FILESTORAGE_KEY : null)) : NULL);
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
                    return (new static(static::format($row), $sql['files'], $sql['fileStorageKey'] ?? (defined(static::class ."::FILESTORAGE_KEY") ? (static::class)::FILESTORAGE_KEY : null)));
                }, DB::select($sql, $params)) ?? array());
            }
            else {
                $results = DB::select($sql, $params);
                foreach ($results as $i => $result) {
                    foreach ($result as $j => $row) {
                        $results[$i][$j] = new static(static::format($row), $sql['files'], $sql['fileStorageKey'] ?? (defined(static::class ."::FILESTORAGE_KEY") ? (static::class)::FILESTORAGE_KEY : null));
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
