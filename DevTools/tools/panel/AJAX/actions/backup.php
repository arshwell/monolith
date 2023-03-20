<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Time;
use Arshwell\Monolith\ENV;

$form = TableValidation::run($_POST,
    array(
        'date' => array(
            'required',
            function ($key, $value) {
                if (!preg_match("/\d{2}\.\d{2}\s\d{2}-\d{2}/", $value)) {
                    return "invalid format";
                }
            }
        )
    ),
    array(
        'required' => "date missing"
    )
);

if ($form->valid()) {
    if (is_dir('DevTools/backups') && Folder::mTime('DevTools/backups') > Folder::mTime('.', array('App/Core'))) {
        $form->info = array(
            'status' => 'Up-to-date backup already exists'
        );
    }
    else {
        $zippath = 'DevTools/backups/'.trim(ENV::root() ?: ENV::site(), '/').$form->value('date').'.zip';
    	if (!is_dir(dirname($zippath))) {
    		mkdir(dirname($zippath), 0755, true);
    	}

    	$zip = new ZipArchive();

        switch ($zip->open($zippath, ZipArchive::CREATE)) {
    		case TRUE: {
                $filesize = 0;

        	    foreach (File::rFolder('.') as $i => $file) {
                    $entryname = substr($file, 2); // local name inside the ZIP archive

                    if ($zip->locateName($entryname) === false) {
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

        		$form->info = array(
                    'status'    => "Entire project was archived in ". dirname($zippath),
                    'filesize'  => filesize($zippath),
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
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
