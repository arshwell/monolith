<?php

namespace Arsh\Core;

use Arsh\Core\Language;
use Arsh\Core\Session;
use Arsh\Core\Cache;
use Arsh\Core\Text;
use Arsh\Core\Func;
use Arsh\Core\File;
use Arsh\Core\ENV;
use Arsh\Core\URL;

/**
 * Routing Management Class.
 * Verifies url and stores stuff about it.

 * @package App/Kernel
 * @author Tănăsescu Valentin <valentin_tanasescu.2000@yahoo.com>
*/
final class Web {
    const WRNNG_NONE = 0;
    const WRNNG_URL_PAGINATION_BACKUP = 1;
    const WRNNG_URL_PAGINATION_NEGATIVE = 2;
    const WRNNG_URL_PATH_BACKUP = 3;
    const WRNNG_URL_PATH_END_SLASH_NO_MATCH = 4;

    private static $web = NULL; // our web object

    /** ↓ Filled by Web::fetch(). *********************************************/
        private static $keys_by_folder      = NULL;
        private static $routes              = NULL;
        private static $routes_by_request   = NULL;
        private static $groups              = NULL;

        private static $protocol = NULL;
        private static $site = NULL;
    /** ↑ We get error if do anything before. *********************************/

    /** route:
        * 0 -> folder path
        * 1 -> array with requests
        * 2 -> array with urls
        * 3 -> array with paginations
        * 4 -> array with the regexs
            * 5 -> array with regexs for getting url params
            * 6 -> array with regexs for getting page number
            * 7 -> array with regexs for getting first page from the pagination list
    */

    static function fetch (): string {
        self::$protocol = ENV::isCRON() ? 'https' : URL::protocol();
        self::$site     = ENV::url();

        if (!is_file(Cache::file('ArshWell/forks')) || Folder::mTime('forks') >= Cache::filemtime('ArshWell/forks')) {
            self::$routes_by_request = array();

            $getforks = function (string $dir) use (&$getforks) {
                $jsons = array();

                foreach (scandir($dir) as $file) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($dir .'/'. $file)) {
                            $jsons = array_replace($jsons, $getforks($dir .'/'. $file));
                        }
                        else {
                            $jsons[str_replace(
                                '/', '.',
                                preg_replace(
                                    "~forks/(.*).json~",
                                    "$1",
                                    preg_replace(
                                        "~(/)(\d+\.)(?!(/|json$))~",
                                        "$1$3",
                                        $dir .'/'. $file
                                    )
                                )
                            )] = json_decode(file_get_contents($dir .'/'. $file), true);
                        }
                    }
                }

                return $jsons;
            };

            $getpagination = function (array $languages, $pagination): array {
                $links = array();

                // if we have only a string, we duplicate it for all languages
                if (is_string($pagination)) {
                    foreach ($languages as $language) {
                        $links[$language] = array($pagination);
                    }
                }
                // if we have only a list of links we duplicate it for all languages
                else if (array_keys($pagination) === range(0, count($pagination) - 1)) {
                    foreach ($languages as $language) {
                        $links[$language] = (is_string($pagination) ? array($pagination) : $pagination);
                    }
                }
                // if we have links for all languages, we just beautify them
                else {
                    $links = array_combine($languages, array_keys($pagination));
                    foreach ($languages as $language) {
                        $links[$language] = (is_string($pagination[$language]) ? array($pagination[$language]) : $pagination[$language]);
                    }
                }
                return $links;
            };

            $getenvlangs = function (array $utils) use (&$getenvlangs, &$getpagination): array {
                $array = array();

                foreach ($utils as $route => $class) {
                    if (is_array($class)) {
                        foreach ($getenvlangs($class) as $r => $info) {
                            $array[$route.'.'.$r] = $info;
                        }
                    }
                    else {
                        $array[$route] = array(
                            'class'         => $class,
                            'languages'     => ($class)::LANGUAGES,
                            'pagination'    => $getpagination(($class)::LANGUAGES, ($class)::PAGINATION)
                        );
                    }
                }

                return $array;
            };

            $envlangs = $getenvlangs(ENV::translations());

            $assoc = function (string $group = NULL, array $routes, $folders) use (&$assoc, &$envlangs) {
                foreach ($routes as $name => $route) {
                    $folders[$group.$name] = ($group ? ($folders[substr($group, 0, -1)] .'/'. $name) : $name);

                    if (is_array(array_values($route)[0])) {
                        $assoc($group.$name.'.', $route, $folders);
                    }
                    else {
                        $name = $group.$name;

                        $languages = ($envlangs[$name]['languages'] ?? NULL); // setting route languages

                        // filling self::$groups with subgroups
                        if (!empty($group)) {
                            $help = substr($group, 0, strrpos($group, '.'));
                            while ($help) {
                                if (!$languages && isset($envlangs[$help])) { // setting route languages
                                    $languages = $envlangs[$help]['languages'];
                                }

                                self::$groups[$help][] = $name;
                                $help = substr($help, 0, strrpos($help, '.'));
                            }
                        }

                        if (empty($languages)) {
                            $languages = Language::LANGUAGES; // setting route languages
                        }

                        // Adding route folder in array.
                        array_unshift($route, $folders[$name]);

                        $route[1] = explode('|', $route[1]);

                        // if we have only a string, we duplicate it for all languages
                        if (is_string($route[2])) {
                            $links = array_fill_keys($languages, array($route[2]));
                        }
                        // if we have only a list of links, we duplicate it for all languages
                        else if (Func::isAssoc($route[2]) == false) {
                            $links = array_fill_keys($languages, $route[2]);
                        }
                        // if we have links for all languages, we just beautify them
                        else {
                            $links = array();
                            foreach ($route[2] as $lg => $input) {
                                // saving for langs site is using for now
                                if (in_array($lg, $languages)) {
                                    $links[$lg] = (array)$input;
                                }
                            }
                        }
                        $route[2] = $links;

                        self::$routes[$name] = $route;
                    }
                }
            };

            $assoc(NULL, $getforks('forks'), array());

            // after creating routes list, we add, in them, paginations and regex
            foreach (self::$routes as $name => $route) {
                foreach ($envlangs as $group => $utils) {
                    if ($name == $group ||
                    (isset(self::$groups[$group]) && in_array($name, self::$groups[$group]))) {
                        self::$routes[$name][3] = $envlangs[$group]['pagination'];
                        self::$routes[$name][5] = $envlangs[$group]['class'];
                        break;
                    }
                }
                if (!isset(self::$routes[$name][3])) { // if doesn't have a webutil defined
                    self::$routes[$name][3] = $getpagination(Language::LANGUAGES, Language::PAGINATION);
                    self::$routes[$name][5] = Language::class;
                }

                foreach ($route[2] as $language => $urls) {
                    foreach ($urls as $url) {
                        if ($url && trim($url)[0] != '/')
                            $url = ('/'.$url);

                        // [page]
                        $url = str_replace("/[page]", '(/(?:'. implode('|', self::$routes[$name][3][$language]) .'))?', $url);
                        $url = str_replace("[page]", '(?:'. implode('|', self::$routes[$name][3][$language]) .')?', $url);

                        // [lg]
                        $url = preg_replace("~(/|)\[lg\]~",                         "($1$language)",              $url);

                        // [name:i] integer
                        $url = preg_replace("~(/|)\[([a-z][a-z0-9]+):i\]~",         "($1(?<$2>[0-9]+))",        $url);

                        // [name:f] float
                        $url = preg_replace("~(/|)\[([a-z][a-z0-9]+):f\]~",         "($1(?<$2>\d+(|\.\d+)))",   $url);

                        // [name:*] words
                        $url = preg_replace("~(/|)\[([a-z][a-z0-9]+):\*\]~",        "($1(?<$2>[^/]+))",         $url);

                        // [name:->] text
                        $url = preg_replace("~(/|)\[([a-z][a-z0-9]+):\-\>\]~",      "($1(?<$2>.+))",            $url);

                        // [name:(input)]
                        $url = preg_replace("~(/|)\[([a-z][a-z0-9]+):\((.*?)\)\]~", "($1(?<$2>($3)))",          $url);

                        $url .= (substr($url, -1) != '/' ? '/?' : '?');

                        self::$routes[$name][4][5][$language][] = ('~^'. $url .'$~J');
                    }
                }

                if (strpos(var_export($route[2], true), '[page]') !== false) {
                    foreach ($route[2] as $language => $urls) {
                        foreach ($urls as $url) {
                            if ($url && trim($url)[0] != '/') {
                                $url = ('/'.$url);
                            }

                            /* self::$routes[$name][4][6][$language] */
                                // [page]
                                $regex = str_replace("/[page]", '(/(?:'. str_replace('(', '(?<page>', implode('|', self::$routes[$name][3][$language])) .'))?', $url);
                                $regex = str_replace("[page]", '(?:'. str_replace('(', '(?<page>', implode('|', self::$routes[$name][3][$language])) .')?', $regex);

                                // [lg]
                                $regex = preg_replace("~(/|)\[lg\]~",                         "($1$language)",      $regex);

                                // [name:i] integer
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):i\]~",         "($1[0-9]+)",       $regex);

                                // [name:f] float
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):f\]~",         "($1\d+(|\.\d+))",  $regex);

                                // [name:*] words
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\*\]~",        "($1[^/]+)",        $regex);

                                // [name:->] text
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\-\>\]~",      "($1.+)",           $regex);

                                // [name:(input)]
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\((.*?)\)\]~", "($1($3))",         $regex);

                                $regex .= (substr($url, -1) != '/' ? '/?' : '?');

                                self::$routes[$name][4][6][$language][] = ('~^'. $regex .'$~J');
                            /* -------------------------------- */

                            /* self::$routes[4][7][$language] */
                                // [page]
                                $regex = str_replace("/[page]", '(/(?<page>' . self::$routes[$name][3][$language][0] .'))?',    $url);
                                $regex = str_replace("[page]", '(?<page>' . self::$routes[$name][3][$language][0] .')?',        $regex);

                                // [lg]
                                $regex = preg_replace("~(/|)\[lg\]~",                         "($1$language)",      $regex);

                                // [name:i] integer
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):i\]~",         "($1[0-9]+)",       $regex);

                                // [name:f] float
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):f\]~",         "($1\d+(|\.\d+))",  $regex);

                                // [name:*] words
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\*\]~",        "($1[^/]+)",        $regex);

                                // [name:->] text
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\-\>\]~",      "($1.+)",           $regex);

                                // [name:(input)]
                                $regex = preg_replace("~(/|)\[([a-z][a-z0-9]+):\((.*?)\)\]~", "($1($3))",         $regex);

                                $regex .= (substr($url, -1) != '/' ? '/?' : '?');

                                self::$routes[$name][4][7][$language][] = ('~^'. $regex .'$~J');
                            /* -------------------------------- */
                        }
                    }
                }

                // filling self::$routes_by_request
                foreach ($route[1] as $request) {
                    self::$routes_by_request[$request][$name] = &self::$routes[$name];
                }

                self::$keys_by_folder[$route[0]] = $name;
            }

            self::$routes_by_request['AJAX'] = array_merge(
                self::$routes_by_request['AJAX'],
                self::$routes_by_request['GET']
            );

            Cache::store('ArshWell/forks', array(
                self::$keys_by_folder,
                self::$routes,
                self::$routes_by_request,
                self::$groups
            ));
        }
        else {
            list(self::$keys_by_folder, self::$routes, self::$routes_by_request, self::$groups) = Cache::fetch('ArshWell/forks');
        }

        return static::class;
    }

    // returns bool | object
    static function prepare (string $url_path, string $method, bool $return = true) {
        self::$protocol = URL::protocol();
        self::$site     = ENV::url();

        if ($method == 'HEAD') {
            $method = 'GET';
        }

        $object = new class ($url_path, $method) {
            private $protocol = NULL;
            private $url_path = NULL;
            private $site     = NULL;
            private $params   = NULL;
            private $page     = NULL;
            private $language = NULL;

            private $request = NULL;

            private $key     = NULL;
            private $route   = NULL;

            private $prepared = NULL;
            private $warnings = array();

            function __construct (string $url_path, string $method) {
                // path
                $this->url_path = $url_path;

                // decide what request we received
                if ($method == 'POST') {
                    if (isset($_POST['ajax_token']) && strlen($_POST['ajax_token']) == 32) {
                        $this->request = 'AJAX';
                    }
                    else {
                        $this->request = 'POST';
                    }
                }
                else {
                    $this->request = $method;
                }

                $this->params   = array();
                $this->page     = 0;

                foreach (Web::routes($this->request) as $key => $route) {
                    foreach (array_keys($route[4][5]) as $language) {
                        for ($nth = 0; $nth < count($route[4][5][$language]); $nth++) {
                            if (preg_match($route[4][5][$language][$nth], $this->url_path, $params, PREG_UNMATCHED_AS_NULL) && (in_array($this->request, $route[1]) || ($this->request == 'AJAX' && in_array('GET', $route[1])))) {

                                // remove params junk
                                foreach ($params as $i => $match) {
                                    if (is_int($i)) {
                                        unset($params[$i]);
                                    }
                                }

                                // if url has pagination
                                if (isset($route[4][6])) {
                                    preg_match($route[4][6][$language][$nth], $this->url_path, $page, PREG_UNMATCHED_AS_NULL);

                                    if (!isset($page['page'])) { // page number
                                        $this->page = 1;
                                    }
                                    else if ($page['page'] > 1) {
                                        $this->page = $page['page'];

                                        if (!preg_match($route[4][7][$language][$nth], $this->url_path)) {
                                            $this->warnings[] = Web::WRNNG_URL_PAGINATION_BACKUP;
                                        }
                                    }
                                    else {
                                        $this->warnings[] = Web::WRNNG_URL_PAGINATION_NEGATIVE;
                                    }
                                }

                                // if is not first url from route
                                if ($nth > 0) {
                                    $this->warnings[] = Web::WRNNG_URL_PATH_BACKUP;
                                }

                                // if url doesn't end properly
                                if ((substr($route[2][$language][$nth], -1) == '/') != (substr($this->url_path, -1) == '/')) {
                                    $this->warnings[] = Web::WRNNG_URL_PATH_END_SLASH_NO_MATCH;
                                }

                                $this->language = $language;
                                $this->params   = $params;
                                $this->route    = $route;
                                $this->key      = $key;

                                $this->prepared = true;

                                if (empty($this->warnings)) {
                                    $this->warnings = array(Web::WRNNG_NONE);
                                }

                                return;
                            }
                        }
                    }
                }

                $this->prepared = false;
            }

            function prepared (): bool {
                return $this->prepared;
            }

            function warnings (): array {
                return $this->warnings;
            }

            function warning (int $warning): bool {
                return in_array($warning, $this->warnings);
            }

            function key (): ?string {
                return $this->key;
            }

            function route (string $key = NULL): array {
                return ($key ? Web::routes()[$key] : $this->route);
            }

            function inGroup (string $group, array $exceptions = array()): bool {
                if (in_array($this->key, Web::group($group))) {
                    foreach ($exceptions as $omit) {
                        if (in_array($this->key, Web::group($omit))) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            }

            function folder (string $key = NULL): string {
                return ($key ? Web::routes()[$key][0] : $this->route[0]);
            }

            function regex (string $regex): bool {
                return (bool)preg_match('~'. $regex .'~', $this->url_path);
            }

            function params (): array {
                return $this->params;
            }

            function param (string $name): ?string {
                return $this->params[$name] ?? NULL;
            }

            function path (): string {
                return $this->url_path;
            }

            function request (): string {
                return $this->request;
            }

            function isType (string $request) {
                return ($this->request == $request);
            }

            function allows (string $request, string $route = NULL): bool {
                return in_array($request, ($route ? Web::routes()[$route][1] : $this->route[1]));
            }

            // (string|array) $key
            function is ($key, string $request = NULL): bool {
                return (in_array($this->key, (array)$key) && ($request == NULL || $this->request == $request));
            }

            function page (): int {
                return $this->page;
            }

            function language (): ?string {
                return $this->language;
            }

            function pattern (string $key = NULL, string $lang = NULL): string {
                return ($key ?
                    Web::routes()[$key][2][($lang ?: (Web::routes()[$key][5])::get())][0] :
                    $this->route[2][($lang ?: ($this->route[5])::get())][0]
                );
            }

            function force (string $key): void {
                $this->key   = $key;
                $this->route = Web::routes()[$key];
            }
        };

        if ($return) {
            return $object;
        }
        else {
            self::$web = $object;

            return $object->prepared();
        }
    }

    static function __callStatic (string $method, array $args) {
        return (self::$web ? call_user_func_array(
            array(self::$web, $method),
            $args
        ) : NULL);
    }

    static function url (string $key, array $values = NULL, string $language = NULL, int $page = 0, array $_get = NULL): string {
        $route      = self::$routes[$key];
        $language   = ($language ?? ($route[5])::get());
        $path       = $route[2][$language][0];

        if ($values) {
            foreach ($values as $name => $value) {
                $path = preg_replace(
                    array("/\[". $name .":[^\[\]]+\]\??/", "/\[". $name .":\(.*?\)\]\??/"),
                    Text::slug($value), $path
                );
            }
        }

        $path = preg_replace_callback(
            "~(/|)\[lg\](\?)?~",
            function ($matches) use ($language, $route) {
                // if lg could be optional
                if (!empty($matches[2]) && $language == ($route[5])::default()) {
                    return NULL;
                }

                return ($matches[1].$language);
            },
            $path
        );

        $path = ($page > 1 ?
            str_replace(
                "[page]",
                preg_replace('/\(.*\)/', $page, $route[3][$language][0]),
                $path
            )
            :
            preg_replace("~/?\[page\]~", '', $path)
        );

        $url = (self::$protocol .'://'. self::$site .'/'. preg_replace('~^/~', '', $path));

        if ($_get) {
            $url .= ('?' . http_build_query($_get));
        }

        return $url;
    }

    static function go (string $key, array $values = NULL, string $language = NULL, int $page = 0, array $_get = NULL, int $http_response_code = NULL): void {
        header("Location: ". self::url($key, $values, $language, $page, $_get), true, $http_response_code);
    }

    static function dontGo () {
        header_remove("Location");
    }

    static function back (string $key = NULL, array $values = NULL, int $page = 0, array $_get = NULL): ?string {
        // $route is the backup if is no referer
        return ($_SERVER['HTTP_REFERER'] ?? ($key ? self::url($key, $values, $page, $_get) : NULL));
    }

    static function goBack (string $key = NULL, array $values = NULL, int $page = 0, array $_get = NULL): bool {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: ". $_SERVER['HTTP_REFERER']);
            return false;
        }
        if ($route) { // backup if is no referer
            header("Location: ". self::get($key, $values, $page, $_get));
            return true;
        }
        return false;
    }

    static function nameByFolder (string $folder): ?string {
        return (self::$keys_by_folder[$folder] ?? NULL);
    }

    static function routes (string $request = NULL): array {
        return ($request ? self::$routes_by_request[$request] : self::$routes);
    }

    static function exists (string $key, string $request = NULL): bool {
        return (!$request ? isset(self::$routes[$key]) : isset(self::$routes_by_request[$request][$key]));
    }

    static function groups (): array {
        return self::$groups;
    }

    static function group (string $group): array {
        return self::$groups[$group];
    }

    static function site (bool $protocol = true): string {
        return (($protocol ? (self::$protocol .'://') : '') . self::$site .'/');
    }

    static function statics (string $name): string {
        return (self::$protocol .'://'. self::$site .'/'. ENV::statics($name));
    }
}
