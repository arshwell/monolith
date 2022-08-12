<?php

use Arsavinel\Arshwell\File;

/**
 * We are including all helpful functions for development mode.
 * Every function runs only if the IP belongs to a developer.

 * @package https://github.com/arsavinel/ArshWell
 */
call_user_func(function () {
    require("vendor/arsavinel/arshwell/DevTools/checks/php-settings.php");

    $filemtime = filemtime("vendor/arsavinel/arshwell/DevTools/checks/web.routes.php");
    foreach (File::rFolder('forks') as $file) {
        if (filemtime($file) > $filemtime) {
            require("vendor/arsavinel/arshwell/DevTools/checks/web.routes.php");
            touch("vendor/arsavinel/arshwell/DevTools/checks/web.routes.php");
            break;
        }
    }

    if (filemtime('env.json') > filemtime("vendor/arsavinel/arshwell/DevTools/checks/env.languages.php")) {
        require("vendor/arsavinel/arshwell/DevTools/checks/env.languages.php");
        touch("vendor/arsavinel/arshwell/DevTools/checks/env.languages.php");
    }

    $filemtime = filemtime("vendor/arsavinel/arshwell/DevTools/checks/pieces-folders.php");
    foreach (File::rFolder('pieces') as $f) {
        if (filemtime($f) > $filemtime) {
            require("vendor/arsavinel/arshwell/DevTools/checks/pieces-folders.php");
            touch("vendor/arsavinel/arshwell/DevTools/checks/pieces-folders.php");
            break;
        }
    }
});
