<?php

namespace Arsavinel\Arshwell;

use Arsavinel\Arshwell\Cache;

/**
 * Class for getting git information. Like commits and tags.

 * @package https://github.com/arsavinel/ArshWell
*/
class Git {
    private static $tag = NULL;

    final static function inform () {
        // if is a child of this class
        if (get_parent_class(static::class) != false) {
            $dir_path = (defined('static::DIR_PATH') ? static::DIR_PATH : ''); // default: project's root
            $cache_name = static::CACHE_NAME;
        }
        else {
            $dir_path = 'vendor/arsavinel/arshwell/';
            $cache_name = 'vendor/arsavinel/arshwell/git';
        }

        $git = Cache::fetch($cache_name);

        if (!$git) {
            $git = array();

            if (is_dir("$dir_path.git")) {
                if (function_exists('exec')) {
                    $git['tag'] = exec('git tag');
                }

                if (is_dir("$dir_path.git/refs/tags")) {
                    $tags = Folder::children("$dir_path.git/refs/tags", true);

                    if ($tags) {
                        usort($tags, 'version_compare');

                        $git['tag'] = $tags[array_key_last($tags)];
                    }
                }
            }

            if (empty($git['tag'])) {
                $git['tag'] = 'v1.0.0'; // default
            }

            Cache::store($cache_name, $git, 60 * 60 * 24); // 24 hours
        }

        self::$tag = $git['tag'];
    }

    final static function __callStatic (string $name, array $arguments) {
        return self::${$name};
    }
}
