<?php

use Arshwell\Monolith\Env\EnvConfig;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\URL;
use Arshwell\Monolith\DB;

session_start();

require("vendor/autoload.php");

StaticHandler::setEnvConfig(new EnvConfig([
    'databases' => json_decode(file_get_contents("config/databases.json"), true, 512, JSON_THROW_ON_ERROR),
    'development' => json_decode(file_get_contents("config/development.json"), true, 512, JSON_THROW_ON_ERROR),
    'locations' => json_decode(file_get_contents("config/locations.json"), true, 512, JSON_THROW_ON_ERROR),
    'services' => json_decode(file_get_contents("config/services.json"), true, 512, JSON_THROW_ON_ERROR),
    'web' => json_decode(file_get_contents("config/web.json"), true, 512, JSON_THROW_ON_ERROR),
]));

StaticHandler::iniSetPHP();

DB::connect('default');
Session::set(StaticHandler::getEnvConfig('web.URL').StaticHandler::getEnvConfig('databases.conn.default.name'));

$urlpath = ltrim(preg_replace('~^'. StaticHandler::getEnvConfig()->getSiteRoot() .'~', '', URL::path()), '/');
$filepath = StaticHandler::getEnvConfig()->getLocationPath('uploads', false) . $urlpath; // could be outside of project

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
