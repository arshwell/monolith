<?php

use ArshWell\Monolith\DevTool\DevToolHTML;
use ArshWell\Monolith\ENV;

call_user_func(function () {
    $regex = "/^[a-z]{2}$/";

    $recursively = function (array $utils) use ($regex, &$recursively): void {
        foreach ($utils as $class) {
            if (is_array($class)) {
                $recursively($class);
            }
            else {
                if (!class_exists($class) || !defined($class."::LANGUAGES") || !is_subclass_of($class, "ArshWell\Monolith\Language")) {
                    DevToolHTML::html(
                        '<i>env.php</i> > <i>'.$class.'</i><br>' .
                        DevToolHTML::code(file_get_contents(str_replace('\\', '/', $class) . '.php')) .
                        DevToolHTML::error("ENV translations classes should extend <i>ArshWell\Monolith\Language</i> and contain LANGUAGES array constant.")
                    );
                }
                foreach (($class)::LANGUAGES as $lang) {
                    if (!preg_match($regex, $lang)) {
                        DevToolHTML::html(
                            '<i>env.php</i> > <i>'.$class.'</i><br>' .
                            DevToolHTML::code(DevToolHTML::var("LANGUAGES") .' = '. DevToolHTML::array(($class)::LANGUAGES) .';') .
                            DevToolHTML::error("Language codes should be valid (".$regex."). ArshWell accepts ". DevToolHTML::link("https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes", "these ones") .".")
                        );
                    }
                }
            }
        }
    };

    $recursively(ENV::class('translation')::langsPerWebGroup());
});
