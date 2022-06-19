<?php

use Arsh\Core\Table\TableValidation;
use Arsh\Core\Folder;
use Arsh\Core\File;
use Arsh\Core\ENV;
use Arsh\Core\DB;

$form = TableValidation::run($_POST, array(
    'prev' => array(
        "array" // TODO: make JS FormData getting arrays recursive
    )
), false);

if ($form->valid()) {
    $build_dir  = sys_get_temp_dir().'/ArshWell/builds/sess_'.session_id().'/';
    $asset      = ENV::uploads(true);

    if (!is_dir($build_dir.$asset.'.app/')) {
        $form->info = array("TABLE FILES have not been added.");
    }
    else {
        $removed    = 0;

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
            }
        }

        Folder::removeEmpty($build_dir.$asset);

        $form->info = array("Have been deleted ".$removed." files from ".$asset);
    }
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
