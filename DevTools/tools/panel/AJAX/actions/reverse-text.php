<?php

use Arsh\Core\Table\TableValidation;

$form = TableValidation::run($_POST,
    array(
        'text' => array(
            "required"
        )
    ),
    array(
        'required' => "Insert text you want to reverse"
    )
);

if ($form->valid()) {
    $form->info = array(
        '<span style="user-select: all;">'.strrev($form->value('text')).'</span>'
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
