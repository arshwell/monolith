<?php

use Arsavinel\Arshwell\Table\TableValidation;

$form = TableValidation::run($_POST,
    array(
        'file' => array(
            "required|is_string"
        )
    ),
    array(
        'required'  => "Insert text you want to reverse",
        'is_string' => "No proper format"
    )
);

if ($form->valid()) {
    if (!is_file($form->value('file'))) {
        $form->info = array(
            "This file has been <u>already</u> deleted"
        );
    }
    else {
        unlink($form->value('file'));

        $form->info = array(
            "This file has been deleted"
        );
    }
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
