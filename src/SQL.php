<?php

namespace Arsavinel\Arshwell;

use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\DB;

/**
 * Class for preparing SQL queries.
 *
 * It has routine functions.

 * @package https://github.com/arsavinel/ArshWell
*/
final class SQL {
    private $sql = NULL;

    function __construct (array $sql = array()) {
        $this->sql = $sql;
    }

    function select () {

    }

    function where () {

    }

    function limit () {

    }

    function orderBy () {

    }

    function groupBy () {

    }

    function prepare () {

    }

    static function clean (string $input): string {
     	return preg_replace(array(
        		'@<script[^>]*?>.*?</script>@si',   // Strip out javascript
        		'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
        		'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
        		'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
        	),
            '', $input
        );
    }

    static function sanitize ($input) {
        if (is_array($input)) {
            foreach ($input as $var => $val) {
                $output[$var] = self::sanitize($val);
            }
        }
        else {
            $output = self::clean($input);
        }
        return $output;
    }

    static function nextID (string $table, string $db_key = NULL) {
        if (!$db_key) {
            $db_key = DB::key();
        }
        return "(SELECT `AUTO_INCREMENT` FROM information_schema.TABLES WHERE `TABLE_SCHEMA` = '". ENV::db('conn.'.$db_key.'.name') ."' AND `TABLE_NAME` = '". ENV::db('conn.'.$db_key.'.prefix'). $table ."')";
    }

    /**
     * Multiple Joins field To Joins query
     */
    static function joinsField2joinsQuery (string $table, string $column, array $joins, &$lgs = array()): array {
        $suffix = (($table)::translationTimes($column) ? ':lg' : '');

        $query = array(
            'class' => $table,
            'columns' => array(
                ($table)::TABLE .'.'. ($table)::PRIMARY_KEY,
                ($table)::TABLE .'.'. $column . $suffix .' AS '. ($table)::TABLE .'_'. $column
            ),
            'joins' => array(),
            'order' => array(
                ($table)::TABLE .'.'. ($table)::PRIMARY_KEY . ' DESC'
            )
        );

        foreach ($joins as $join) {
            if (($join['table'])::translationTimes($join['column']) > 0) {
                $suffix = ':lg';

                $lgs[($join['table'])::TABLE .'.'. $join['column']] = (($join['table'])::TRANSLATOR)::default();
            }

            $query['columns'][] = ($join['table'])::TABLE .'.'. ($join['table'])::PRIMARY_KEY;
            $query['columns'][] = ($join['table'])::TABLE .'.'. $join['column'] . $suffix .' AS '. ($join['table'])::TABLE .'_'. $join['column'];

            $query['order'][] = ($join['table'])::TABLE .'.'. ($join['order'] ?? ($join['table'])::PRIMARY_KEY . ' DESC');

            $query['joins'][] = array(
                'type'  => "INNER",
                'table' => ($join['table'])::TABLE,
                'on'    => ($table)::TABLE .'.'.($join['table'])::PRIMARY_KEY .' = '. ($join['table'])::TABLE .'.'.($join['table'])::PRIMARY_KEY
            );

            $table = $join['table'];
        }

        $query['columns'] = implode(', ', $query['columns']);
        $query['order'] = implode(', ', array_reverse($query['order']));

        return $query;
    }

    /**
     * Multiple Tree Joins field To Joins query (recursive method)
     */
    static function joinField2joinQuery (array $array, string $table = NULL): array {
        if (empty($array['table'])) {
            $array['table'] = $table;
        }

        $suffix = (($array['table'])::translationTimes($array['column']) ? ':lg' : '');

        $query = array(
            'columns' => array(
                ($array['table'])::TABLE .'.'. ($array['table'])::PRIMARY_KEY,
                ($array['table'])::TABLE .'.'. $array['column'] . $suffix .' AS '. ($array['table'])::TABLE .'_'. $array['column']
            ),
            'order' => array(
                ($array['table'])::TABLE .'.'. ($array['table'])::PRIMARY_KEY . ' DESC'
            )
        );

        if (empty($array['join'])) {
            return $query;
        }
        else {
            $query['joins'] = array(
                array(
                    'type'  => "INNER",
                    'table' => ($array['join']['table'])::TABLE,
                    'on'    => ($array['table'])::TABLE .'.'.($array['join']['table'])::PRIMARY_KEY .' = '. ($array['join']['table'])::TABLE .'.'.($array['join']['table'])::PRIMARY_KEY
                )
            );
        }

        $query = array_merge_recursive(
            $query,
            self::joinField2joinQuery($array['join'])
        );

        $query['class'] = $array['table'];

        return $query;
    }
}
