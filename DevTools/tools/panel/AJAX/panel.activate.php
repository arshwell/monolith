<?php

use ArshWell\Monolith\DevTool\DevToolData;
use ArshWell\Monolith\Table\TableValidation;
use ArshWell\Monolith\Session;

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
