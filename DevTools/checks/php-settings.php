<?php

use Arsh\Core\Arsh;

/**
 * Verifies if url requests (from routes.php) exists and are uppercase.

 * @package App/DevTools
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 */
if (version_compare(PHP_VERSION, '7.3') == -1) {
    _html(
        _code("<i>ArshWell ". Arsh::VERSION ."</i>") .
        _error("PHP_VERSION (". PHP_VERSION .") is lower than required 7.3 version.")
    );
}
if (!class_exists('ZipArchive', false)) {
    _html(
        _code("<i>ArshWell ". Arsh::VERSION ."</i>") .
        _error(
			"ZipArchive class is missing. You need it for launching the build.<br>
			Try to enable <i>zip</i> extension from <b>cPanel > PHP Version > Extensions</b>."
		)
    );
}
if (!function_exists('mime_content_type')) {
    _html(
        _code("<i>ArshWell ". Arsh::VERSION ."</i>") .
        _error(
			"mime_content_type() function is missing. You need it for file management.<br>
			Try to enable <i>fileinfo</i> extension from <b>cPanel > PHP Version > Extensions</b>."
		)
    );
}
