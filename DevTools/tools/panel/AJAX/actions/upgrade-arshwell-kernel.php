<?php

use Arsh\Core\Table\TableValidation;
use Arsh\Core\Session;
use Arsh\Core\Folder;
use Arsh\Core\Cache;
use Arsh\Core\File;
use Arsh\Core\Func;
use Arsh\Core\ENV;

$form = TableValidation::run(array_merge($_POST, $_FILES),
    array(
        'archive' => array(
            function ($key, $value) {
                $env = ENV::fetch();

                if (!$env->maintenance('active') || $env->maintenance('smart')) {
                    return "Setup an <b>Instant Maintenance</b> before updating project";
                }
            },
            "required|doc",
        ),
        'overwrite-resources' => array(
            'optional|equal:1'
        ),
        'hooks' => array(
            'optional|equal:1'
        )
    ),
    array(
        'required'  => "Upload the archive",
        'doc'       => "No valid file",
        'equal'     => "Invalid checkbox"
    )
);

if ($form->valid()) {
    $zip = new ZipArchive();
    $zipfile = $form->value('archive')['tmp_name'];

    switch ($zip->open($zipfile)) {
		case TRUE: {
            try { // in case are problems in upgrading
                $files = array_merge(
                    File::rFolder('App/Core'),
                    File::rFolder('DevTools'),
                    ['.htaccess', 'download.php', 'web.php']
                ); // to compare the new ones with

                if ($form->value('overwrite-resources')) {
                    $files = array_merge(
                        $files,
                        File::rFolder('resources/images'),
                        File::rFolder('resources/scss/plugins'),
                        File::rFolder('resources/js/plugins')
                    ); // to compare the new ones with
                }

                $overwritten = 0;
                $removed = 0;
                $new = 0;

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $newfile = $zip->getNameIndex($i);

                    if (strpos($newfile, 'App/Core/') === 0 || strpos($newfile, 'DevTools/') === 0
                    || (dirname($newfile) == '.' && in_array($newfile, $files))
                    || ($form->value('overwrite-resources')
                    && (strpos($newfile, 'resources/images/') === 0 || strpos($newfile, 'resources/scss/plugins/') === 0 || strpos($newfile, 'resources/js/plugins/') === 0))) {
                        $dirname = dirname($newfile);

                        if (!is_dir($dirname)) {
                            mkdir($dirname, 0755, true);
                        }

                        if (in_array($newfile, $files)) {
                            unset($files[array_search($newfile, $files)]);
                            $overwritten++;
                        }
                        else {
                            $new++;
                        }

                        copy('zip://'. $zipfile .'#'. $newfile, $newfile);
                    }
                }

                // removing files no present in archive
                foreach ($files as $file) {
                    if (unlink($file)) {
                        $removed++;
                    }
                }

                $info = array(
                    'status' => "ArshWell kernel was upgraded",
                    '1.' => '---',
                    'files' => array(
                        $removed . ' removed',
                        $overwritten . ' overwritten',
                        $new . ' new'
                    )
                );

                if ($form->value('hooks') == NULL) {
                    $info = array_merge(
                        $info,
                        array(
                            '2.'            => '---',
                            'hook.upgrade'  => '<i>disabled</i>'
                        )
                    );
                }
                else {
                    $info = array_merge(
                        $info,
                        array(
                            '2.'            => '---',
                            'hook.upgrade'  => array()
                        )
                    );

                    foreach (File::rFolder('DevTools/hooks/upgrade/') as $file) {
                        try {
                            switch (File::extension($file)) {
                                case 'sql': {
                                    DB::importSqlFile($file);

                                    $info['hook.upgrade'][$file] = "<i>SQL executed</i>";
                                    break;
                                }
                                case 'php': {
                                    $fn = require_once($file);

                                    if (is_object($fn) && $fn instanceof Closure) {
                                        $info['hook.upgrade'][$file] = $fn($form->values());
                                    }
                                    break;
                                }
                            }
                        }
                        catch (Exception $e) {
                            $info['hook.upgrade'][$file] = array(
                                'status'    => get_class($e) . " Error",
                                'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                                'message'   => $e->getMessage()
                            );
                        }

                        // see if subextension is 'always'
                        if (File::extension(File::name($file)) != 'always') {
                            unlink($file);
                        }
                    }
                }

                Folder::removeEmpty('App/Core');
                Folder::removeEmpty('DevTools');
                Folder::removeEmpty('resources/images');
                Folder::removeEmpty('resources/scss/plugins');
                Folder::removeEmpty('resources/js/plugins');

                // NOTE: create new session because new ArshWell version
                // could expect different things.
                session_destroy();
                session_start();

                Session::set(ENV::url().ENV::db('conn.default.name'));
                Cache::delete();

                $info['3.'] = '---';
                $info['PHP'] = Func::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);

                $form->info = $info;
            }
            catch (Exception $e) {
                $form->info = array(
                    'status'    => "Error",
                    'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                    'message'   => $e->getMessage()
                );
            }
            break;
        }
        case ZipArchive::ER_EXISTS: {
            $form->info = array(
                'error' => "Zip already exists."
            );
            break;
        }
        case ZipArchive::ER_INCONS: {
            $form->info = array(
                'error' => "Zip archive inconsistent."
            );
            break;
        }
        case ZipArchive::ER_INVAL: {
            $form->info = array(
                'error' => "Invalid argument."
            );
            break;
        }
        case ZipArchive::ER_MEMORY: {
            $form->info = array(
                'error' => "Malloc failure."
            );
            break;
        }
        case ZipArchive::ER_NOENT: {
            $form->info = array(
                'error' => "No such file."
            );
            break;
        }
        case ZipArchive::ER_NOZIP: {
            $form->info = array(
                'error' => "Not a zip archive."
            );
            break;
        }
        case ZipArchive::ER_OPEN: {
            $form->info = array(
                'error' => "Can't open file."
            );
            break;
        }
        case ZipArchive::ER_READ: {
            $form->info = array(
                'error' => "Read error."
            );
            break;
        }
        case ZipArchive::ER_SEEK: {
            $form->info = array(
                'error' => "Seek error."
            );
            break;
        }
    }
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
