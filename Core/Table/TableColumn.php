<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Table\TableSegment;
use Arsavinel\Arshwell\DB;

final class TableColumn implements TableSegment {
    private $class  = NULL;
    private $where  = NULL;
    private $key    = NULL;

    private $translated = false;
    private $languages  = array();
    private $values     = array();

    /**
     * (string|array) $input
    */
    function __construct (string $class, string $where = NULL, string $key, $input = NULL) {
        if (defined("{$class}::TRANSLATOR") && defined("{$class}::TRANSLATED")
        && in_array($key, ($class)::TRANSLATED)) {
            $this->translated = true;
            $this->languages = (($class)::TRANSLATOR)::LANGUAGES;
        }

        if ($where && $input == NULL) {
            if ($this->translated == true) {
                $input = array();

                $columns = DB::select(array(
                    'class'     => $class,
                    'columns'   => ($class)::PRIMARY_KEY .','. implode(', ', array_map(function ($lg) use ($key) {
                        return $key.'_'.$lg.' AS '.$lg;
                    }, $this->languages)),
                    'where'     => $where
                ));

                if ($columns) {
                    foreach ($this->languages as $lg) {
                        $input[$lg] = array_column($columns, $lg, ($class)::PRIMARY_KEY);
                    }
                }
            }
            else {
                $input = array_column(DB::select(array(
                    'class'     => $class,
                    'columns'   => ($class)::PRIMARY_KEY .','. $key,
                    'where'     => $where
                )), $key, ($class)::PRIMARY_KEY);
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
        $this->where    = $where;
        $this->key      = $key;
        $this->values   = $input;
    }

    function class (): string {
        return $this->class;
    }

    function id (): ?int {
        return NULL;
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

    function value (string $language = NULL): array {
        return $this->values[($language ?? ($this->translated ? (($this->class)::TRANSLATOR)::get() : $this->key))];
    }

    function set (string &$value = NULL, string $language = NULL): void {
        $this->values[($language ?? ($this->translated ? (($this->class)::TRANSLATOR)::get() : $this->key))] = &$value;
    }
}
