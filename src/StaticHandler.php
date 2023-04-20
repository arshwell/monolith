<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\Env\EnvConfig;

use ErrorException;

/**
 * Static container class with global variables.
 */
final class StaticHandler
{
    /** @var EnvConfig */
    private static $envConfig;

    /** @var bool */
    private static $is_cron = NULL;

    /** @var string */
    private static $client_ip = NULL; // on CRON Jobs, it is not set

    /** @var bool */
    private static $supervisor = false; // on CRON Jobs, it is false


    static function iniSetPHP(): void
    {
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

            self::$supervisor = in_array(self::$client_ip, Func::arrayFlatten(self::$envConfig->get('development.ips')));
        }

        set_error_handler(function ($e_code, $text, $file, $line) {
            if (error_reporting() != 0) { // Because for expressions prepended by @ it returns 0 (zero).
                throw new ErrorException($text, $e_code, 0, $file, $line);
            }
        });

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

        // errors folder
        if (!is_dir(Folder::root() . 'errors/')) {
            mkdir(Folder::root() . 'errors/');
        }

        ini_set(
            'error_log',
            Folder::root() . 'errors/'. strtok(strtok(Folder::shorter(StaticHandler::scriptfile() ?? getcwd()), '.'), '/') .'.log'
        ); // setting for saving errors (index.log, download.log, crons.log)

        foreach (glob(Folder::realpath('vendor/arshwell/monolith/DevTools/functions/*.php')) as $v) {
            require($v);
        }

        // .htaccess file
        if (!is_file(Folder::root(). '.htaccess')) {
            copy(Folder::root() . 'vendor/arshwell/monolith/resources/htaccess/project.htaccess', Folder::root() . '.htaccess');
        }

        // .htaccess in files folder
        if (!is_file(Folder::root() . 'uploads/files/.htaccess')) {
            if (!is_dir(Folder::root() . 'uploads/files/')) {
                mkdir(Folder::root() . 'uploads/files/', 0777, true);
            }
            copy(Folder::root() . 'vendor/arshwell/monolith/resources/htaccess/uploads.files.htaccess', Folder::root() . 'uploads/files/.htaccess');
        }

        // .htaccess in design folder
        if (!is_file(Folder::root() . 'uploads/design/.htaccess')) {
            if (!is_dir(Folder::root() . 'uploads/design/')) {
                mkdir(Folder::root() . 'uploads/design/', 0777, true);
            }
            copy(Folder::root() . 'vendor/arshwell/monolith/resources/htaccess/uploads.design.htaccess', Folder::root() . 'uploads/design/.htaccess');
        }

        if (strstr(Folder::shorter(getcwd()), '/', true) == 'crons' && !StaticHandler::isCRON()
        && (!StaticHandler::getEnvConfig('development.debug') || !StaticHandler::supervisor())) { // CRON is run by a stranger.
            http_response_code(404);
            exit;
        }

        // Supervisors can see the errors directly displayed.
        if (StaticHandler::supervisor()) {
            ini_set('display_errors', TRUE);
            ini_set('display_startup_errors', TRUE);
        }
    }


    /**
     * Get EnvConfig object. Or just certain key value.
     *
     * @return EnvConfig|mixed
     */
    static function getEnvConfig(string $key = null)
    {
        if ($key) {
            return self::$envConfig->get($key);
        }

        return self::$envConfig;
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


    static function setEnvConfig(EnvConfig $envConfig): void
    {
        self::$envConfig = $envConfig;
    }
}
