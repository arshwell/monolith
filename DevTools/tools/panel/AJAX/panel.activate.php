<?php

use Arsh\Core\Table\TableValidation;
use Arsh\Core\Session;

$form = TableValidation::run($_POST, array());

if ($form->valid()) {
    Session::setPanel('active',	true);
}

echo $form->json();
