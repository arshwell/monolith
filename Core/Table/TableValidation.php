<?php

namespace Arsh\Core\Table;

use Arsh\Core\Table\TableValidationResponse;
use Arsh\Core\Session;
use Arsh\Core\Filter;
use Arsh\Core\Table;
use Arsh\Core\File;
use Arsh\Core\Text;
use Arsh\Core\DB;
use ReflectionFunction;
use ReflectionMethod;
use Closure;

/**
 * Form class for fields trimming and validating.
 * Made especially for html forms.
 * It also allows you to create custom validation rules, in TableValidation::message().

 * @package https://github.com/arshavin-dev/ArshWell
*/
abstract class TableValidation extends Table {
    const PRIMARY_KEY   = NULL;
    const TRANSLATED    = array('message');

    private static $tag_groups;
    private static $tags;
    private static $values;
    private static $errors;
    private static $valid;

    final static function session (string $route, string $suffix = NULL): object {
        return new TableValidationResponse((Session::form(($suffix ? ($suffix.'.'.$route.'.'.$suffix) : $route)) ?? array()));
    }

    /**
     * (bool|array) $messages
     */
    final static function run (array $fields, array $protocols = array(), $messages = true): object {
        // If there's no form_token or wrong one,
        // could mean non-inside-browser request or new session.
        if (!isset($fields['form_token']) || $fields['form_token'] !== Session::token('form')) {
            return new TableValidationResponse(array(
                'submitted' => true,
                'expired'   => true,
                'valid'     => false
            ));
        }
        unset($fields['form_token']); // so $protocols can't validate it

        self::$tag_groups   = array();
        self::$tags         = array();
        self::$values = array();
        self::$errors = array();
        self::$valid  = true;

        /**
         * (string|array) $value
         */
        $fn_check = function (string $action, &$value, string $sub_field) use (&$messages): ?string {
            $pieces = explode(':', $action);
            $rule   = array_shift($pieces); // rule name
            $params = explode(',', (implode(':', $pieces) ?? NULL)); // list of params (if exists)

            $message = ($messages == false ? true : $messages[$rule] ?? NULL);

            switch ($rule) {
                case 'optional':
                    // says that this field may not exist (ex: empty arrays | optional text)
                    break;
                case 'required':
                    if (!is_numeric($value) && empty($value)) {
                        return ($message ?? self::message('required'));
                    }
                    break;
                case 'alphaNumeric':
                    if ((is_numeric($value) || !empty($value)) && !ctype_alnum(str_replace(' ', '', $value))) {
                        return ($message ?? self::message('alphaNumeric'));
                    }
                    break;
                case 'letters':
                    if ((is_numeric($value) || !empty($value)) && !ctype_alpha(str_replace(' ', '', $value))) {
                        return ($message ?? self::message('letters'));
                    }
                    break;
                case 'date':
                    if (is_numeric($value) || !empty($value)) {
                        $string_date = date("m/d/Y", strtotime($value));
                        $array_date = explode('/', $string_date);
                        if (!checkdate($array_date[0], $array_date[1], $array_date[2]) || $string_date == "01/01/1970") {
                            return ($message ?? self::message('date'));
                        }
                    }
                    break;
                case 'dateMultiple':
                    if (is_numeric($value) || !empty($value)) {
                        $vals = explode(",", $value);
                        $invalid = '';
                        foreach ($vals as $val) {
                            $string_date = date("m/d/Y", strtotime($val));
                            $array_date  = explode('/', $string_date);
                            if (!checkdate($array_date[0], $array_date[1], $array_date[2]) || $string_date == "01/01/1970") {
                                $invalid .= ($string_date . ', ');
                            }
                        }
                        if ($invalid) {
                            return ($message ?? self::message('dateMultiple', [$invalid]));
                        }
                    }
                    break;
                case 'numeric':
                    if ($value != NULL && $value != '' && !is_numeric($value)) {
                        return ($message ?? self::message('numeric'));
                    }
                    break;
                case 'int':
                    if ((is_numeric($value) || !empty($value)) && !is_int($value) && (!is_string($value) || !preg_match('/^(-)?\d+$/', $value))) {
                        return ($message ?? self::message('int'));
                    }
                    break;
                case 'float':
                    if ((is_numeric($value) || !empty($value)) && !preg_match('/^(-)?[0-9]+(\.[0-9]+)?$/', $value)) {
                        return ($message ?? self::message('float'));
                    }
                    break;
                case 'min':
                    if ((is_numeric($value) || !empty($value)) && $value < $params[0]) {
                        return ($message ?? self::message('min', [$value, $params[0]]));
                    }
                    break;
                case 'max':
                    if ((is_numeric($value) || !empty($value)) && $value > $params[0]) {
                        return ($message ?? self::message('max', [$value, $params[0]]));
                    }
                    break;
                case 'minLength':
                    if ((is_numeric($value) || !empty($value)) && strlen($value) < $params[0]) {
                        return ($message ?? self::message('minLength', [strlen($value), $params[0]]));
                    }
                    break;
                case 'length':
                    if ((is_numeric($value) || !empty($value)) && strlen($value) != $params[0]) {
                        return ($message ?? self::message('length', [strlen($value), $params[0]]));
                    }
                    break;
                case 'maxLength':
                    if ((is_numeric($value) || !empty($value)) && strlen($value) > $params[0]) {
                        return ($message ?? self::message('maxLength', [strlen($value), $params[0]]));
                    }
                    break;
                case 'inArray':
                    if ((is_numeric($value) || !empty($value)) && !in_array($value, $params)) {
                        return ($message ?? self::message('inArray', [implode(', ', $params)]));
                    }
                    break;
                case 'notInArray':
                    if ((is_numeric($value) || !empty($value)) && in_array($value, $params)) {
                        return ($message ?? self::message('notInArray', [implode(', ', $params)]));
                    }
                    break;
                case 'arrayEqual':
                    foreach ($params as $param) {
                        if ($value != self::$values[$param]) {
                            return ($message ?? self::message('arrayEqual', [implode(', ', $params)]));
                        }
                    }
                    break;
                case 'arrayNotEqual':
                    foreach ($params as $param) {
                        if ($value == self::$values[$param]) {
                            return ($message ?? self::message('arrayNotEqual', [implode(', ', $params)]));
                        }
                    }
                    break;
                case 'equal':
                    if ((is_numeric($value) || !empty($value)) && $value != $params[0]) {
                        return ($message ?? self::message('equal'));
                    }
                    break;
                case 'notEqual':
                    if ((is_numeric($value) || !empty($value)) && $value == $params[0]) {
                        return ($message ?? self::message('notEqual'));
                    }
                    break;
                case 'array':
                    if ((is_numeric($value) || !empty($value)) && !is_array($value)) {
                        return ($message ?? self::message('array'));
                    }
                    break;
                case 'distinct':
                    if ((is_numeric($value) || !empty($value)) && $value != array_unique($value)) {
                        return ($message ?? self::message('distinct'));
                    }
                    break;
                case 'minCount':
                    if ((is_numeric($value) || !empty($value)) && count($value) < $params[0]) {
                        return ($message ?? self::message('minCount', [$params[0]]));
                    }
                    break;
                case 'count':
                    if ((is_numeric($value) || !empty($value)) && count($value) != $params[0]) {
                        return ($message ?? self::message('count', [$params[0]]));
                    }
                    break;
                case 'maxCount':
                    if ((is_numeric($value) || !empty($value)) && count($value) > $params[0]) {
                        return ($message ?? self::message('maxCount', [$params[0]]));
                    }
                    break;
                case 'email':
                    if ((is_numeric($value) || !empty($value)) && !Filter::isEmail($value)) {
                        return ($message ?? self::message('email'));
                    }
                    break;
                case 'url':
                    if ((is_numeric($value) || !empty($value)) && !Filter::isURL($value)) {
                        return ($message ?? self::message('url'));
                    }
                    break;
                case 'cnp':
                    if ((is_numeric($value) || !empty($value)) && !Filter::isCNP($value)) {
                        return ($message ?? self::message('cnp'));
                    }
                    break;
                case 'json':
                    if (is_numeric($value) || !empty($value)) {
                        json_decode($value);
                        if (json_last_error() != JSON_ERROR_NONE) {
                            return ($message ?? self::message('json'));
                        }
                    }
                    break;
                case 'match':
                    if ((is_numeric($value) || !empty($value)) && preg_match($params[0], $value) == 0) {
                        return ($message ?? self::message('match'));
                    }
                    break;
                case 'inDB':
                    if ((is_numeric($value) || !empty($value)) &&
                    !DB::count($params[0], ($params[1] ?? $sub_field) .' = ?', array($value))) {
                        return ($message ?? self::message('dbExists'));
                    }
                    break;
                case 'notInDB':
                    if ((is_numeric($value) || !empty($value)) &&
                    DB::count($params[0], ($params[1] ?? $sub_field) .' = ?', array($value))) {
                        return ($message ?? self::message('dbUnique'));
                    }
                    break;
                case 'likeDB':
                    if ((is_numeric($value) || !empty($value)) &&
                    !DB::count($params[0], ($params[1] ?? $sub_field) .' LIKE ?', array($value))) {
                        return ($message ?? self::message('dbLike'));
                    }
                    break;
                case 'notLikeDB':
                    if ((is_numeric($value) || !empty($value)) &&
                    DB::count($params[0], ($params[1] ?? $sub_field) .' LIKE ?', array($value))) {
                        return ($message ?? self::message('dbNotLike'));
                    }
                    break;
                case 'doc': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !isset($value['type'])
                            || !isset($value['tmp_name']) || !is_file($value['tmp_name'])
                            || !isset($value['error']) || !is_numeric($value['error'])
                            || !isset($value['size']) || !is_numeric($value['size'])) {
                                return $message ?? self::message('required');
                            }

                            switch ($value['error']) {
                                case 0: {
                                    break;
                                }
                                case 1: {
                                    return $message ?? self::message('exceeded_php_size');
                                }
                                case 2: {
                                    return $message ?? self::message('exceeded_html_size');
                                }
                                case 3: {
                                    return $message ?? self::message('partially_uploaded');
                                }
                                case 4: {
                                    return $message ?? self::message('no_file');
                                }
                                case 6: {
                                    return $message ?? self::message('missing_tmp_folder');
                                }
                                case 7: {
                                    return $message ?? self::message('error_writing_disk');
                                }
                                case 8: {
                                    return $message ?? self::message('php_extension_problem');
                                }
                            }

                            if (in_array($value['name'], ['.htaccess', '.htpasswd'])
                            || in_array(File::extension($value['name']), ['php', 'phtml'])
                            || in_array(File::mimeType($value['tmp_name']), [NULL, 'text/x-php'])) {
                                return $message ?? self::message('mime_type_forbidden');
                            }
                        // ↑ minimal file validation //

                        // custom DOC validation
                        if ($params[0]) {
                            $filekey = ($params[1] ?? $sub_field);

                            if (isset(($params[0])::FILES[$filekey]['mimes'])
                            && !in_array($value['type'], ($params[0])::FILES[$filekey]['mimes'])) {
                                return $message ?? self::message('mimeTypes', [implode(', ', ($params[0])::FILES[$filekey]['mimes'])]);
                            }

                            if (isset(($params[0])::FILES[$filekey]['bytes'])) {
                                if ($value['size'] < ($params[0])::FILES[$filekey]['bytes'][0]) {
                                    return ($message ?? self::message('small_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][0])
                                    ]));
                                }
                                if ($value['size'] > ($params[0])::FILES[$filekey]['bytes'][1]) {
                                    return ($message ?? self::message('big_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][1])
                                    ]));
                                }
                            }
                            else if ($value['size'] > 614400) {
                                return ($message ?? self::message('big_file_size', [
                                    $value['name'],
                                    File::readableSize($value['size']),
                                    File::readableSize(614400) // 600 kB
                                ]));
                            }
                        }
                    }
                    break;
                }
                case 'image': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !isset($value['type'])
                            || !isset($value['tmp_name']) || !is_file($value['tmp_name'])
                            || !isset($value['error']) || !is_numeric($value['error'])
                            || !isset($value['size']) || !is_numeric($value['size'])) {
                                return $message ?? self::message('required');
                            }

                            switch ($value['error']) {
                                case 0: {
                                    break;
                                }
                                case 1: {
                                    return $message ?? self::message('exceeded_php_size');
                                }
                                case 2: {
                                    return $message ?? self::message('exceeded_html_size');
                                }
                                case 3: {
                                    return $message ?? self::message('partially_uploaded');
                                }
                                case 4: {
                                    return $message ?? self::message('no_file');
                                }
                                case 6: {
                                    return $message ?? self::message('missing_tmp_folder');
                                }
                                case 7: {
                                    return $message ?? self::message('error_writing_disk');
                                }
                                case 8: {
                                    return $message ?? self::message('php_extension_problem');
                                }
                            }

                            // security validation
                            if (in_array($value['name'], ['.htaccess', '.htpasswd'])
                            || in_array(File::extension($value['name']), ['php', 'phtml'])
                            || in_array(File::mimeType($value['tmp_name']), [NULL, 'text/x-php'])) {
                                return $message ?? self::message('mime_type_forbidden');
                            }
                        // ↑ minimal file validation //


                        // custom IMAGE validation
                        if ($params[0]) {
                            $filekey = ($params[1] ?? $sub_field);

                            $dataimage = getimagesize($value['tmp_name']);

                            // NOTE: Arsh\Core\Table\TableView doesn't have sizes set
                            if (isset(($params[0])::FILES[$filekey]['sizes'])) {
                                $sizes = ($params[0])::FILES[$filekey]['sizes'];

                                foreach ($sizes as $size => $ranges) {
                                    $ranges['width'] = array_values((array)($ranges['width'] ?? array(NULL)));

                                        if (array_key_exists(1, $ranges['width']) == false) {
                                            $ranges['width'][1] = $ranges['width'][0];
                                        }

                                    $ranges['height'] = array_values((array)($ranges['height'] ?? array(NULL)));

                                        if (array_key_exists(1, $ranges['height']) == false) {
                                            $ranges['height'][1] = $ranges['height'][0];
                                        }

                                    $sizes[$size] = $ranges;
                                }

                                $maxW = max(array_column(array_column($sizes, 'width'), 0));
                                $maxH = max(array_column(array_column($sizes, 'height'), 0));

                                if ($dataimage[0] < $maxW || $dataimage[1] < $maxH) {
                                    return ($message ?? self::message('small_image', [$value['name'], $dataimage[0].'x'.$dataimage[1], ($maxW ?: '(auto)').'x'.($maxH ?: '(auto)')]));
                                }
                            }

                            if (isset(($params[0])::FILES[$filekey]['mimes'])) {
                                if (!in_array($value['type'], ($params[0])::FILES[$filekey]['mimes'])) {
                                    return $message ?? self::message('mimeTypes', [implode(', ', ($params[0])::FILES[$filekey]['mimes'])]);
                                }
                            }
                            else if (!in_array($dataimage[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) {
                                return ($message ?? self::message('no_image_type'));
                            }

                            if (isset(($params[0])::FILES[$filekey]['bytes'])) {
                                if ($value['size'] < ($params[0])::FILES[$filekey]['bytes'][0]) {
                                    return ($message ?? self::message('small_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][0])
                                    ]));
                                }
                                if ($value['size'] > ($params[0])::FILES[$filekey]['bytes'][1]) {
                                    return ($message ?? self::message('big_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][1])
                                    ]));
                                }
                            }
                            else if ($value['size'] > 614400) {
                                return ($message ?? self::message('big_file_size', [
                                    $value['name'],
                                    File::readableSize($value['size']),
                                    File::readableSize(614400) // 600 kB
                                ]));
                            }
                        }

                        $value['name'] = Text::slug(File::name($value['name'])) .'.'. File::extension($value['name']);
                    }
                    break;
                }
                case 'video': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !isset($value['type'])
                            || !isset($value['tmp_name']) || !is_file($value['tmp_name'])
                            || !isset($value['error']) || !is_numeric($value['error'])
                            || !isset($value['size']) || !is_numeric($value['size'])) {
                                return $message ?? self::message('required');
                            }

                            switch ($value['error']) {
                                case 0: {
                                    break;
                                }
                                case 1: {
                                    return $message ?? self::message('exceeded_php_size');
                                }
                                case 2: {
                                    return $message ?? self::message('exceeded_html_size');
                                }
                                case 3: {
                                    return $message ?? self::message('partially_uploaded');
                                }
                                case 4: {
                                    return $message ?? self::message('no_file');
                                }
                                case 6: {
                                    return $message ?? self::message('missing_tmp_folder');
                                }
                                case 7: {
                                    return $message ?? self::message('error_writing_disk');
                                }
                                case 8: {
                                    return $message ?? self::message('php_extension_problem');
                                }
                            }

                            // security validation
                            if (in_array($value['name'], ['.htaccess', '.htpasswd'])
                            || in_array(File::extension($value['name']), ['php', 'phtml'])
                            || in_array(File::mimeType($value['tmp_name']), [NULL, 'text/x-php'])) {
                                return $message ?? self::message('mime_type_forbidden');
                            }
                        // ↑ minimal file validation //


                        // custom VIDEO validation
                        if ($params[0]) {
                            $filekey = ($params[1] ?? $sub_field);

                            if (isset(($params[0])::FILES[$filekey]['mimes'])) {
                                if (!in_array($value['type'], ($params[0])::FILES[$filekey]['mimes'])) {
                                    return $message ?? self::message('mimeTypes', [implode(', ', ($params[0])::FILES[$filekey]['mimes'])]);
                                }
                            }
                            else if (!in_array($value['type'], array("video/mp4", "audio/mp3", "audio/wma", "image/gif", "image/pjpeg"))) {
                                return ($message ?? self::message('no_video_type'));
                            }

                            if (isset(($params[0])::FILES[$filekey]['bytes'])) {
                                if ($value['size'] < ($params[0])::FILES[$filekey]['bytes'][0]) {
                                    return ($message ?? self::message('small_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][0])
                                    ]));
                                }
                                if ($value['size'] > ($params[0])::FILES[$filekey]['bytes'][1]) {
                                    return ($message ?? self::message('big_file_size', [
                                        $value['name'],
                                        File::readableSize($value['size']),
                                        File::readableSize(($params[0])::FILES[$filekey]['bytes'][1])
                                    ]));
                                }
                            }
                            else if ($value['size'] > 52430000) {
                                return ($message ?? self::message('big_file_size', [
                                    $value['name'],
                                    File::readableSize($value['size']),
                                    File::readableSize(52430000) // 50 MB
                                ]));
                            }
                        }

                        $value['name'] = Text::slug(File::name($value['name'])) .'.'. File::extension($value['name']);
                    }
                    break;
                }
                case 'docs': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !is_array($value['name'])
                            || !isset($value['type']) || !is_array($value['type'])
                            || !isset($value['tmp_name']) || !is_array($value['tmp_name'])
                            || !isset($value['error']) || !is_array($value['error'])
                            || !isset($value['size']) || !is_array($value['size'])) {
                                return $message ?? self::message('required');
                            }

                            foreach ($value['error'] as $error) {
                                switch ($error) {
                                    case 0:
                                        break;
                                    case 1:
                                        return $message ?? self::message('exceeded_php_size');
                                        break;
                                    case 2:
                                        return $message ?? self::message('exceeded_html_size');
                                        break;
                                    case 3:
                                        return $message ?? self::message('partially_uploaded');
                                        break;
                                    case 4:
                                        return $message ?? self::message('no_file');
                                        break;
                                    case 6:
                                        return $message ?? self::message('missing_tmp_folder');
                                        break;
                                    case 7:
                                        return $message ?? self::message('error_writing_disk');
                                        break;
                                    case 8:
                                        return $message ?? self::message('php_extension_problem');
                                        break;
                                }
                            }
                            foreach ($value['tmp_name'] as $tmp_name) {
                                if (!is_file($tmp_name)) {
                                    return $message ?? self::message('required');
                                }
                                if (in_array(File::mimeType($tmp_name), [NULL, 'text/x-php'])) {
                                    return $message ?? self::message('mime_type_forbidden');
                                }
                            }
                            foreach ($value['name'] as $name) {
                                if (in_array($name, ['.htaccess', '.htpasswd'])
                                || in_array(File::extension($name), ['php', 'phtml'])) {
                                    return $message ?? self::message('mime_type_forbidden');
                                }
                            }
                        // ↑ minimal file validation //


                        // custom DOCS validation
                        if ($params[0]) {
                            $filekey = ($params[1] ?? $sub_field);

                            if (isset(($params[0])::FILES[$filekey]['mimes'])) {
                                for ($i = 0, $len = count($value['name']); $i < $len; $i++) {
                                    if ($value['size'][$i] > 0) {
                                        if (!in_array($value['type'][$i], ($params[0])::FILES[$filekey]['mimes'])) {
                                            return $message ?? self::message('mimeTypes', [implode(', ', ($params[0])::FILES[$filekey]['mimes'])]);
                                        }
                                    }
                                }
                            }
                        }

                        foreach ($value['name'] as &$name) {
                            $name = Text::slug(File::name($name)) .'.'. File::extension($name);
                            unset($name);
                        }
                    }
                    break;
                }
                case 'images': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !is_array($value['name'])
                            || !isset($value['type']) || !is_array($value['type'])
                            || !isset($value['tmp_name']) || !is_array($value['tmp_name'])
                            || !isset($value['error']) || !is_array($value['error'])
                            || !isset($value['size']) || !is_array($value['size'])) {
                                return $message ?? self::message('required');
                            }

                            foreach ($value['error'] as $error) {
                                switch ($error) {
                                    case 0:
                                        break;
                                    case 1:
                                        return ($message ?? self::message('exceeded_php_size'));
                                    case 2:
                                        return ($message ?? self::message('exceeded_html_size'));
                                    case 3:
                                        return ($message ?? self::message('partially_uploaded'));
                                    case 4:
                                        return ($message ?? self::message('no_file'));
                                    case 6:
                                        return ($message ?? self::message('missing_tmp_folder'));
                                    case 7:
                                        return ($message ?? self::message('error_writing_disk'));
                                    case 8:
                                        return ($message ?? self::message('php_extension_problem'));
                                }
                            }
                            foreach ($value['tmp_name'] as $tmp_name) {
                                if (!is_file($tmp_name)) {
                                    return ($message ?? self::message('required'));
                                }
                                if (in_array(File::mimeType($tmp_name), [NULL, 'text/x-php'])) {
                                    return ($message ?? self::message('mime_type_forbidden'));
                                }
                            }
                            foreach ($value['name'] as $name) {
                                if (in_array($name, ['.htaccess', '.htpasswd'])
                                || in_array(File::extension($name), ['php', 'phtml'])) {
                                    return ($message ?? self::message('mime_type_forbidden'));
                                }
                            }
                        // ↑ minimal file validation //


                        // custom IMAGE validation
                        if ($params[0]) {
                            $filekey = ($params[1] ?? $sub_field);

                            if (isset(($params[0])::FILES[$filekey]['bytes'])) {
                                foreach ($value['size'] as $k => $size) {
                                    if ($size < ($params[0])::FILES[$filekey]['bytes'][0]) {
                                        return ($message ?? self::message('small_file_size', [
                                            $value['name'][$k],
                                            File::readableSize($size),
                                            File::readableSize(($params[0])::FILES[$filekey]['bytes'][0])
                                        ]));
                                    }
                                    if ($size > ($params[0])::FILES[$filekey]['bytes'][1]) {
                                        return ($message ?? self::message('big_file_size', [
                                            $value['name'][$k],
                                            File::readableSize($size),
                                            File::readableSize(($params[0])::FILES[$filekey]['bytes'][1])
                                        ]));
                                    }
                                }
                            }

                            foreach ($value['tmp_name'] as $k => $tmp_name) {
                                $dataimage = getimagesize($value['tmp_name'][$k]);

                                // NOTE: Arsh\Core\Table\TableView doesn't have sizes set
                                if (isset(($params[0])::FILES[$filekey]['sizes'])) {
                                    $sizes = ($params[0])::FILES[$filekey]['sizes'];

                                    foreach ($sizes as $size => $ranges) {
                                        $ranges['width'] = array_values((array)($ranges['width']));

                                            if (array_key_exists(1, $ranges['width']) == false) {
                                                $ranges['width'][1] = $ranges['width'][0];
                                            }

                                        $ranges['height'] = array_values((array)($ranges['height']));

                                            if (array_key_exists(1, $ranges['height']) == false) {
                                                $ranges['height'][1] = $ranges['height'][0];
                                            }

                                        $sizes[$size] = $ranges;
                                    }

                                    $maxW = max(array_column(array_column($sizes, 'width'), 0));
                                    $maxH = max(array_column(array_column($sizes, 'height'), 0));

                                    if ($dataimage[0] < $maxW || $dataimage[1] < $maxH) {
                                        return ($message ?? self::message('small_image', [$value['name'][$k], $dataimage[0].'x'.$dataimage[1], ($maxW ?: '(auto)').'x'.($maxH ?: '(auto)')]));
                                    }
                                }

                                if (isset(($params[0])::FILES[$filekey]['mimes'])) {
                                    if (!in_array($value['type'][$k], ($params[0])::FILES[$filekey]['mimes'])) {
                                        return ($message ?? self::message('mimeTypes', [implode(', ', ($params[0])::FILES[$filekey]['mimes'])]));
                                    }
                                }
                                else if (!in_array($dataimage[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) {
                                    return ($message ?? self::message('no_image_type'));
                                }
                            }
                        }

                        foreach ($value['name'] as &$name) {
                            $name = Text::slug(File::name($name)) .'.'. File::extension($name);
                            unset($name);
                        }
                    }
                    break;
                }
                case 'maxBytes': {
                    if (!empty($value)) {
                        // minimal file validation //
                            if (!isset($value['name']) || !isset($value['type'])
                            || !isset($value['tmp_name']) || !is_file($value['tmp_name'])
                            || !isset($value['size']) || !is_numeric($value['size'])) {
                                return $message ?? self::message('required');
                            }
                        // ↑ minimal file validation //

                        // custom IMAGE validation
                        if (!empty($params[0]) && is_numeric($params[0])) {
                            if ($value['size'] > $params[0]) {
                                return ($message ?? self::message('big_file_size', [
                                    $value['name'],
                                    File::readableSize($value['size']),
                                    File::readableSize($params[0])
                                ]));
                            }
                        }
                    }
                    break;
                }
                default: {
                    // Assuming, the keyword, is a php, default or defined, function.
                    if (is_numeric($value) || !empty($value)) {
                        // if oposite
                        if (strpos($rule, '!') === 0) {
                            if (substr($rule, 1)($value) == true) {
                                return ($message ?? self::message($rule));
                            }
                        }
                        else if ($rule($value) == false) {
                            return ($message ?? self::message($rule));
                        }
                    }
                    break;
                }
            }
            return NULL;
        };

        $fn_recursive = function (string $parent = NULL, string $name, &$value, array $sections) use (&$fn_recursive, &$fn_check, &$messages) {
            $field = (($parent ? ($parent .'.') : '') . $name);

            self::$values[$field]       = &$value;
            self::$tag_groups[$field]   = ($parent ? (self::$tag_groups[$parent] .'['. $name .']') : $name);

            // if not existing field, stop validating
            if (!isset(self::$values[$field]) && is_string(current($sections))) {
                if (explode('|', $sections[0])[0] != 'optional') {
                    self::$valid = false;
                    self::$errors[$field] = ($messages == false ? true : $messages['required'] ?? self::message('required'));
                }
                return;
            }

            foreach ($sections as $sub_field => $section) {
                // apply rules
                if (is_string($section)) {
                    $actions = explode('|', $section);

                    // for preventing, so called arrays, which are NULL, they get an empty array()
                    if (in_array('array', $actions) && empty(self::$values[$field])) {
                        self::$values[$field] = array();
                    }
                    if ((!in_array('optional', $actions) || isset(self::$values[$field])) && !isset(self::$errors[$field])) {
                        foreach ($actions as $action) {
                            if ((self::$errors[$field] = $fn_check($action, self::$values[$field], $name))) {
                                self::$valid = false;
                                break; // if found error, stop looking for another
                            }
                        }
                    }
                }

                // Call custom function rules (closures | array class methods).
                // NOTE: Name functions are not permitted!
                else if (is_callable($section)) {
                    switch (count(((is_object($section) || (is_string($section) && function_exists($section)) ?
                        new ReflectionFunction($section) :
                        (is_array($section) ?
                            new ReflectionMethod($section[0], $section[1]) :
                            new ReflectionMethod(strstr($section, '::', true), ltrim(strstr($section, '::'), ':'))
                        )
                    ))->getParameters())) {
                        case 1: {
                            if (!isset(self::$errors[$field])) {
                                // editing value function
                                self::$values[$field] = ($section instanceof Closure ?
                                    Closure::fromCallable($section)->bindTo(NULL, static::class)(self::$values[$field])
                                    :
                                    call_user_func($section, self::$values[$field])
                                ); // param ($field)
                            }
                            break;
                        }
                        case 2: {
                            if (!isset(self::$errors[$field])) {
                                $response = ($section instanceof Closure ?
                                    Closure::fromCallable($section)->bindTo(NULL, static::class)($name, self::$values[$field])
                                    :
                                    call_user_func($section, $name, self::$values[$field])
                                );
                                if (is_string($response)) {
                                    self::$errors[$field] = $response;
                                    self::$valid = false;
                                }
                                else if (is_array($response)) {
                                    $fn_recursive($parent, $name, self::$values[$field], $response);
                                }
                            }
                            break;
                        }
                        case 3: {
                            foreach (self::$values[$field] as $key => &$v) {
                                if (!isset(self::$errors[$field.'.'.$key])) {
                                    $response = ($section instanceof Closure ?
                                        Closure::bind($section, NULL, static::class)($name, $key, $v)
                                        :
                                        call_user_func($section, $name, $key, $v)
                                    );

                                    if (is_string($response)) {
                                        self::$errors[$field.'.'.$key] = $response;
                                        self::$valid = false;
                                    }
                                    else if (is_array($response)) {
                                        $fn_recursive($field, $key, $v, $response);
                                    }
                                }
                                unset($v); // so we can pass always by reference
                            }
                            break;
                        }
                    }
                }

                // child inside it, with its own section (trimming, rules, custom checks, children)
                else if (is_array($section) && is_array(self::$values[$field])) {
                    if (is_int($sub_field) || $sub_field == NULL) { // means it's about unspecified child fields
                        foreach (self::$values[$field] as $sub_field => &$sub_value) {
                            if (!isset(self::$errors[$field.'.'.$sub_field])) { // apply section for all unverified child fields
                                // recursive function for finding child field errors
                                $fn_recursive($field, $sub_field, $sub_value, $section);
                            }
                            unset($sub_value); // so we can pass always by reference
                        }
                    }
                    else {
                        if (!isset(self::$values[$field][$sub_field])) {
                            self::$values[$field][$sub_field] = NULL; // so we can pass always by reference
                        }

                        // apply section only for the specific child field ($field[$name])
                        // recursive function for finding field errors
                        $fn_recursive($field, $sub_field, self::$values[$field][$sub_field], $section);
                    }
                }
            }

            if (!is_array(self::$values[$field])) {
                self::$tags[('[name="'. self::$tag_groups[$field] .'"]')] = self::$values[$field];
            }
        };

        // iterate field rules
        foreach ($protocols as $name => $sections) {
            if (!isset($fields[$name])) {
                $fields[$name] = NULL; // so we can pass always by reference
            }

            // recursive function for finding field errors
            $fn_recursive(NULL, $name, $fields[$name], $sections);
        }

        return new TableValidationResponse(array(
            'values'    => self::$values,
            'tags'      => self::$tags,
            'errors'    => self::$errors,
            'valid'     => self::$valid,
            'submitted' => true,
            'expired'   => false
        ));
    }

    final static function message (string $keyword, array $params = []): string {
        $keyword = str_replace(' ', '', $keyword);

        // get custom message from DB (form table)
        $message = self::field('message_'.(static::TRANSLATOR)::get(), "error LIKE ?", array($keyword));

        if (!$message) {
            $languages = (static::TRANSLATOR)::LANGUAGES;
            $message   = "{{ Error: ". $keyword ." }}";

            self::insert(
                "error, vars, message_". implode(', message_', $languages),
                ':error, :vars'. str_repeat(', :message', count($languages)),
                array(
                    ':error'    => $keyword,
                    ':vars'     => count($params),
                    ':message'  => $message
                )
            );
        }

        foreach ($params as $i => $param) { // replace params with the certain values
            $message = str_replace('{$'. ($i+1) .'}', $param, $message);
        }

        return $message; // return the custom error
    }


    final protected static function value (string $field) {
        return (self::$values[$field] ?? NULL);
    }
    final protected static function error (string $field): ?string {
        return (self::$errors[$field] ?? NULL);
    }
    final protected static function valid (): bool {
        return self::$valid;
    }
    final protected static function invalid (): bool {
        return !self::$valid;
    }
}
