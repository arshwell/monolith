<?php

use Arshwell\Monolith\Table\TableValidation;

$form = TableValidation::run($_POST, array());

if ($form->valid()) {
	$form->info = array("Website history was enriched.");
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
