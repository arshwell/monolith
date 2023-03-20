<?php

use Arshwell\Monolith\DevTool\DevToolData;
use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Session;

$form = TableValidation::run(
    $_POST,
    array(
        'pass' => array(
            "equal:arshwell".DevToolData::ArshwellVersionNumber()
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
