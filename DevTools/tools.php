<?php

use Arshwell\Monolith\DevTool\DevToolData;

/**
 * Preparation for development mode actions.
 * These are used for helping developer in his process.

 *******************************
 * rshwll   -> arshwell
 * pnl      -> panel
 * hdr      -> header
 * rsrc     -> resource
 * dlt      -> delete
 ***** just removed vowels *****

 * @package https://github.com/arshwell/monolith
 */

if (!empty($_REQUEST['rshwll']) && $_REQUEST['rshwll'] == substr(md5(DevToolData::ArshwellVersion()), 0, 5)) {
    call_user_func(function () {
        // DevTool panel action
        if (!empty($_REQUEST['pnl']) && is_file("vendor/arshwell/monolith/DevTools/tools/panel/". $_REQUEST['pnl'] .".php")) {
            http_response_code(200);
            require("vendor/arshwell/monolith/DevTools/tools/panel/". $_REQUEST['pnl'] .".php");
            exit;
        }

        // resource file
        if (!empty($_GET['hdr']) && !empty($_GET['rsrc']) && is_file("vendor/arshwell/monolith/resources/". $_GET['rsrc'])) {
            ini_set('memory_limit', '-1');
            http_response_code(200);
            header("Content-Type: ". $_GET['hdr']);
            echo file_get_contents("vendor/arshwell/monolith/resources/". $_GET['rsrc']);
            if (!empty($_GET['dlt']) && $_GET['dlt'] == '1') {
                unlink("vendor/arshwell/monolith/resources/". $_GET['rsrc']);
            }
            exit;
        }
    });
}
