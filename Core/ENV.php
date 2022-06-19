<?php

namespace Arsh\Core;

use Arsh\Core\Folder;
use Arsh\Core\Filter;
use Arsh\Core\Func;
use Arsh\Core\File;
use ErrorException;
use PDOException;
use Exception;
use Throwable;

set_error_handler(function ($e_code, $text, $file, $line) {
    if (error_reporting() != 0) { // Because for expressions prepended by @ it returns 0 (zero).
        throw new ErrorException($text, $e_code, 0, $file, $line);
    }
});

final class ENV {
	/******************************************************************************
        All vars are NULL so we get error if we don't fetch() before anything.
    ******************************************************************************/
    private static $env         = NULL; // our env object
    private static $client_ip   = NULL; // on CRON Jobs, it is not set
    private static $is_cron     = NULL;
    private static $supervisor  = false; // on CRON Jobs, it is false

	static function fetch (string $path = NULL, bool $merge_env_build = false): object {
        $object = new class ($path, $merge_env_build) {
            private $path            = NULL;
            private $merge_env_build = NULL;
            private $json = NULL;
            private $site = NULL;
            private $root = NULL;

            function __construct (string $path = NULL, bool $merge_env_build) {
                if (!$path || $path[0] != '/') {
                    $path = Folder::root() . $path; // default (our ArshWell project)
                }
                if (substr($path, -1) != '/') {
                    $path .= '/';
                }

                $merge_env_build = ($merge_env_build && is_file($path.'env.build.json'));

                Cache::setProject($path ?: Folder::root()); // where to look for caches

                if (!is_file(Cache::file('ArshWell/env')) || !Cache::fetch('ArshWell/env')
                || $merge_env_build || filemtime("{$path}env.json") >= Cache::filemtime('ArshWell/env')) {
        			$this->json = json_decode(file_get_contents("{$path}env.json"), true, 512, JSON_THROW_ON_ERROR);

                    if ($merge_env_build) {
                        $this->json = array_replace_recursive(
                            $this->json,
                            json_decode(file_get_contents($path.'env.build.json'), true, 512, JSON_THROW_ON_ERROR)
                        );
                    }

                    array_walk_recursive($this->json['statics'], function (string &$value): void {
                        $value = ('statics/'.$value.'/');
                    });

                    if ($this->json['uploads']) {
                        $this->json['uploads'] = trim($this->json['uploads'], '/') . '/'; // having one, and only one, slash at the end
                    }

        			foreach ($this->json as $key => $value) {
                        if (is_array($value)) {
                            // NOTE: array_merge_recursive is not good
                            $this->json[$key] = array_replace_recursive(
                                $this->json[$key],
                                Func::arrayFlattenTree($value, NULL, '.', true)
                            );
                        }
        			}

                    if (!empty($this->json['board']['supervisors'])) {
                        // NOTE: We need flattining also subarrays of IPs
                        // Exception: arrayFlattenTree() can't make yet that.
                        $this->json['board']['supervisors'] = array_replace_recursive(
                            $this->json['board']['supervisors'],
                            Func::arrayFlattenTree($this->json['board']['supervisors'], NULL, '.', true)
                        );
                    }
                }
        		else {
        			$this->json = Cache::fetch('ArshWell/env');
        		}

                $this->merge_env_build  = $merge_env_build;
                $this->path             = $path;

                $this->site = (strstr($this->json['URL'], '/', true) ?: $this->json['URL']);
                $this->root = (strstr($this->json['URL'], '/') ?: '');
            }

            function cache (): void {
                // Changes you've made are cached.
                // NOTE: But env source stay the same.
                Cache::store('ArshWell/env', $this->json);

                if ($this->merge_env_build) {
                    // merge env with env.build
                    file_put_contents("{$this->path}env.json", json_encode(
                        array_replace_recursive(
                            json_decode(file_get_contents("{$this->path}env.json"), true),
                            json_decode(file_get_contents("{$this->path}env.build.json"), true)
                        ),
                        JSON_PRETTY_PRINT
                    ));
                    unlink("{$this->path}env.build.json");
                }
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

            function statics (string $key = NULL) {
        		return ($key ? $this->json['statics'][$key] : $this->json['statics']);
        	}

            function uploads (bool $add_subdirectory = false) {
                return $this->json['uploads'] . ($add_subdirectory ? 'uploads/' : '');
            }

        	function translations (): array {
        		return $this->json['translations'];
        	}

            function maintenance (string $key) {
        		return $this->json['maintenance'][$key];
        	}

            function setMaintenance (bool $active, bool $smart = NULL): void {
                $this->json['maintenance']['active'] = $active;

                if ($smart !== NULL) {
                    $this->json['maintenance']['smart'] = $smart;
                }
        	}
        };

        if (!self::$env) {
            self::$is_cron = (in_array(php_sapi_name(), ['cgi', 'cgi-fcgi', 'cli']) && !isset($_SERVER['TERM']));

            if (self::$is_cron == true) {
                if (empty($_SERVER['SCRIPT_FILENAME'])) {
                    foreach (debug_backtrace() as $trace) {
                        if (!empty($trace['file']) && !empty($trace['function']) && $trace['function'] == 'require') {
                            $_SERVER['SCRIPT_FILENAME'] = $trace['file'];
                            exit;
                        }
                    }
                }
            }
            else {
                self::$client_ip = (Filter::isIP(($_SERVER['HTTP_CLIENT_IP'] ?? '')) ? $_SERVER['HTTP_CLIENT_IP'] : (Filter::isIP(($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));

                // check for '::ffff:' prepended in IP
                if (strpos(self::$client_ip, '::') === 0) {
                    self::$client_ip = substr(self::$client_ip, strrpos(self::$client_ip, ':') + 1);
                }

                self::$supervisor = in_array(self::$client_ip, Func::arrayFlatten($object->board('supervisors')));
            }

            self::$env = $object;
            self::$env->cache();
        }

        return $object;
    }

	static function clientIP (): ?string {
        return self::$client_ip;
    }

	static function isCRON (): bool {
        return self::$is_cron;
    }

    static function supervisor (): bool {
        return self::$supervisor;
    }

    static function design (string $key = NULL): string {
        return (!$key ? 'uploads/design/' : array(
            'css'       => 'uploads/design/css/',
            'js-header' => 'uploads/design/js/h/',
            'js-footer' => 'uploads/design/js/f/',
            'mails'     => 'uploads/design/mails/',
            'dev'       => 'uploads/design/dev/'
        )[$key]);
    }

    static function scriptfile (): ?string {
        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        }

        $document_root  = ($_SERVER['DOCUMENT_ROOT'] ?? $_SERVER['CONTEXT_DOCUMENT_ROOT'] ?? NULL);
        $domain_path    = ($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? NULL);

        if (!empty($document_root) && !empty($domain_path)) {
            return ($document_root . $domain_path);
        }
        return NULL;
    }

    public static function __callStatic (string $method, array $args) {
        return call_user_func_array(
            array(self::$env, $method),
            $args
        );
    }
}

// CSV files created on ancient Macs have \r line endings.
// Turn it on if you want to be as permissive as possible about the CSV files you want to process.
ini_set('short_open_tag',       false);
ini_set('detect_line_endings',  false);

// For the PHP GD library. Make (at 1) GD ignore warnings while loading JPEGs.
ini_set('gd.jpeg_ignore_warning',   0);

ini_set('allow_url_fopen',          0);
ini_set('allow_url_include',        0);
ini_set('mysql.connect_timeout',    '50');

ini_set('memory_limit',             '100M');
ini_set('post_max_size',            '2M');
ini_set('upload_max_filesize',      '2M');
ini_set('max_execution_time',       15);
ini_set('max_input_time',           15);

ini_set('display_errors',           FALSE); // we hide them from the public
ini_set('display_startup_errors',   FALSE); // we hide them from the public
ini_set('ignore_repeated_errors',	TRUE);
ini_set('log_errors',				TRUE);
ini_set('log_errors_max_len',		1024); // Logging file size

error_reporting(E_ALL);

// Auto Class Load
spl_autoload_register(function ($class) {
    if (is_file(($file = (__DIR__ .'/../../'. preg_replace("~^Arsh/~", 'ArshWell/', str_replace('\\', '/', $class)) .'.php')))) {
        require($file);
    }
}, true);

if (!is_dir(__DIR__ .'/../../errors')) {
    mkdir(__DIR__ .'/../../errors');
}

ini_set(
	'error_log',
    // __DIR__ .'/../../errors/'. strtok(strtok(substr(ENV::scriptfile(), (strlen(__DIR__) - 8)), '.'), '/') .'.log'
	__DIR__ .'/../../errors/'. strtok(strtok(Folder::shorter(ENV::scriptfile() ?? getcwd()), '.'), '/') .'.log'
); // setting for saving errors (web.log, download.log, crons.log)

foreach (glob(Folder::realpath('ArshWell/DevTools/functions/*.php')) as $v) {
    require($v);
}

ENV::fetch();

if (strstr(Folder::shorter(getcwd()), '/', true) == 'crons' && !ENV::isCRON()
&& (!ENV::board('dev') || !ENV::supervisor())) { // CRON is run by a stranger.
    http_response_code(404);
    exit;
}

// Supervisors can see the errors directly displayed.
if (ENV::board('dev') && ENV::supervisor()) {
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
}
