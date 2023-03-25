<?php

namespace Arshwell\Monolith\DevTool;

use Arshwell\Monolith\Folder;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\Text;
use Arshwell\Monolith\DB;
use Exception;

/**
 * Static class for debuging website in development phase.

 * @package https://github.com/arshwell/monolith
 */
final class DevToolDebug {

    /**
     * See how many times every closure was the fastest.

     * @return ?array
     */
    static function compare_functions (array $funcs, int $counter = 1000): ?array {
        if (StaticHandler::supervisor() == false) {
            return NULL;
        }

        $score = array();
        $speed = array();
        foreach ($funcs as $name => $func) {
            $score[$name] = 0;
            $speed[$name] = 0;
        }

        for ($i = 0; $i < $counter; $i++) {
            foreach ($speed as &$val) {
                $val = 0;
                unset($val);
            }

            foreach ($funcs as $name => $func) {
                $time = microtime(true);

                    $func();

                $speed[$name] = microtime(true) - $time;
            }

            foreach (array_keys($speed, min($speed)) as $key) {
                $score[$key]++;
            }
        }

        return $score;
    }

    /**
     * Calc execution time for action inside the closure.

     * @return ?int
     */
    static function execution_time (\closure $function): ?int {
        if (StaticHandler::supervisor() == false) {
            return NULL;
        }

        $time = microtime(true);

            $function();

        return (microtime(true) - $time);
    }

    /**
     * It throws proper exception for last json error.
     */
    static function throw_json_last_error (): void {
        if (StaticHandler::supervisor() == false) {
            return;
        }

        switch (json_last_error()) {
            case JSON_ERROR_NONE: {
                throw new Exception('JSON_LAST_ERROR: No errors');
                break;
            }
            case JSON_ERROR_DEPTH: {
                throw new Exception('JSON_LAST_ERROR: Maximum stack depth exceeded');
                break;
            }
            case JSON_ERROR_STATE_MISMATCH: {
                throw new Exception('JSON_LAST_ERROR: Underflow or the modes mismatch');
                break;
            }
            case JSON_ERROR_CTRL_CHAR: {
                throw new Exception('JSON_LAST_ERROR: Unexpected control character found');
                break;
            }
            case JSON_ERROR_SYNTAX: {
                throw new Exception('JSON_LAST_ERROR: Syntax error, malformed JSON');
                break;
            }
            case JSON_ERROR_UTF8: {
                throw new Exception('JSON_LAST_ERROR: Malformed UTF-8 characters, possibly incorrectly encoded');
                break;
            }
            default: {
                throw new Exception('JSON_LAST_ERROR: Unknown error');
                break;
            }
        }
    }

    /**
     * It prints the sql query and its parameters nicer.
     *
     * This function is used at least by try-catch from Arshwell\Monolith\Table class methods.
     */
    static function print_pdo_exception (object $exception, string $sql_query, array $params = NULL): void {
        DB::rollBack();

        if (StaticHandler::supervisor()) { ?>
            <table cellpadding="15" style="width: 100%;">
                <tr style="background-color: rgb(230,230,230);">
                    <th>File</th>
                    <td><small><?= Folder::shorter($exception->getFile()) ?> (<?= $exception->getLine() ?>)</small></td>
                </tr>
                <tr style="background-color: rgb(240,240,240);">
                    <th>SQL</th><td><?php  DevToolDebug::print_sql_query($sql_query, $params) ?></td>
                </tr>
                <tr style="background-color: rgb(230,230,230);">
                    <th>Message</th><td><?= $exception->getMessage() ?></td>
                </tr>
                <tr style="background-color: rgb(240,240,240);">
                    <th>Type</th>
                    <td><small><?= get_class($exception) ?></small></td>
                </tr>

                <?php
                if (function_exists('debug_backtrace')) {
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 7);

                    if ($backtrace) { ?>
                        <tr style="background-color:rgb(230,230,230);">
                            <th style="vertical-align: baseline;">Backtrace</th>
                            <td style="padding: 0;">
                                <table cellpadding="5" style="width: 100%;">
                                    <?php
                                    array_shift($backtrace);
                                    foreach ($backtrace as $i => $bt) {
                                        $color = (230 + (($i % 2) * 10)); ?>
                                        <tr style="background-color: rgb(<?= $color ?>,<?= $color ?>,<?= $color ?>);">
                                            <th><?= ($i+1) ?>.</th>
                                            <td>
                                                <table cellpadding="5" style="text-align: left; font-size: smaller;">
                                                    <?php
                                                    if (isset($bt['file'])) { ?>
                                                        <tr>
                                                            <th>File:</th>
                                                            <td><?= Folder::shorter($bt['file']) ?> <b>(<?= $bt['line'] ?>)</b></td>
                                                        </tr>
                                                    <?php }
                                                    if (isset($bt['class'])) { ?>
                                                        <tr>
                                                            <th><?= ($bt['type'] == '->' ? 'Object' : 'Class') ?>:</th>
                                                            <td><?= $bt['class'] ?></td>
                                                        </tr>
                                                    <?php }
                                                    if (isset($bt['function'])) { ?>
                                                        <tr>
                                                            <th><?= ((isset($bt['type']) && $bt['type'] == '->') ? 'Method' : 'Function') ?>:</th>
                                                            <td><?= $bt['function'] ?></td>
                                                        </tr>
                                                    <?php }
                                                    if (isset($bt['args'])) { ?>
                                                        <tr>
                                                            <th>Args:</th>
                                                            <td>(<?= implode(', ', array_map(function ($arg) {
                                                                switch (gettype($arg)) {
                                                                    case 'string': {
                                                                        return DevToolHTML::string(Text::chars($arg, 10));
                                                                    }
                                                                    case 'boolean': {
                                                                        return DevToolHTML::bool($arg);
                                                                    }
                                                                    case 'integer':
                                                                    case 'float':
                                                                    case 'double': {
                                                                        return DevToolHTML::int($arg);
                                                                    }
                                                                    case 'object': {
                                                                        return get_class($arg);
                                                                    }
                                                                    case 'NULL': {
                                                                        return 'NULL';
                                                                    }
                                                                    default: {
                                                                        return gettype($arg);
                                                                    }
                                                                }
                                                            }, $bt['args'])) ?>)</td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </table>
        <?php }
        exit;
    }

    /**
     * It prints the sql query and its parameters nicer.
     *
     * This function is used at least by try-catch from Arshwell\Monolith\Table class methods.
     */
    static function print_sql_query ($sql, $columns = array()): void {
        if (StaticHandler::supervisor() == false) {
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
                            $sql .= DevToolHTML::int($value);
                        else if (is_string($value))
                            $sql .= DevToolHTML::string($value);
                        else if (is_bool($value))
                            $sql .= DevToolHTML::bool($value);
                        else if (is_array($value))
                            $sql .= DevToolHTML::array($value);
                        else
                            $sql .= $value;
                        $sql .= "</small></td></tr>";
                    }
                $sql .= "</table>";
            }

        echo DevToolHTML::code($sql);
    }
}
