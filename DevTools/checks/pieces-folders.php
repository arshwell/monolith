<?php

use ArshWell\Monolith\DevTool\DevToolHTML;
use ArshWell\Monolith\Folder;

/**
 * Verifies if all pieces have correct name pattern.

 * @package https://github.com/arshwell/monolith
 */

$regex = '/[a-z]+[a-z0-9-]*/';

$problems = array();
foreach (Folder::all('pieces') as $folder) {
    if (!preg_match($regex, $folder)) {
        $problems[] = $folder;
    }
}

if ($problems) {
    DevToolHTML::html(
        '<i>pieces/*</i><br>' .
        DevToolHTML::code(implode('<br>', $problems)) .
        DevToolHTML::error(
            "All pieces folders should match <code>$regex</code>.<br>
            That's because these are used in <i>css classes</i> and <i>file links</i>."
        )
    );
}
