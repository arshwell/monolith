<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\Time;

$form = TableValidation::run($_POST,
    array(
        'mkdir' => array(
            'optional|equal:1'
        ),
        'behavior' => array(
            'required|is_string|inArray:stop,replace,merge'
        ),
        'source' => array(
            "required|is_string",
            function ($key, $value) {
                if (!is_dir($value)) {
                    return "Source doesn't exist";
                }
            }
        ),
        'destination' => array(
            "required|is_string",
            function ($key, $value) {
                if (!is_dir(dirname($value)) && !self::value('mkdir')) {
                    return "Destination's dirs don't exist";
                }
                if (is_dir($value) && self::value('behavior') == 'stop') {
                    return "Destination already exists";
                }
            }
        )
    ),
    array(
        'required'  => 'Insert folder path',
        'is_string' => 'Invalid folder',
        'equal'     => 'Invalid checkbox',
        'inArray'   => 'Invalid radio'
    )
);

if ($form->valid()) {
    if ($form->value('behavior') == 'replace') {
        Folder::remove($form->value('destination'));
    }

    Folder::copy($form->value('source'), $form->value('destination'));

    $form->info = array(
        'status'    => "Source was copied in the destination",
        'PHP'       => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
