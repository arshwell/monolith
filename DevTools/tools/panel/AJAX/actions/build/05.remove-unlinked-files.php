<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Table\TableView;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\DB;

$form = TableValidation::run($_POST, array(
    'prev' => array(
        "array" // TODO: make JS FormData getting arrays recursive
    )
), false);

if ($form->valid()) {
    $build_dir  = sys_get_temp_dir().'/vendor/arshwell/monolith/builds/sess_'.session_id().'/';
    $asset      = 'uploads/files/';

    if (!is_dir($build_dir . StaticHandler::getEnvConfig()->getFileStoragePathByIndex(0, 'uploads') . 'files/')) {
        $form->info = array("TABLE FILES have not been added.");
    }
    else {
        $removed = 0;

        foreach (File::rFolder($build_dir.$asset) as $file) {
            if (is_file($file) && ($matches = File::parsePath(substr($file, strlen($build_dir))))) {
                $class = Folder::decode($matches['class']);

                if (!class_exists($class) || !DB::existsTable(($class)::TABLE)
                || !($class)::get($matches['id_table']) || !defined("{$class}::FILES")) {
                    // if dirname was removed, we add up removed files
                    $removed += Folder::remove($build_dir.$asset.$matches['class'] .'/'. $matches['id_table']);
                }
                else if (!isset(($class)::FILES[$matches['filekey']])) {
                    // if dirname was removed, we add up removed files
                    $removed += Folder::remove($build_dir.$asset.$matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey']);
                }
                else if (!in_array($matches['language'], (($class)::TRANSLATOR)::LANGUAGES)) {
                    // if dirname was removed, we add up removed files
                    $removed += Folder::remove($build_dir.$asset.$matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey'] .'/'. $matches['language']);
                }
                else if (!empty(($class)::FILES[$matches['filekey']]['sizes']) // avoiding removing ViewTable files
                && !isset(($class)::FILES[$matches['filekey']]['sizes'][$matches['size']])) {
                    // if dirname was removed, we add up removed files
                    $removed += Folder::remove($build_dir.$asset.$matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey'] .'/'. $matches['language'] .'/'. $matches['size']);
                }

                /**
                 * Deleting empty folders if not View folders.
                 *
                 * We need those folders for TableView files, so TableFiles classes can know the required filesizes.
                 */
                if (!is_subclass_of($class, TableView::class)) {
                    Folder::removeEmpty($build_dir.$asset.$matches['class']);
                }
            }
        }

        $form->info = array("Have been deleted ".$removed." files from ".$asset);
    }
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
