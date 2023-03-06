<?php

namespace ArshWell\Monolith;

use ArshWell\Monolith\File;

/**
 * Class for manipulating folders.
 *
 * It has routine functions.

 * @package https://github.com/arshwell/monolith
*/
final class Folder {
    const MODE = 0755;
    private static $offset = NULL;

    static function children (string $folder, bool $basename = false): array {
        // $folder = Folder::realpath($folder);

        if (is_dir($folder)) {
            if (substr($folder, -1) != '/') {
                $folder .= '/';
            }

            return array_map(
                function ($path) use ($folder, $basename): string {
                    // return ($basename ? $path : Folder::shorter($folder).$path);
                    return ($basename ? $path : $folder.$path);
                },
                array_filter(scandir($folder), function ($item) use ($folder): bool {
                    return ($item != '.' && $item != '..' && is_dir($folder.$item));
                })
            );
        }

        return array();
    }

    static function all (string $folder, bool $basename = false): array {
        $folders = array();

        foreach (glob(rtrim($folder, '/').'/*', GLOB_ONLYDIR) as $path) {
            $folders[] = ($basename ? basename($path) : $path);
            $folders = array_merge($folders, self::all($path, $basename));
        }

        return $folders;
    }

    static function copy (string $source, string $destination): bool {
        // $source = Folder::realpath($source);
        // $destination = Folder::realpath($destination);

        if (is_dir($source)) {
            $copied = false;
            if (!is_dir($destination)) {
                mkdir($destination, self::MODE, true);
            }

            foreach (scandir($source) as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($source .'/'. $file)) {
                        if (self::copy($source .'/'. $file, $destination .'/'. $file)) {
                            $copied = true;
                        }
                    }
                    else if (filesize($source .'/'. $file) < 5242880) { // 5242880 = 5MB
                        if (copy($source .'/'. $file, $destination .'/'. $file)) {
                            $copied = true;
                        }
                    }
                    else if (File::copy($source .'/'. $file, $destination .'/'. $file)) {
                        $copied = true;
                    }
                }
            }
            return $copied;
        }
        return false;
    }

    static function remove (string $dir, bool $rmdir = true): int {
        $removed = 0;

        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dir .'/'. $file)) {
                        $removed += self::remove($dir .'/'. $file, $rmdir);
                    }
                    else if (unlink($dir .'/'. $file)) {
                        $removed++;
                    }
                }
            }

            if ($rmdir) {
                rmdir($dir);
            }
        }

        return $removed;
    }

    static function removeEmpty (string $dir): bool {
        $empty = true;
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file != '.' && $file != '..' && (is_file($dir.'/'.$file) || !self::removeEmpty($dir.'/'.$file))) {
                    $empty = false;
                }
            }
            if ($empty) {
                rmdir($dir);
            }
        }
        return $empty;
    }

    static function move (string $src, string $dest): bool {
        if (is_dir($src)) {
            self::copy($src, $dest);
            return self::remove($src);
        }
        return false;
    }

    static function size (string $folder): int {
        $size = 0;

        foreach (scandir($folder) as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($folder .'/'. $file)) {
                    $size += self::size($folder .'/'. $file);
                }
                else {
                    $size += filesize($folder .'/'. $file);
                }
            }
        }

        return $size;
    }

    /**
     * (string|array) $input
    */
    static function mTime ($input, array $exceptions = array()): int {
        $times = array(0); // max() needs at least one value

        $folders = (is_array($input) ? $input : array($input));

        foreach ($folders as $folder) {
            // We need realpath because also crons can use this class
            $folder = realpath(self::root().$folder);

            foreach (scandir($folder) as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($folder .'/'. $file)) {
                        if (!in_array($folder .'/'. $file, $exceptions)) {
                            $times[] = self::mTime($folder .'/'. $file, $exceptions);
                        }
                    }
                    else {
                        $times[] = filemtime($folder .'/'. $file);
                    }
                }
            }
        }

        return max($times);
    }

    // Get TABLE FILE path - from CLASS NAMESPACE
    static function encode (string $class): string {
        return strtolower(preg_replace('/([A-Z])/', '.$1', str_replace('\\', '/', $class)));
    }

    // Get CLASS NAMESPACE - from TABLE FILE path
    static function decode (string $path): string {
        return str_replace(['.','/'], ['','\\'], lcfirst(ucwords($path, '.')));
    }

    static function root (): string {
        // NOTE: returns the path of the project
        return substr(__DIR__, 0, -1 * strlen("vendor/arshwell/monolith/src"));
    }

    static function realpath (string $path): string {
        if ($path[0] != '/') {
            $path = self::root().$path;
        }

        $count = 0;
        do {
            // removes ../
            $path = preg_replace('~(?<![^/])(?!\.\.(?![^/]))[^/]+/\.\.(?:/|$)~', '', $path, -1, $count);
        } while ($count > 0);

        return $path;
    }

    static function shorter (string $path): string {
        if ($path[0] == '/' && strpos($path, sys_get_temp_dir()) !== 0) {
            $path = substr($path, strlen(__DIR__) - strlen("vendor/arshwell/monolith/src")); // remove root
        }
        return $path;
    }
}
