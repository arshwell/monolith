<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Table\TableView;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Time;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\DB;

$form = TableValidation::run($_POST,
    array(
        'remove-lg' => array(
            "optional|int|equal:1"
        )
    ),
    array(
        'int'   => 'Invalid checkbox',
        'equal' => 'Invalid checkbox'
    )
);

if ($form->valid()) {
    $missing = array();
    $removed = array(
        0 => 0, // count
        1 => array() // dirs
    ); // we dont use keys for nice display in DevPanel

    foreach (File::rFolder(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/') as $file) {
        if (is_file($file) && ($matches = File::parsePath($file))) {
            $class = Folder::decode($matches['class']);

            if (class_exists($class) && !DB::existsTable(($class)::TABLE)) {
                $missing[] = ($class)::TABLE;
            }
            // class doesn't exist || table row doesn't exist || class don't have files
            else if (!class_exists($class) || !($class)::get($matches['id_table']) || !defined("{$class}::FILES")) {
                $path = $matches['class'] .'/'. $matches['id_table'];
                $count = count(File::rFolder(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path));

                if (Folder::remove(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path)) {
                    $removed[0] += $count;
                    $removed[1][] = $path . ' <small class="text-muted text-monospace">('.$count.')</small>';
                }
            }
            // class files don't have this certain filekey
            else if (!isset(($class)::FILES[$matches['filekey']])) {
                $path = $matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey'];
                $count = count(File::rFolder(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path));

                if (Folder::remove(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path)) {
                    $removed[0] += $count;
                    $removed[1][] = $path . ' <small class="text-muted text-monospace">('.$count.')</small>';
                }
            }
            // remove lg - if not used by class
            else if ($form->value('remove-lg') && !in_array($matches['language'], (($class)::TRANSLATOR)::LANGUAGES)) {
                $path = $matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey'] .'/'. $matches['language'];
                $count = count(File::rFolder(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path));

                if (Folder::remove(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path)) {
                    $removed[0] += $count;
                    $removed[1][] = $path . ' <small class="text-muted text-monospace">('.$count.')</small>';
                }
            }
            else if (!empty(($class)::FILES[$matches['filekey']]['sizes']) // avoiding removing TableView files
            && !isset(($class)::FILES[$matches['filekey']]['sizes'][$matches['size']])) {
                $path = $matches['class'] .'/'. $matches['id_table'] .'/'. $matches['filekey'] .'/'. $matches['language'] .'/'. $matches['size'];
                $count = count(File::rFolder(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path));

                if (Folder::remove(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $path)) {
                    $removed[0] += $count;
                    $removed[1][] = $path . ' <small class="text-muted text-monospace">('.$count.')</small>';
                }
            }

            /**
             * Deleting empty folders if not View folders.
             *
             * We need those folders for TableView files, so TableFiles classes can know the required filesizes.
             */
            if (!is_subclass_of($class, TableView::class)) {
                Folder::removeEmpty(StaticHandler::getEnvConfig()->getLocationPath('uploads') . 'files/' . $matches['class']);
            }
        }
    }

    if ($removed[0]) {
        $removed[0] = ("<b>count:</b> ". $removed[0]); // for nice display in DevPanel
    }

    $form->info = array(
        'missing tables'    => $missing ?: NULL,
        'removed'           => ($removed[0] ? $removed : 'none'),
        'PHP'               => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
