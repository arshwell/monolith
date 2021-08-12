<?php

use Arsh\Core\ENV;

/**
 * It prints the sql query and its parameters nicer.
 * This function is used at least by try-catch from Table Class methods.

 * @package App/DevTools
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 */
function _print_sql_query ($sql, $columns = array()): void {
    if (ENV::supervisor() == false) {
        return;
    }

    $keywords = array(
        'SELECT', 'UPDATE', 'INSERT', 'INTO', 'VALUES', 'DELETE', 'FROM', 'ON',
        'WHERE', 'IF', 'ELSE', 'EXISTS', 'AND', 'OR', 'NOT', 'AS', 'LIKE', 'INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN',
        'FULL JOIN', 'SELF JOIN', 'NULL', 'SET', 'GROUP BY', 'ORDER BY CASE WHEN', 'ORDER BY', 'THEN', 'LIMIT',
        'ASC', 'END DESC', 'DESC', 'OFFSET', 'RAND()', 'CREATE TABLE', 'ALTER TABLE', 'MODIFY COLUMN', 'DROP TABLE', 'IFNULL', 'MAX', 'MIN'
    );

    /* Edit SQL query */
        $sql = preg_replace("/([\d])/",                 "<span style=color: green;>$1</span>",  $sql);
        $sql = preg_replace("/((\'\w+\')|(\"\w+\"))/",  "<span style=color: red;>$1</span>",    $sql);
        $sql = preg_replace("/(\`\w+\`)/",              "<span style=color: #05a;>$1</span>",   $sql);

        foreach ($keywords as $key) {
            $sql = preg_replace("/\b($key)\b/i", "<span style=color: #708; text-transform: uppercase;><b>$1</b></span>", $sql);
        }
        $sql = preg_replace("/<span style=(.*?)>/", "<span style='$1'>", $sql);
        $sql = preg_replace("/\bELSE\b/i", "<br>ELSE", $sql);

    /* Edit params */
        if ($columns && count($columns)) {
            $array_keys = array_keys($columns);
            $last_key = end($array_keys);
            $sql .= "<table style='padding: 0px 5px; border-top: 1px solid gray;'>";
                foreach ($columns as $name => $value) {
                    if ($name != $last_key)
                        $sql .= "<tr><td style='border-bottom: 1px dotted gray;'><small>[". $name ."]</small></td><td style='border-bottom: 1px dotted gray;'><small style='color: #708;'>&#xbb;</small></td><td  style='border-bottom: 1px dotted gray; -ms-word-break: break-all; word-break: break-all;  word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; -ms-hyphens: auto; hyphens: auto;'><small> ";
                    else
                        $sql .= "<tr><td><small>[". $name ."]</small></td><td><small style='color: #708;'>&#xbb;</small></td><td style='-ms-word-break: break-all; word-break: break-all;  word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; -ms-hyphens: auto; hyphens: auto;'><small> ";

                    if (is_numeric($value))
                        $sql .= _int($value);
                    else if (is_string($value))
                        $sql .= _string($value);
                    else if (is_bool($value))
                        $sql .= _bool($value);
                    else if (is_array($value))
                        $sql .= _array($value);
                    else
                        $sql .= $value;
                    $sql .= "</small></td></tr>";
                }
            $sql .= "</table>";
        }

    echo _code($sql);
}
