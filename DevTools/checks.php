<?php

use Arsh\Core\File;

/**
 * We are including all helpfull functions for development mode.
 * Every function runs only if the IP belongs to a developer.

 * @package App/DevTools
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 */
call_user_func(function () {
    require("ArshWell/DevTools/checks/php-settings.php");

    $filemtime = filemtime("ArshWell/DevTools/checks/web.routes.php");
    foreach (File::rFolder('forks') as $file) {
        if (filemtime($file) > $filemtime) {
            require("ArshWell/DevTools/checks/web.routes.php");
            touch("ArshWell/DevTools/checks/web.routes.php");
            break;
        }
    }

    if (filemtime('env.json') > filemtime("ArshWell/DevTools/checks/env.languages.php")) {
        require("ArshWell/DevTools/checks/env.languages.php");
        touch("ArshWell/DevTools/checks/env.languages.php");
    }

    $filemtime = filemtime("ArshWell/DevTools/checks/pieces-folders.php");
    foreach (File::rFolder('pieces') as $f) {
        if (filemtime($f) > $filemtime) {
            require("ArshWell/DevTools/checks/pieces-folders.php");
            touch("ArshWell/DevTools/checks/pieces-folders.php");
            break;
        }
    }
});
