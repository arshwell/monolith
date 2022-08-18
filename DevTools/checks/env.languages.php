<?php

use Arsavinel\Arshwell\ENV;

call_user_func(function () {
    $regex = "/^[a-z]{2}$/";

    $recursively = function (array $utils) use ($regex, &$recursively): void {
        foreach ($utils as $class) {
            if (is_array($class)) {
                $recursively($class);
            }
            else {
                if (!class_exists($class) || !defined($class."::LANGUAGES") || !is_subclass_of($class, "Arsavinel\Arshwell\Language")) {
                    _html(
                        '<i>env.php</i> > <i>'.$class.'</i><br>' .
                        _code(file_get_contents(str_replace('\\', '/', $class) . '.php')) .
                        _error("ENV translations classes should extend <i>Arsavinel\Arshwell\Language</i> and contain LANGUAGES array constant.")
                    );
                }
                foreach (($class)::LANGUAGES as $lang) {
                    if (!preg_match($regex, $lang)) {
                        _html(
                            '<i>env.php</i> > <i>'.$class.'</i><br>' .
                            _code(_var("LANGUAGES") .' = '. _array(($class)::LANGUAGES) .';') .
                            _error("Language codes should be valid (".$regex."). ArshWell accepts ". _link("https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes", "these ones") .".")
                        );
                    }
                }
            }
        }
    };

    $recursively(ENV::class('translation')));
});
