<?php

use Symfony\Component\Dotenv\Dotenv;

use Arshwell\Monolith\Env\EnvConfig;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\URL;
use Arshwell\Monolith\DB;

session_start();

require("vendor/autoload.php");

// loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
(new Dotenv())->loadEnv('.env');

StaticHandler::setEnvConfig(new EnvConfig([
    'databases' => json_decode(file_get_contents("config/databases.json"), true, 512, JSON_THROW_ON_ERROR),
    'development' => json_decode(file_get_contents("config/development.json"), true, 512, JSON_THROW_ON_ERROR),
    'filestorages' => json_decode(file_get_contents("config/filestorages.json"), true, 512, JSON_THROW_ON_ERROR),
    'services' => json_decode(file_get_contents("config/services.json"), true, 512, JSON_THROW_ON_ERROR),
    'web' => json_decode(file_get_contents("config/web.json"), true, 512, JSON_THROW_ON_ERROR),
], $_ENV));

StaticHandler::iniSetPHP();

// connect to all databases
foreach (array_keys(StaticHandler::getEnvConfig('databases')['conn']) as $connKey) {
    DB::connect($connKey);
}
Session::set(StaticHandler::getEnvConfig('web.URL').StaticHandler::getEnvConfig()->getDbConnByIndex()['name']);

$urlpath = ltrim(preg_replace('~^'. StaticHandler::getEnvConfig()->getSiteRoot() .'~', '', URL::path()), '/');

foreach (StaticHandler::getEnvConfig('filestorages') as $filesystemKey => $filesystem) {
    $filepath = StaticHandler::getEnvConfig()->getFileStoragePath($filesystemKey, 'uploads', false) . $urlpath; // could be outside of project

    if (is_file($filepath) && ($matches = File::parsePath($urlpath))) {
        if (!empty($filesystem['aliases'])) {
            foreach ($filesystem['aliases'] as $alias => $myClass) {
                if (Folder::encode($alias) == $matches['class']) {
                    $matches['class'] = Folder::encode($myClass);
                }
            }
        }

        if (call_user_func_array(
                array(Folder::decode($matches['class']), 'fileAccess'),
                array($matches['id_table'], $matches['filekey'], $matches['language'], $matches['size'], $matches['extension'])
            )
        ) {
            header("Content-Type: ". File::mimeType($filepath));
            echo file_get_contents($filepath);
        }
    }
}

http_response_code(404);
