<?php

use Arsavinel\Arshwell\Table\TableValidation;

$form = TableValidation::run($_POST, array());

if ($form->valid()) {
	$form->info = array("Site history was enriched.");
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
