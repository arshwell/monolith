<?php

use Arsh\Core\Folder;

/**
 * Verifies if url requests (from routes.php) exists and are uppercase.

 * @package Arsh/DevTools
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */

$regex = '/[a-z]+[a-z0-9-]*/';

$problems = array();
foreach (Folder::all('pieces') as $folder) {
    if (!preg_match($regex, $folder)) {
        $problems[] = $folder;
    }
}
if ($problems) {
    _html(
        '<i>pieces/*</i><br>' .
        _code(implode('<br>', $problems)) .
        _error("
            All pieces folders should match <code>$regex</code>.<br>
            That's because these are used in <i>css classes</i> and <i>file links</i>."
        )
    );
}
