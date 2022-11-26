<?php

use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\URL;
use Arsavinel\Arshwell\DB;

session_start();

require("vendor/autoload.php");

require("vendor/arsavinel/arshwell/src/ENV.php");

DB::connect('default');
Session::set(ENV::url().ENV::db('conn.default.name'));

$urlpath = ltrim(preg_replace('~^'. ENV::root() .'~', '', URL::path()), '/');
$filepath = ENV::path('uploads', false) . $urlpath; // could be outside of project

// _vd($urlpath, '$urlpath');
// _vd($filepath, '$filepath');
// exit;

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
