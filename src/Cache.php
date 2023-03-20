<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Exception;

final class Cache {
    static private $project = NULL; // Arshwell project
    static private $folder = 'caches/';

    static function project (): ?string {
        return self::$project;
    }

    static function setProject (string $project): void {
        if (substr($project, -1) != '/') {
            $project .= '/';
        }
        self::$project = Folder::realpath($project);
    }

    // This is the function which stores information with
    static function store (string $key, array $data, int $time_to_live = 0): void {
        $file = self::file($key);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        // Opening the file in write mode
        if (!($handle = fopen($file, 'w'))) {
            throw new Exception('Could not write to cache (fopen)');
        }

        flock($handle, LOCK_EX); // exclusive lock, will get released when the file is closed

        // Serializing along with the TTL
        $data = json_encode(array(
            ($time_to_live ? time() + $time_to_live : 0),
            $data
        ));
        if (fwrite($handle, $data) === false) {
            throw new Exception('Could not write to cache (fwrite)');
        }
        fclose($handle);
    }

    // The function to fetch data returns NULL on failure
    static function fetch (string $key, string $project = NULL): ?array {
        $file = self::file($key);

        if (!is_file($file) || filesize($file) == 0) {
            return NULL;
        }

        $handle = fopen($file, 'r');

        if (!$handle) {
            return NULL;
        }

        $data = json_decode(fread($handle, filesize($file)), true);

        fclose($handle);

        if ($data && ($data[0] == 0 || time() > $data[0])) { // file has data and is not 'expired'
            return $data[1];
        }
        return NULL;
    }

    static function delete (string $key = NULL): bool {
        if ($key) {
            $file = self::file($key);

            return (is_file($file) && unlink($file));
        }
        else {
            foreach (File::rFolder(Folder::root() . self::$folder, ['json']) as $file) {
                if (unlink($file) == false) {
                    return false;
                }
            }

            return true;
        }
    }

    static function file (string $key, bool $basename = false): string {
        if ($basename) {
            return trim(str_replace(' ', '-', $key));
        }

        return (self::$project . self::$folder . trim(str_replace(' ', '-', $key)) .'.json');
    }

    static function filemtime (string $key): int {
        return filemtime(self::file($key));
    }

    static function files (string $dir = NULL): array {
        return File::rFolder(self::$project . self::$folder . $dir);
    }
}
