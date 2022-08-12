<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\URL;

$form = TableValidation::run($_POST, array(), false);

if ($form->valid()) {
	$build_dir = sys_get_temp_dir().'/vendor/arsavinel/arshwell/builds/sess_'.session_id();
	$build_zip = sys_get_temp_dir().'/vendor/arsavinel/arshwell/builds/sess_'.session_id().'.zip';

	$zip = new ZipArchive();

	switch ($zip->open($build_zip, ZipArchive::CREATE)) {
		case TRUE: {
			$filesize = 0;

		    foreach (File::rFolder($build_dir) as $i => $file) {
				$entryname = substr($file, strlen($build_dir) + 1); // local name inside the ZIP archive

				if (strpos($entryname, 'vendor/arsavinel/arshwell/DevTools/backups/') !== 0 && $zip->locateName($entryname) === false) {
					$filesize += filesize($file);

					if ($filesize > 52428800 && $i > 0) { // 52428800 = 50MB
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

			$env = ENV::fetch($build_dir);

			$zipsize = filesize($build_zip);

			$form->redirect = array(
				'href'      => URL::get(true, false).'?rshwll='. $_POST['rshwll'] .'&hdr=application/zip&fl='. str_replace('//', '/', $path . $build_zip),
				'download'  => trim($env->root() ?: $env->site(), '/').date(' d.m.Y H-i') .'.zip',
				'waiting'   => $zipsize / 1000 // waiting time approx
			);
			$form->info = array(
	            'status'    => "Build was archived.",
	            'archive'   => "Has been sent to browser.",
	            'filesize'  => File::readableSize($zipsize)
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

	Folder::remove($build_dir);
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
