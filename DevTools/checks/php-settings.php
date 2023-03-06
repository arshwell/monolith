<?php

use ArshWell\Monolith\DevTool\DevToolData;
use ArshWell\Monolith\DevTool\DevToolHTML;
use ArshWell\Monolith\Folder;

/**
 * Verifies if the minimum PHP requirements are met.

 * @package https://github.com/arshwell/monolith
 */
if (version_compare(PHP_VERSION, '7.3') == -1) {
    DevToolHTML::html(
        DevToolHTML::code("<i>ArshWell ". DevToolData::ArshWellVersion() ."</i>") .
        DevToolHTML::error("PHP_VERSION (". PHP_VERSION .") is lower than required 7.3 version.")
    );
}
if (!class_exists('ZipArchive', false)) {
    DevToolHTML::html(
        DevToolHTML::code("<i>ArshWell ". DevToolData::ArshWellVersion() ."</i>") .
        DevToolHTML::error(
			"ZipArchive class is missing. You need it for launching the build.<br>
			Try to enable <i>zip</i> extension from <b>cPanel > PHP Version > Extensions</b>."
		)
    );
}
if (!function_exists('json_encode')) {
    DevToolHTML::html(
        DevToolHTML::code("<i>ArshWell ". DevToolData::ArshWellVersion() ."</i>") .
        DevToolHTML::error(
			"json_encode() function is missing. You need it for, but not only, ENV, Cache, Layyout, Table, etc.<br>
			Check <b>cPanel > PHP Version</b>."
		)
    );
}
if (!function_exists('mime_content_type')) {
    DevToolHTML::html(
        DevToolHTML::code("<i>ArshWell ". DevToolData::ArshWellVersion() ."</i>") .
        DevToolHTML::error(
			"mime_content_type() function is missing. You need it for file management.<br>
			Try to enable <i>fileinfo</i> extension from <b>cPanel > PHP Version > Extensions</b>."
		)
    );
}
