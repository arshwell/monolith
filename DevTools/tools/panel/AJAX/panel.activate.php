<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Session;

$form = TableValidation::run($_POST, array());

if ($form->valid()) {
    Session::setPanel('active',	true);
}

echo $form->json();
