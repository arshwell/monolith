<?php

use ArshWell\Monolith\Table\TableValidation;
use ArshWell\Monolith\Module\Backend;
use ArshWell\Monolith\Table\TableMigration;
use ArshWell\Monolith\ENV\ENVComponent;
use ArshWell\Monolith\Folder;
use ArshWell\Monolith\File;
use ArshWell\Monolith\Time;
use ArshWell\Monolith\ENV;
use ArshWell\Monolith\Web;

$form = TableValidation::run(array_merge($_POST, $_FILES),
    array(
        'time' => array(
            "required|int",
            // "min:1624222800000" // milliseconds: 21/05/2021
        ),
        'attempt' => array(
            "required|int|min:1"
        ),
        'archive' => array(
            function ($key, $value) {
                $env = new ENVComponent();

                if (!($env->class('maintanance'))::isActive() || ($env->class('maintanance'))::isSmart()) {
                    return "Setup an <b>Instant Maintenance</b> before updating project";
                }

                if (!self::error('attempt') && self::value('attempt') == 1) {
                    return array(
                        "required|doc",
                        function ($key, $value) {
                            if (File::extension($value['name']) != 'zip') {
                                return "Only .zip files accepted";
                            }
                        }
                    );
                }
            }
        ),
        'replace' => array(
            "required|int|inArray:0,1"
        )
    ),
    array(
        'required'  => "Upload the archive",
        'doc'       => "No valid file",
        'int'       => 'Invalid radio',
        'inArray'   => 'Invalid radio',
        'min'       => 'Invalid attempt'
    )
);

if ($form->valid()) {
    $archive = $form->value('archive');
    $zipfile = sys_get_temp_dir().'/vendor/arshwell/monolith/updates/sess_'.session_id().'/time_'.$form->value('time').'.zip';

    if ($form->value('attempt') == 1 || !is_file($zipfile)) {
        if (!is_dir(dirname($zipfile))) {
            mkdir(dirname($zipfile), 0755, true);
        }
        copy($archive['tmp_name'], $zipfile);
    }

    $zip = new ZipArchive();

    switch ($zip->open($zipfile)) {
		case TRUE: {
            $filesize = 0;

            try { // in case there are problems in updating
                $index = 0;
                $copied = 0;
                $has_subfolder = false;
                $zipmtime = filemtime($zipfile);

                // check if archive has a subfolder with same name
                // if (rtrim($zip->getNameIndex(0), '/') == File::name($archive['name'])) {
                //     $index = 1;
                //     $has_subfolder = true;
                // }

                $zip_files = array();
                while ($index < $zip->numFiles) {
                    $destination = $zip->getNameIndex($index);

                    if ($form->value('replace') || basename($destination) == '.htaccess'
                    || (strpos($destination, 'caches/') !== 0 && strpos($destination, 'uploads/files/') !== 0)
                    || (strpos($destination, 'uploads/design/') === 0 && preg_match("/^\d+\.css$/", basename($destination)))) {
                        $zip_files[$index] = $zip->getNameIndex($index);
                    }

                    $index++;
                }

                // Order of updating helps a lot,
                // in not existing conflicts during update attempts.
                $zip_folders = array(
                    // NOTE: Exceptions which don't create conflicts
                    'vendor/arshwell/monolith/src/Tygh/.+',
                    'vendor/arshwell/monolith/DevTools/tools/files/',
                    preg_quote(Folder::shorter(__DIR__), '~').'/(?!'.preg_quote(basename(__FILE__), '~').'$)', // vendor/arshwell/monolith/DevTools/panel/AJAX/actions/*

                    '(?!\.htaccess$|env\.json$|web\.php$|vendor/arshwell/monolith/src/.+|vendor/arshwell/monolith/DevTools/.+)', // everything else
                    '.+' // .htaccess, web.php, vendor/arshwell/monolith/src/, vendor/arshwell/monolith/DevTools/ (which create coflicts)
                );

                uasort($zip_files, function ($file_1, $file_2) use ($zip_folders) {
                    $a_key = $b_key = count($zip_folders);

                    foreach ($zip_folders as $i => $folder) {
                        if (preg_match("~^$folder~", $file_1)) {
                            $a_key = $i;
                            break;
                        }
                    }
                    foreach ($zip_folders as $i => $folder) {
                        if (preg_match("~^$folder~", $file_2)) {
                            $b_key = $i;
                            break;
                        }
                    }

                    if ($a_key == $b_key) {
                        return 0;
                    }
                    return ($a_key < $b_key) ? -1 : 1;
                });

                foreach ($zip_files as $index => $source) {
                    $sourcemtime = $zip->statIndex($index)['mtime'];
                    $destination = $source;

                    // if ($has_subfolder) {
                    //     $destination = substr($destination, strpos($destination, '/', 1) + 1);
                    // }

                    if (!is_file($destination) || filemtime($destination) < $sourcemtime
                    || file_get_contents('zip://'. $zipfile .'#'. $source) != file_get_contents($destination)) {

                        $dirname = dirname($destination);

                        if (!is_dir($dirname)) {
                            mkdir($dirname, 0755, true);
                        }

                        copy('zip://'. $zipfile .'#'. $source, $destination);

                        $filesize += filesize($destination);

                        if ($filesize > 52428800 && $copied > 0) { // 50MB
                            $zip->close();
    						http_response_code(500);
    						exit;
    					}

                        $copied++;
                    }
                }

                $removed = 0;
                $overwritten = 0;

                // removing files no present in archive
                // counting overwritten/new files
                foreach (File::rFolder('.') as $file) {
                    if ($form->value('replace') || basename($file) == '.htaccess'
                    || (strpos($file, './caches/') !== 0 && strpos($file, './uploads/') !== 0)) {
                        // if file not in archive
                        if (!in_array(substr($file, 2), $zip_files)) {
                            if (unlink($file)) {
                                $removed++;
                            }
                        }
                        // if newer than zip archive
                        else if (is_file($file) && filemtime($file) > $zipmtime) {
                            $overwritten++;
                        }
                    }
                }

                // setting initial modification time to files
                foreach ($zip_files as $index => $destination) {
                    // if ($has_subfolder) {
                    //     $destination = substr($destination, strpos($destination, '/', 1) + 1);
                    // }

                    if (is_file($destination)) {
                        $sourcemtime = $zip->statIndex($index)['mtime'];

                        touch($destination, $sourcemtime);
                    }
                }

                if (is_file('caches/vendor/arshwell/monolith/env.json')) {
                    touch('caches/vendor/arshwell/monolith/env.json'); // so is up-to-date with env.json
                }
            }
            catch (Exception $e) {
                $info = array(
                    'status'    => "Error catched",
                    'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                    'message'   => $e->getMessage()
                );
            }

            // if no error occurred
            if (empty($info)) {
                try { // because could be thrown SCSS/Web errors
                    Web::fetch(true); // getting routes from the new forks

                    // Sync new modules with DB
                    foreach (File::rFolder('outcomes') as $file) {
                        if (basename($file) == 'back.module.php') {
                            $back = call_user_func(function () use ($file) {
                                return require($file);
                            });

                            if (!empty($back['DB']) && is_array($back['DB']) && class_exists($back['DB']['table'])
                            && !empty($back['fields']) && is_array($back['fields'])) {
                                Backend::buildDB($back['DB'], $back['features'], $back['fields']);
                            }
                        }
                    }

                    $info = array(
                        'status' => "Project was updated",
                        '1.' => '---',
                        'files'     => array(
                            $overwritten . ' overwritten/new',
                            $removed . ' removed'
                        ),
                        '2.' => '---',
                        'migrations' => array()
                    );

                    // Migrations
                    try {
                        $class = ENV::class('migration');

                        if (class_exists($class) && is_subclass_of($class, TableMigration::class)) {
                            $info['migrations'] = ($class)::migrate();
                        }
                    }
                    catch (Error $e) { // we need this because Fatal Errors (like wrong defined classes) aren't catched
                        $info['migrations'] = array(
                            'status'    => get_class($e),
                            'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                            'message'   => $e->getMessage()
                        );
                    }
                    catch (Exception $e) {
                        $info['migrations'] = array(
                            'status'    => get_class($e) . " Error",
                            'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                            'message'   => $e->getMessage()
                        );
                    }

                    $info['3.']     = '---';
                    $info['PHP']    = Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
                    $info['4.']     = '---';
                    $info['NEXT']   = '<a class="text-success" href="javascript:$(\'[href=&quot;#actions-daily&quot;]\').click();$(\'[href=&quot;#actions-daily-recompile&quot;]\').click();">Recompile existing css/js files</a>';
                }
                catch (Exception $e) {
                    $info = array(
                        'status'    => "Error catched <i>(but updating is done)</i>",
                        'from'      => Folder::shorter($e->getFile()) .':'. $e->getLine(),
                        'message'   => $e->getMessage(),
                        '1.'        => '---',
                        'files'     => array(
                            $overwritten . ' overwritten/new',
                            $removed . ' removed'
                        ),
                    );
                }
            }

            $form->info = $info;
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
