<?php

use Arsavinel\Arshwell\DevTool\DevToolData;
use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Session;

$form = TableValidation::run(
    $_POST,
    array(
        'pass' => array(
            "equal:arshwell".DevToolData::ArshWellVersionNumber()
        )
    ),
    array(
        'equal' => "Wrong pass"
    )
);

if ($form->valid()) {
    Session::setPanel('active',	true);
}

echo $form->json();
