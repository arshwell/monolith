<?php

use Arsavinel\Arshwell\Table\TableValidation;
use Arsavinel\Arshwell\Module\Backend;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\Time;
use Arsavinel\Arshwell\DB;

$form = TableValidation::run($_POST,
    array(
        'remove-lg' => array(
            "required|int|inArray:0,1"
        )
    ),
    array(
        'required'  => 'Invalid radio',
        'int'       => 'Invalid radio',
        'inArray'   => 'Invalid radio'
    )
);

if ($form->valid()) {
    DB::beginTransaction();

        // Sync modules with DB
        foreach (File::rFolder('outcomes') as $file) {
            if (basename($file) == 'back.module.php') {
                $back = call_user_func(function () use ($file) {
                    return require($file);
                });

                if (!empty($back['DB']) && is_array($back['DB']) && class_exists($back['DB']['table'])
                && !empty($back['fields']) && is_array($back['fields'])) {
                    Backend::buildDB($back['DB'], $back['features'], $back['fields'], ($form->value('remove-lg') ? true : false));
                }
            }
        }

        $arshwell_errors = require("vendor/arsavinel/arshwell/DevTools/tools/validation.errors.php");
        $validation_tables = array();

        $composer = json_decode(file_get_contents(Folder::root() . 'composer.json'), true);

        foreach ($composer['autoload'] as $standard) {
            foreach ($standard as $namespace => $path) {
                foreach (Folder::children(Folder::root() . $path) as $folder) {
                    foreach (File::rFolder($folder) as $file) {
                        $class = str_replace(['/', '\\\\'], '\\', File::name(preg_replace("/^". preg_quote(Folder::shorter($path), '/') ."/", $namespace, Folder::shorter($file)), false));

                        // Create and update validation tables
                        if (class_exists($class) && is_subclass_of($class, TableValidation::class)) {
                            $languages      = (($class)::TRANSLATOR)::LANGUAGES;
                            $custom_errors  = array();
                            $validation_tables[] = $class;

                            if (DB::existsTable(($class)::TABLE)) {
                                $custom_errors = DB::select(array(
                                    'class'     => $class,
                                    'columns'   => "*",
                                    'where'     => "error NOT IN ('". implode("', '", array_column($arshwell_errors, 'error')) ."')"
                                ));
                                DB::truncateTable(($class)::TABLE);

                                foreach ($languages as $language) {
                                    DB::alterTable(($class)::TABLE, 'ADD', 'message_'.$language, 'TEXT NOT NULL');
                                }
                            }
                            else {
                                DB::createTable(($class)::TABLE, array_merge(
                                    array(
                                        "error VARCHAR(100) NOT NULL",
                                        "vars INT(3) DEFAULT 0"
                                    ),
                                    array_map(function ($language) {
                                        return '`message_'.$language.'` TEXT NOT NULL';
                                    }, $languages)
                                ));
                            }

                            $params = array();
                            foreach (array_merge($custom_errors, $arshwell_errors) as $i => $error) {
                                $params[] = $error['error'];
                                $params[] = $error['vars'];

                                foreach ($languages as $language) {
                                    $params[] = ($error[$language] ?? $error['en'] ?? $error['ro']);
                                }
                            }

                            ($class)::insert(
                                "error, vars, message_". implode(', message_', $languages),
                                array_fill(0, $i + 1, array_fill(0, count($languages) + 2, '?')),
                                $params
                            );
                        }
                    }
                }
            }
        }

    DB::commit();

    $form->info = array(
        'Validation tables' => $validation_tables,
        'PHP'               => Time::readableTime((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
    );
}
else if ($form->expired()) {
    $form->info = array("Session expired. Reopen DevPanel!");
}

echo $form->json();
