<?php

namespace Arshwell\Monolith;

/**
 * Class for URL manipulation.
 *
 * It has routine functions.

 * @package https://github.com/arshwell/monolith
*/
final class URL {

    static function protocol (bool $unused = false): string {
        $protocol = ($_SERVER['REQUEST_SCHEME'] ?? (stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http' : 'https'));

        if ($unused) {
            return ($protocol == 'https' ? 'http' : 'https');
        }
        return $protocol;
    }

    static function hasSSL (string $domain = NULL): bool {
        try {
            fclose(fsockopen(
                'ssl://'. ($domain ?? ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'])),
                443, $errno, $errstr, 5
            ));

            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    static function get (bool $protocol = true, bool $query = true, string $url = NULL): ?string {
        if (!$url) {
            $url = self::protocol() .'://'. ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']) . ($_SERVER['REQUEST_URI'] ?? (self::path() . (isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '')));
        }

        $info = parse_url($url);

        if (empty($info)) {
            return NULL;
        }

        return (
            ($protocol ? ($info['scheme'] . '://') : '') .
            $info['host'] . $info['path'] . ($query && !empty($info['query']) ? '?'.$info['query'] : '')
        );
    }

    static function path (string $url = NULL): string {
        if ($url) {
            return urldecode(parse_url($url, PHP_URL_PATH));
        }

        // Note: $_SERVER['REDIRECT_URL'] replaces multiple '/'-es with only one.
        return urldecode($_SERVER['SCRIPT_URL'] ?? $_SERVER['REDIRECT_URL']);
    }
}
