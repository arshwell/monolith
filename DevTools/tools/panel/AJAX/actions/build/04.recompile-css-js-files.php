<?php

use ArshWell\Monolith\Table\TableValidation;
use ArshWell\Monolith\ENV\ENVComponent;
use ArshWell\Monolith\Layout;
use ArshWell\Monolith\Folder;

$form = TableValidation::run($_POST, array(), false);

if ($form->valid()) {
    $build_dir = sys_get_temp_dir().'/vendor/arshwell/monolith/builds/sess_'.session_id().'/';

    $env = new ENVComponent($build_dir);

    try { // because could be thrown SCSS errors
        Layout::recompileSCSS(NULL, NULL, NULL, $env->url(), $build_dir);
        Layout::recompileMailSCSS(NULL, NULL, NULL, $env->url(), $build_dir);
        Layout::recompileJSHeader(NULL, $env->url(), $build_dir);
        Layout::recompileJSFooter(NULL, $build_dir);

		$form->info = array(
            "CSS/JS have been recompiled in build."
        );
    }
    catch (Exception $e) {
        $form->info = array(
            'error'     => Folder::shorter($e->getFile()) .':'. $e->getLine(),
            'message'   => $e->getMessage()
        );
    }
}
else if ($form->expired()) {
	$form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
