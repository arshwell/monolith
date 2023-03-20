<?php

namespace Arshwell\Monolith\Module\Syntax\Frontend\Field;

class JS {

    public static function AJAX (bool $AJAX): bool {
        return $AJAX;
    }

    public static function update (bool $update): bool {
        return $update;
    }

    /**
     * (bool|array) $tinymce
    */
    public static function tinymce ($tinymce) {
        return $tinymce;
    }

    /**
     * (bool|array) $tagsinput
    */
    public static function tagsinput ($tagsinput) {
        return $tagsinput;
    }

    public static function multiselect (bool $multiselect): bool {
        return $multiselect;
    }
}
