<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\StaticHandler;

$form = TableValidation::run($_POST,
    array(
        'css-js-files' => array(
            "optional|equal:1"
        ),
        'table-files' => array(
            "optional|equal:1"
        )
    ),
    array(
        'equal' => 'Invalid checkbox'
    )
);

if ($form->valid()) {
    $build_dir = sys_get_temp_dir().'/vendor/arshwell/monolith/builds/sess_'.session_id().'/';

    Folder::remove($build_dir); // NOTE: safety decision

    Folder::copy('.', $build_dir);

    if ($form->value('css-js-files')) {
        // remove only css/js dev files
        Folder::remove($build_dir . 'uploads/design/dev/');
    }
    else {
        // keep .htaccess files
        foreach (File::rFolder($build_dir . 'uploads/') as $file) {
            if (basename($file) != '.htaccess') {
                unlink($file);
            }
        }
    }

    if (empty($form->value('table-files'))) {
        Folder::remove($build_dir . 'uploads/files/');
    }

    Folder::removeEmpty($build_dir . 'uploads/files/');

    $form->info = array("Project was copied in build.");
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
