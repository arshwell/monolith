<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Time;

$form = TableValidation::run($_POST, array());

if ($form->valid()) {
    Session::empty();

    $form->info = array(
        'status'    => 'The session has been emptied',
        'PHP'       => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
