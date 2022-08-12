<?php

use Arsh\Core\Table\TableValidation;
use Arsh\Core\ENV;

$form = TableValidation::run($_POST, array(), false);

if ($form->valid()) {
	if (is_file('env.build.json')) {
        $env = ENV::fetch(sys_get_temp_dir().'/ArshWell/builds/sess_'.session_id(), true);
        $env->cache();

        $form->info = array("env.json <u>was merged</u> with env.build.json, in build.");
	}
	else {
        $form->info = array("env.build.json doesn't exist.");
	}
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
