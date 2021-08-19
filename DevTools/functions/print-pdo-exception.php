<?php

use Arsh\Core\Folder;
use Arsh\Core\Text;
use Arsh\Core\ENV;
use Arsh\Core\DB;

/**
 * It prints the sql query and its parameters nicer.
 * This function is used at least by try-catch from Table Class methods.

 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
function _print_pdo_exception (object $exception, string $sql_query, array $params = NULL): void {
    DB::rollBack();

    if (ENV::supervisor()) { ?>
        <table cellpadding="15" style="width: 100%;">
            <tr style="background-color: rgb(230,230,230);">
                <th>File</th>
                <td><small><?= Folder::shorter($exception->getFile()) ?> (<?= $exception->getLine() ?>)</small></td>
            </tr>
            <tr style="background-color: rgb(240,240,240);">
                <th>SQL</th><td><?php _print_sql_query($sql_query, $params) ?></td>
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
                $backtrace = debug_backtrace(NULL, 7);

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
                                                                    return _string(Text::chars($arg, 10));
                                                                }
                                                                case 'boolean': {
                                                                    return _bool($arg);
                                                                }
                                                                case 'integer':
                                                                case 'float':
                                                                case 'double': {
                                                                    return _int($arg);
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
