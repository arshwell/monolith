<?php

use Arsavinel\Arshwell\Table\TableValidation;

$form = TableValidation::run($_POST, array(), false);

if ($form->valid()) {
    $form->info = array("Input is valid.");

    $build_zip = sys_get_temp_dir().'/vendor/arsavinel/arshwell/builds/sess_'.session_id().'.zip';

    if (is_file($build_zip)) {
        $form->info[] = "Deleting last build .zip";

        unlink($build_zip);
    }
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
