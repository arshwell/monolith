<?php

use Arsh\Core\Session;
use Arsh\Core\Folder;
use Arsh\Core\File;
use Arsh\Core\ENV;
use Arsh\Core\URL;
use Arsh\Core\DB;

session_start();

require("ArshWell/Core/ENV.php");

DB::connect('default');
Session::set(ENV::url().ENV::db('conn.default.name'));

$urlpath = ltrim(preg_replace('~^'. ENV::root() .'~', '', URL::path()), '/');
$filepath = ENV::uploads() . $urlpath; // could be outside of project

if (is_file($filepath) && ($matches = File::parsePath($urlpath))
    && call_user_func_array(
        array(Folder::decode($matches['class']), 'fileAccess'),
        array($matches['id_table'], $matches['filekey'], $matches['language'], $matches['size'], $matches['extension'])
    )
) {
    header("Content-Type: ". File::mimeType($filepath));
    echo file_get_contents($filepath);
}
else {
    http_response_code(404);
}
