<?php

namespace ArshWell\Monolith\ENV;

use ArshWell\Monolith\Folder;
use ArshWell\Monolith\Cache;
use ArshWell\Monolith\Func;
use ArshWell\Monolith\ENV;
use ArshWell\Monolith\URL;

use Exception;

final class ENVComponent {
    private $path = NULL;
    private $json = NULL;
    private $site = NULL;
    private $root = NULL;

    function __construct (string $path = NULL) {
        if (!$path || $path[0] != '/') {
            $path = Folder::root() . $path; // default (our ArshWell project)
        }
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        $this->path = $path;

        Cache::setProject($path ?: Folder::root()); // where to look for caches

        if (!Cache::fetch('vendor/arshwell/monolith/env') || $this->sourceWasUpdated()) {
            $this->json = json_decode(file_get_contents("{$path}env.json"), true, 512, JSON_THROW_ON_ERROR);

            // merge env with env.dynamic
            if (is_file("{$path}env.dynamic.json")) {
                /**
                 * @example of env.dynamic structure:
                 *
                 *     "domain": {
                 *         "example.com": {
                 *             // ...env data you want to overwrite...
                 *         },
                 *
                 *         "test.example.org": {
                 *             // ...env data you want to overwrite...
                 *         }
                 *     },
                 *     "protocol|ip": {
                 *         "https|8.8.8.8": {
                 *             // ...env data you want to overwrite...
                 *         }
                 *     }
                 *
                 * @see env.dynamic.json file from the root of a project which uses ArshWell.
                 *
                 * Available parameters in env.dynamic 👇
                 *
                 * @todo: edit this array if you want to add new dynamic rules.
                 * @todo: don't remove rules because you'll affect some projects.
                 */
                $params = array(
                    'domain'    => ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']),
                    'protocol'  => URL::protocol(),
                    'ip'        => ENV::clientIP(),
                );

                $json_dynamic = json_decode(file_get_contents("{$path}env.dynamic.json"), true, 512, JSON_THROW_ON_ERROR);

                foreach ($json_dynamic as $rule => $envs) {
                    // a rule can have more conditions (param names) separated by pipeline: "|"
                    $conditions = str_replace(array_keys($params), array_values($params), explode('|', $rule), $count);

                    if (array_diff(explode('|', $rule), array_keys($params))) {
                        throw new Exception("|ArshWell| env.dynamic.json has invalid conditions at rule \"{$rule}\".");
                    }

                    foreach ($envs as $value => $env) {
                        // a value should have all condition values separated by pipeline: "|"
                        $values = explode('|', $value);

                        if (count($conditions) != count($values) || count($values) != $count) {
                            throw new Exception("|ArshWell| env.dynamic.json has invalid structure at rule \"{$rule}\", condition: \"{$value}\".");
                        }

                        // merge env for satisfied conditions
                        if ($conditions == $values) {
                            // NOTE: array_merge_recursive is not good
                            $this->json = array_replace_recursive(
                                $this->json,
                                $env
                            );
                        }
                    }
                }
            }

            array_walk_recursive($this->json['paths'], function (string &$folder = NULL): void {
                if ($folder) {
                    $folder = trim($folder, '/') . '/'; // having one, and only one, slash at the end
                }
            });

            foreach ($this->json as $key => $value) {
                if (is_array($value) && Func::isAssoc($value)) {
                    // NOTE: array_merge_recursive is not good
                    $this->json[$key] = array_replace_recursive(
                        $this->json[$key],
                        Func::arrayFlattenTree($value, NULL, '.', true)
                    );
                }
            }

            if (!empty($this->json['board']['supervisors'])) {
                // NOTE: array_merge_recursive is not good
                $this->json['board']['supervisors'] = array_replace_recursive(
                    $this->json['board']['supervisors'],
                    Func::arrayFlattenTree($this->json['board']['supervisors'], NULL, '.', true)
                );
            }
        }
        else {
            $this->json = Cache::fetch('vendor/arshwell/monolith/env');
        }

        $this->site = (strstr($this->json['URL'], '/', true) ?: $this->json['URL']);
        $this->root = (strstr($this->json['URL'], '/') ?: '');
    }

    function sourceWasUpdated (): bool {
        $env_mtime = filemtime("{$this->path}env.json");
        $env_dynamic_mtime = is_file("{$this->path}env.dynamic.json") ? filemtime("{$this->path}env.dynamic.json") : 0;

        return (
            max($env_mtime, $env_dynamic_mtime) >= Cache::filemtime('vendor/arshwell/monolith/env')
        );
    }

    function mergeWithEnvBuild (): bool {
        if (!is_file($this->path.'env.build.json')) {
            return false;
        }

        // NOTE: array_merge_recursive is not good
        $this->json = array_replace_recursive(
            $this->json,
            json_decode(file_get_contents($this->path.'env.build.json'), true, 512, JSON_THROW_ON_ERROR)
        );

        return true;
    }

    function cache (): void {
        // Changes you've made are cached.
        // NOTE: But env source stay the same.
        Cache::store('vendor/arshwell/monolith/env', $this->json);
    }

    function mergeSourceFileWithEnvBuild () {
        file_put_contents("{$this->path}env.json", json_encode(
            $this->json,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            // makes it readable | encodes characters correctly
        ));
        unlink("{$this->path}env.build.json");
    }


    function credits (): array {
        return $this->json['credits'];
    }

    function board (string $key) {
        return $this->json['board'][$key];
    }

    function url (): string {
        return $this->json['URL'];
    }

    function site (): string {
        return $this->site;
    }

    function root (): string {
        return $this->root;
    }

    function db (string $key) {
        return $this->json['db'][$key];
    }

    function mail (string $key) {
        return $this->json['mail'][$key];
    }

    function paths (): array {
        return $this->json['paths'];
    }

    function path (string $folder, bool $append_folder = true): string {
        try {
            return $this->json['paths'][$folder] . ($append_folder ? "$folder/" : '');
        }
        catch (Exception $e) {
            throw new Exception("|ArshWell| env.json should contain ['paths'][$folder] with string value. It represents the optional path to your $folder/ folder, or NULL for default path.");
        }
    }

    /**
     * @return string
     */
    function class (string $key): string {
        return $this->json['class'][$key];
    }
}
