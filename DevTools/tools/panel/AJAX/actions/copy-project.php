<?php

use Arshwell\Monolith\Table\TableValidation;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Time;

$form = TableValidation::run($_POST,
    array(
        'replace' => array(
            "optional|equal:1"
        ),
        'folder' => array(
            "required",
            function ($value) {
                return urlencode($value);
            },
            function ($key, $value) {
                if (empty($value)) {
                    return "The urlencoding have been emptied the filename";
                }
                if (!self::value('replace') && is_dir('../'.$value)) {
                    return "Folder already exists";
                }
            }
        )
    ),
    array(
        'required'  => "Add the folder name",
        'equal'     => "Invalid checkbox"
    )
);

if ($form->valid()) {
    Folder::remove('../'. $form->value('folder'));
    Folder::copy('.', '../'. $form->value('folder'));

    foreach (File::folder('../'. $form->value('folder').'/layouts', ['json']) as $file) {
        touch($file);
    }

    $form->info = array(
        'status'    => "Project was copied in the destination.",
        'PHP'       => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
