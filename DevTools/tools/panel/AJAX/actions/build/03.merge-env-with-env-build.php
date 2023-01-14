<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\ENV\ENVComponent;

$form = TableValidation::run($_POST, array(), false);

if ($form->valid()) {
	if (is_file('env.build.json')) {
        $env = new ENVComponent(sys_get_temp_dir().'/vendor/arsavinel/arshwell/builds/sess_'.session_id());

        $env->mergeWithEnvBuild();

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
