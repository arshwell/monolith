<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Time;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\URL;

$form = TableValidation::run($_POST,
    array(
        'delete' => array(
            'optional|equal:1'
        ),
        'time' => array(
            "required|int",
            // "min:1624222800000" // milliseconds: 21/05/2021
        ),
    ),
    array(
        'required'  => "Required",
        'equal'     => 'Invalid checkbox',
        'int'       => 'Not int',
        'min'       => 'Min broken'
    )
);

if ($form->valid()) {
    $zippath = sys_get_temp_dir().'/vendor/arsavinel/arshwell/projects/sess_'.session_id().'/time_'.$form->value('time').'.zip';

    if (!is_dir(dirname($zippath))) {
        mkdir(dirname($zippath), 0755, true);
    }

    $zip = new ZipArchive();

    switch ($zip->open($zippath, ZipArchive::CREATE)) {
		case TRUE: {
			$filesize = 0;

            foreach (File::rFolder('.') as $i => $file) {
                $entryname = substr($file, 2); // local name inside the ZIP archive

				if (strpos($entryname, 'DevTools/backups/') !== 0 && $zip->locateName($entryname) === false) {
					$filesize += filesize($file);

					if ($filesize > 52428800 && $i > 0) { // 50MB
                        $zip->close();
						http_response_code(500);
						exit;
					}

                    $zip->addFile(realpath($file), $entryname);
			    }
            }

            $zip->close();

			$path = '../../../../'; // getting out from vendor/arsavinel/arshwell/DevTools/tools/files
            $getcwd = getcwd();

            do {
                $path .= '../';
                $getcwd = dirname($getcwd);
            } while ($getcwd != '/'); // creating path to tmp folder

            $zipsize = filesize($zippath);

            $form->redirect = array(
                'href'      => URL::get(true, false).'?'.http_build_query(array(
                    'rshwll'    => $_POST['rshwll'],
                    'hdr'       => 'application/zip',
                    'fl'        => str_replace('//', '/', $path . $zippath),
                    'dlt'       => 1 // delete file after download
                )),
                'download'  => trim(ENV::root() ?: ENV::site(), '/').date(' d.m.Y H-i') .'.zip',
                'waiting'   => $zipsize / 1000 // waiting time approx
            );
            $form->info = array(
                'status'    => "Project was copied in the archive.",
                'archive'   => "Has been sent to browser.",
                'filesize'  => File::readableSize($zipsize),
                'PHP'       => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
            );
            break;
        }
        case ZipArchive::ER_EXISTS: {
            $form->info = array(
                'error' => "File already exists."
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
