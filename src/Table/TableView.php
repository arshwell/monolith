<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Tygh\Upload;
use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\Table;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\Func;
use Arsavinel\Arshwell\Web;
use Arsavinel\Arshwell\ENV;
use Arsavinel\Arshwell\SQL;
use Arsavinel\Arshwell\DB;
use Exception;

/*
 * Static methods, for getting (and auto-setting) View content from DB,
 * used for a better organization of the content in the site.
*/
abstract class TableView extends Table {
    const FILES = array(
        'value' => array(
            'quality' => 100, // %
        )
    );

    const TRANSLATED = array('value');

    // NOTE: Their order is given by creation over the time.
    // NOTE: Don't change their number value (never)!
    const TYPES = array(
        'sentence'      => 1,
        'text'          => 2,
        'content'       => 3,
        'sentenceSEO'   => 4,
        'image'         => 5,
        'images'        => 6,
        'checked'       => 7,  // created on 12/2020
        'video'         => 8,  // created on 01/2021
        'textSEO'       => 9,  // created on 04/2021
        'imageSEO'      => 10, // created on 04/2021
    );

    private static $source = array(
        'image' => "vendor/arsavinel/arshwell/resources/images/views/default-image-view.png",
        'video' => "vendor/arsavinel/arshwell/resources/images/views/default-video-view.mp4"
    );

    private static function source (bool &$global): string {
        if (ENV::isCRON() || Web::key() == NULL) { // if CRON or browser testing CRON
            if ($global == false) {
                $source = Folder::shorter(ENV::scriptfile()); // CRON file
                $global = true;
            }
            else {
                $source = '';
            }
        }
        else {
            if ($global == false) {
                $source = Web::key();
            }
            else {
                $source = '';

                foreach (debug_backtrace(0) as $trace) {
                    if (!empty($trace['class']) && !empty($trace['function'])
                    && (($trace['class'] == 'Arsavinel\Arshwell\Piece' && $trace['function'] == 'html')
                    || ($trace['class'] == 'Arsavinel\Arshwell\Mail' && in_array($trace['function'], ['send', 'html'])))) {
                        $source = strtolower(substr($trace['class'], strrpos($trace['class'], '\\') + 1)) .'s/'. $trace['args'][0];
                        break;
                    }
                }
            }
        }

        return $source;
    }

    final static function sentenceSEO (string $info, array $vars = NULL, string $route = NULL): string {
        $source = ($route ?: Web::key());

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value, vars',
                'where'     => "info = ? AND type = ? AND source = ?"
            ),
            array($info, self::TYPES['sentenceSEO'], $source)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, 0, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':info'     => $info,
                    ':type'     => self::TYPES['sentenceSEO'],
                    ':value'    => ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}'),
                    ':vars'     => ($vars ? count($vars) : 0)
                )
            );

            return ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}');
        }
        else if ($vars && $result['vars'] != count($vars)) {
            static::update(
                array(
                    'set'   => "vars = ?",
                    'where' => static::PRIMARY_KEY . " = ?"
                ),
                array(count($vars), $result[static::PRIMARY_KEY])
            );
        }

        if ($vars) {
            if (Func::isAssoc($vars)) {
                // replace assoc placeholders (ex: {$name})
                $result['value'] = str_replace(
                    array_map(function ($key) {
                        return "{\$".($key)."}";
                    }, array_keys($vars)),
                    $vars,
                    $result['value']
                );
            }

            // replace index placeholders (ex: {$1})
            $result['value'] = str_replace(
                array_map(function ($nr) {
                    return "{\$".($nr)."}";
                }, range(1, count($vars))),
                $vars,
                $result['value']
            );
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function textSEO (string $info, array $vars = NULL, string $route = NULL): string {
        $source = ($route ?: Web::key());

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value, vars',
                'where'     => "info = ? AND type = ? AND source = ?"
            ),
            array($info, self::TYPES['textSEO'], $source)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, 0, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':info'     => $info,
                    ':type'     => self::TYPES['textSEO'],
                    ':value'    => ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}'),
                    ':vars'     => ($vars ? count($vars) : 0)
                )
            );

            return ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}');
        }
        else if ($vars && $result['vars'] != count($vars)) {
            static::update(
                array(
                    'set'   => "vars = ?",
                    'where' => static::PRIMARY_KEY . " = ?"
                ),
                array(count($vars), $result[static::PRIMARY_KEY])
            );
        }

        if ($vars) {
            if (Func::isAssoc($vars)) {
                // replace assoc placeholders (ex: {$name})
                $result['value'] = str_replace(
                    array_map(function ($key) {
                        return "{\$".($key)."}";
                    }, array_keys($vars)),
                    $vars,
                    $result['value']
                );
            }

            // replace index placeholders (ex: {$1})
            $result['value'] = str_replace(
                array_map(function ($nr) {
                    return "{\$".($nr)."}";
                }, range(1, count($vars))),
                $vars,
                $result['value']
            );
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function imageSEO (string $info, int $width, int $height, string $route = NULL): string {
        ini_set('max_execution_time', ini_get('max_execution_time') + 2);

        $source = ($route ?: Web::key());

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY,
                'where'     => "info = ? AND type = ? AND source = ?"
            ),
            array($info, self::TYPES['imageSEO'], $source)
        );

        $language   = (static::TRANSLATOR)::GET();
        $site       = Web::site();

        if ($result) {
            Session::setView(static::class, $result[static::PRIMARY_KEY]);

            $urlpath = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/'. $language .'/'. $width.'x'.$height;

            $file = File::first(ENV::uploads('files'). $urlpath);

            if ($file) {
                return ($site . ENV::uploads('files') . $urlpath . '/'. basename($file));
            }

            // If no file found, will be created below. Because imageSEO is not optional (any page should have imageSEO).
        }
        else {
            $result[static::PRIMARY_KEY] = DB::insert(
                static::class,
                "source, info, type, `order`",
                "?, ?, ?, ". SQL::nextID(static::TABLE),
                array($source, $info, self::TYPES['imageSEO'])
            );
        }

        $image_folder = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/';

        foreach ((static::TRANSLATOR)::LANGUAGES as $lang) {
            // if this language doesn't have the file
            if (File::first(ENV::uploads('files'). $image_folder . $lang .'/'. $width.'x'.$height) == NULL) {
                $image = (
                    File::findBiggestSibling(ENV::uploads('files'). $image_folder . $lang .'/'. $width.'x'.$height.'/foo.bar')
                    ?:
                    self::$source['image']
                );

                $basename   = basename($image);
                $image_name = File::name($basename); // getting name from sibling file

                $resizer = new Upload($image);

                if (!$resizer->uploaded) {
                    throw new Exception($resizer->error);
                }

                $resizer->file_new_name_body        = $image_name;
                $resizer->file_overwrite            = true;
                $resizer->file_name_body_lowercase  = true;
                $resizer->jpeg_quality              = self::FILES['value']['quality'];

                $resizer->file_safe_name    = false;
                $resizer->image_resize      = true;

                $resizer->image_x            = $width;
                $resizer->image_y            = $height;
                $resizer->image_ratio_crop   = true;

                $resizer->process(ENV::uploads('files'). $image_folder . $lang .'/'. $width .'x'. $height .'/');

                // remember url file, for current language
                if ($lang == $language) {
                    $file = ($site .ENV::uploads('files'). $image_folder . $language .'/'. $width .'x'. $height .'/'. $basename);
                }
            }
        }

        return $file;
    }

    final static function sentence (string $info, array $vars = NULL, bool $global = false): string {
        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value, vars',
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['sentence'], $source, (int)$global)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, :global, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':global'   => (int)$global,
                    ':info'     => $info,
                    ':type'     => self::TYPES['sentence'],
                    ':value'    => ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}'),
                    ':vars'     => ($vars ? count($vars) : 0)
                )
            );

            return ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}');
        }
        else if ($vars && $result['vars'] != count($vars)) {
            static::update(
                array(
                    'set'   => "vars = ?",
                    'where' => static::PRIMARY_KEY . " = ?"
                ),
                array(count($vars), $result[static::PRIMARY_KEY])
            );
        }

        if ($vars) {
            if (Func::isAssoc($vars)) {
                // replace assoc placeholders (ex: {$name})
                $result['value'] = str_replace(
                    array_map(function ($key) {
                        return "{\$".($key)."}";
                    }, array_keys($vars)),
                    $vars,
                    $result['value']
                );
            }

            // replace index placeholders (ex: {$1})
            $result['value'] = str_replace(
                array_map(function ($nr) {
                    return "{\$".($nr)."}";
                }, range(1, count($vars))),
                $vars,
                $result['value']
            );
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function text (string $info, array $vars = NULL, bool $global = false): string {
        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value, vars',
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['text'], $source, (int)$global)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, :global, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':global'   => (int)$global,
                    ':info'     => $info,
                    ':type'     => self::TYPES['text'],
                    ':value'    => ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}'),
                    ':vars'     => ($vars ? count($vars) : 0)
                )
            );

            return ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}');
        }
        else if ($vars && $result['vars'] != count($vars)) {
            static::update(
                array(
                    'set'   => "vars = ?",
                    'where' => static::PRIMARY_KEY . " = ?"
                ),
                array(count($vars), $result[static::PRIMARY_KEY])
            );
        }

        if ($vars) {
            if (Func::isAssoc($vars)) {
                // replace assoc placeholders (ex: {$name})
                $result['value'] = str_replace(
                    array_map(function ($key) {
                        return "{\$".($key)."}";
                    }, array_keys($vars)),
                    $vars,
                    $result['value']
                );
            }

            // replace index placeholders (ex: {$1})
            $result['value'] = str_replace(
                array_map(function ($nr) {
                    return "{\$".($nr)."}";
                }, range(1, count($vars))),
                $vars,
                $result['value']
            );
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function content (string $info, array $vars = NULL, bool $global = false): ?string {
        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value, vars',
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['content'], $source, (int)$global)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, :global, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':global'   => (int)$global,
                    ':info'     => $info,
                    ':type'     => self::TYPES['content'],
                    ':value'    => ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}'),
                    ':vars'     => ($vars ? count($vars) : 0)
                )
            );

            return ($vars ? '{{ '.$info.' ['.count($vars).'] }}' : '{{ '.$info.' }}');
        }
        else if ($vars && $result['vars'] != count($vars)) {
            static::update(
                array(
                    'set'   => "vars = ?",
                    'where' => static::PRIMARY_KEY . " = ?"
                ),
                array(count($vars), $result[static::PRIMARY_KEY])
            );
        }

        if ($vars) {
            if (Func::isAssoc($vars)) {
                // replace assoc placeholders (ex: {$name})
                $result['value'] = str_replace(
                    array_map(function ($key) {
                        return "{\$".($key)."}";
                    }, array_keys($vars)),
                    $vars,
                    $result['value']
                );
            }

            // replace index placeholders (ex: {$1})
            $result['value'] = str_replace(
                array_map(function ($nr) {
                    return "{\$".($nr)."}";
                }, range(1, count($vars))),
                $vars,
                $result['value']
            );
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function image (string $info, int $width, int $height, bool $global = false): ?string {
        ini_set('max_execution_time', ini_get('max_execution_time') + 2);

        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY,
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['image'], $source, (int)$global)
        );

        $language   = (static::TRANSLATOR)::GET();
        $site       = Web::site();

        if ($result) {
            Session::setView(static::class, $result[static::PRIMARY_KEY]);

            $urlpath = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/'. $language .'/'. $width.'x'.$height;

            $file = File::first(ENV::uploads('files'). $urlpath);

            if ($file) {
                return $site .ENV::uploads('files'). $urlpath .'/'. basename($file);
            }

            $dirpath = ENV::uploads('files') . $urlpath .'/'. dirname($file);

            /**
             * Creating its directory no matter what.
             *
             * We need it for TableView files, so TableFiles classes can know the required filesizes.
             */
            if (!is_dir($dirpath)) {
                mkdir($dirpath, 0755, true);
            }

            return NULL; // If no file found, NULL returned. Because image is optional.
        }
        else {
            $result[static::PRIMARY_KEY] = DB::insert(
                static::class,
                "source, global, info, type, `order`",
                "?, ?, ?, ?, ". SQL::nextID(static::TABLE),
                array($source, (int)$global, $info, self::TYPES['image'])
            );
        }

        $image_folder = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/';

        foreach ((static::TRANSLATOR)::LANGUAGES as $lang) {
            // getting name from sibling file
            $image = (
                File::findBiggestSibling(ENV::uploads('files'). $image_folder . $lang .'/'. $width.'x'.$height.'/foo.bar')
                ?:
                self::$source['image']
            );

            $basename   = basename($image);
            $image_name = File::name($basename);

            $resizer = new Upload($image);

            if (!$resizer->uploaded) {
                throw new Exception($resizer->error);
            }

            $resizer->file_new_name_body        = $image_name;
            $resizer->file_overwrite            = true;
            $resizer->file_name_body_lowercase  = true;
            $resizer->jpeg_quality              = self::FILES['value']['quality'];

            $resizer->file_safe_name    = false;
            $resizer->image_resize      = true;

            $resizer->image_x            = $width;
            $resizer->image_y            = $height;
            $resizer->image_ratio_crop   = true;

            $resizer->process(ENV::uploads('files'). $image_folder . $lang .'/'. $width .'x'. $height .'/');

            // remember url file, for current language
            if ($lang == $language) {
                $file = ($site .ENV::uploads('files'). $image_folder . $language .'/'. $width .'x'. $height .'/'. $basename);
            }
        }

        return $file;
    }

    final static function images (string $info, int $width, int $height, bool $global = false): array {
        ini_set('max_execution_time', ini_get('max_execution_time') + 2);

        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY,
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['images'], $source, (int)$global)
        );

        $encoded_class  = Folder::encode(static::class);
        $language       = (static::TRANSLATOR)::GET();
        $site           = Web::site();

        if ($result) {
            Session::setView(static::class, $result[static::PRIMARY_KEY]);

            $urlpath = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/'. $language .'/'.$width .'x'. $height;

            $files = array_map(function ($file) use ($site, $urlpath) {
                return ($site .ENV::uploads('files'). $urlpath .'/'. basename($file));
            }, File::folder(ENV::uploads('files'). $urlpath));

            return $files;
        }
        else {
            $result[static::PRIMARY_KEY] = DB::insert(
                static::class,
                "source, global, info, type, `order`",
                "?, ?, ?, ?, ". SQL::nextID(static::TABLE),
                array($source, (int)$global, $info, self::TYPES['images'])
            );
        }

        $image_folder   = $encoded_class .'/'. $result[static::PRIMARY_KEY] .'/value/';
        $results        = array();

        foreach ((static::TRANSLATOR)::LANGUAGES as $lang) {
            unset($max); // because $max is used many times in this foreach
            foreach (Folder::children(ENV::uploads('files'). $image_folder . $lang, true) as $size) {
                list($w, $h) = explode('x', $size);
                $value = ($w*$h);

                if (($max ?? $value) >= $value) {
                    $max = $value;
                    $biggest = $size;
                }
            }

            $images = (isset($biggest) ?
                File::folder(ENV::uploads('files'). $image_folder . $lang .'/'. $biggest)
                :
                array(self::$source['image'])
            );

            foreach ($images as $image) {
                $basename   = basename($image);
                $image_name = File::name($basename);

                $resizer = new Upload($image);

                if (!$resizer->uploaded) {
                    throw new Exception($resizer->error);
                }

                $resizer->file_new_name_body        = $image_name;
                $resizer->file_overwrite            = true;
                $resizer->file_name_body_lowercase  = true;
                $resizer->jpeg_quality              = self::FILES['value']['quality'];

                $resizer->file_safe_name    = false;
                $resizer->image_resize      = true;

                $resizer->image_x            = $width;
                $resizer->image_y            = $height;
                $resizer->image_ratio_crop   = true;

                $resizer->process(ENV::uploads('files'). $image_folder . $lang .'/'. $width .'x'. $height .'/');

                if ($lang == $language) {
                    $results[] = ($site .ENV::uploads('files'). $image_folder . $lang .'/'. $width .'x'. $height .'/'. $basename);
                }
            }
        }

        return $results;
    }

    final static function checked (string $info, bool $global = false): int {
        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY . ', value:lg as value',
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['checked'], $source, (int)$global)
        );

        if (!$result) {
            DB::insert(
                static::class,
                "source, global, info, type, value:lg, vars, `order`",
                ":source, :global, :info, :type, ". implode(', ', array_fill(0, count((static::TRANSLATOR)::LANGUAGES), ':value')) .", :vars, ". SQL::nextID(static::TABLE),
                array(
                    ':lg'       => (static::TRANSLATOR)::LANGUAGES,
                    ':source'   => $source,
                    ':global'   => (int)$global,
                    ':info'     => $info,
                    ':type'     => self::TYPES['checked'],
                    ':value'    => 0, // false
                    ':vars'     => 0
                )
            );

            return 0;
        }

        Session::setView(static::class, $result[static::PRIMARY_KEY]);

        return $result['value'];
    }

    final static function video (string $info, bool $global = false): string {
        ini_set('max_execution_time', ini_get('max_execution_time') + 2);

        $source = self::source($global);

        $result = DB::first(
            array(
                'class'     => static::class,
                'columns'   => static::PRIMARY_KEY,
                'where'     => "info = ? AND type = ? AND source = ? AND global = ?"
            ),
            array($info, self::TYPES['video'], $source, (int)$global)
        );

        $language   = (static::TRANSLATOR)::GET();
        $site       = Web::site();

        if ($result) {
            Session::setView(static::class, $result[static::PRIMARY_KEY]);

            $urlpath = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/'. $language;

            $file = File::first(ENV::uploads('files'). $urlpath);

            if ($file) {
                return ($site . ENV::uploads('files') . $urlpath . '/'. basename($file));
            }
        }
        else {
            $result[static::PRIMARY_KEY] = DB::insert(
                static::class,
                "source, global, info, type, `order`",
                "?, ?, ?, ?, ". SQL::nextID(static::TABLE),
                array($source, (int)$global, $info, self::TYPES['video'])
            );
        }

        $image_folder = Folder::encode(static::class) .'/'. $result[static::PRIMARY_KEY] .'/value/';

        foreach ((static::TRANSLATOR)::LANGUAGES as $lang) {
            $basename = basename(self::$source['image']);

            $dirpath = ENV::uploads('files') . $image_folder . $language; // could be outside of project
            $urlpath = ENV::uploads('files'). $image_folder . $language;

            if (!is_dir($dirpath)) {
                mkdir($dirpath, 0755, true);
            }
            copy(self::$source['video'], $dirpath .'/'. $basename);

            if ($lang == $language) {
                $file = ($site . $urlpath .'/'. $basename);
            }
        }

        return $file;
    }
}
