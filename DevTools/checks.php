<?php

use Arshwell\Monolith\File;

/**
 * We are including all helpful functions for development mode.
 * Every function runs only if the IP belongs to a developer.

 * @package https://github.com/arshwell/monolith
 */
call_user_func(function () {
    require("vendor/arshwell/monolith/DevTools/checks/php-settings.php");

    $filemtime = filemtime("vendor/arshwell/monolith/DevTools/checks/web.routes.php");
    foreach (File::rFolder('config/forks') as $file) {
        if (filemtime($file) > $filemtime) {
            require("vendor/arshwell/monolith/DevTools/checks/web.routes.php");
            touch("vendor/arshwell/monolith/DevTools/checks/web.routes.php");
            break;
        }
    }

    if (filemtime('config/services.json') > filemtime("vendor/arshwell/monolith/DevTools/checks/env.languages.php")) {
        require("vendor/arshwell/monolith/DevTools/checks/env.languages.php");
        touch("vendor/arshwell/monolith/DevTools/checks/env.languages.php");
    }

    $filemtime = filemtime("vendor/arshwell/monolith/DevTools/checks/pieces-folders.php");
    foreach (File::rFolder('pieces') as $f) {
        if (filemtime($f) > $filemtime) {
            require("vendor/arshwell/monolith/DevTools/checks/pieces-folders.php");
            touch("vendor/arshwell/monolith/DevTools/checks/pieces-folders.php");
            break;
        }
    }
});
