<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Time;
use Arshwell\Monolith\ENV;

$form = TableValidation::run($_POST,
    array(
        'type' => array(
            "inArray:none,smart,instant"
        )
    ),
    array(
        'inArray' => "Invalid value"
    )
);

if ($form->valid()) {
    (ENV::class('maintenance'))::setActive($form->value('type') != 'none');
    (ENV::class('maintenance'))::setSmart($form->value('type') != 'instant');

    $form->info = array(
        'status'    => 'Maintenance set',
        'PHP'       => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
