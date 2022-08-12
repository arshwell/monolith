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
}
