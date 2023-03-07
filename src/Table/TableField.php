<?php

namespace ArshWell\Monolith\Table;

use ArshWell\Monolith\Table\TableSegment;
use ArshWell\Monolith\DB;

final class TableField implements TableSegment {
    private $class      = NULL;
    private $id_table   = NULL;
    private $where      = NULL;
    private $key        = NULL;

    private $translated = false;
    private $languages  = array();
    private $values     = array();

    /**
     * (int|string) $where
     * (string|array) $input
    */
    function __construct (string $class, $where = NULL, string $key, $input = NULL) {
        if (defined("{$class}::TRANSLATOR") && defined("{$class}::TRANSLATED")
        && in_array($key, ($class)::TRANSLATED)) {
            $this->translated = true;
            $this->languages = (($class)::TRANSLATOR)::LANGUAGES;
        }

        $id_table = NULL;

        if ($where != NULL && $input == NULL) {
            if (is_numeric($where)) { // int OR numeric string
                $where = ($class)::PRIMARY_KEY . ' = '. $where;
            }

            if ($this->translated == true) {
                $input = DB::first(array(
                    'class'     => $class,
                    'columns'   => ($class)::PRIMARY_KEY. ', '. implode(', ', array_map(function ($lg) use ($key) {
                        return $key.'_'.$lg.' AS '.$lg;
                    }, $this->languages)),
                    'where'     => $where
                ));
            }
            else {
                $input = DB::first(array(
                    'class'     => $class,
                    'columns'   => ($class)::PRIMARY_KEY. ', '. $key,
                    'where'     => $where
                ));
            }

            if ($input) {
                if ($key == ($class)::PRIMARY_KEY) {
                    $id_table = $input[$key]; // copying id table
                }
                else {
                    $id_table = array_shift($input); // extract id table
                }
            }
        }
        else {
            if ($this->translated) {
                if (!is_array($input)) {
                    $input = array_fill_keys($this->languages, $input);
                }
            }
            else {
                $input = array(
                    $key => array_values((array)$input)[0]
                );
            }
        }

        $this->class    = $class;
        $this->id_table = $id_table;
        $this->where    = $where;
        $this->key      = $key;
        $this->values   = $input;
    }

    function class (): string {
        return $this->class;
    }

    function id (): ?int {
        return $this->id_table;
    }

    function where (): ?string {
        return $this->where;
    }

    function key (): string {
        return $this->key;
    }

    function isTranslated (): bool {
        return $this->translated;
    }

    function values (): array {
        return $this->values;
    }

    function value (string $language = NULL): ?string {
        return $this->values[($language ?? ($this->translated ? (($this->class)::TRANSLATOR)::get() : $this->key))] ?? NULL;
    }

    function set (string &$value = NULL, string $language = NULL): void {
        $this->values[($language ?? ($this->translated ? (($this->class)::TRANSLATOR)::get() : $this->key))] = &$value;
    }
}
