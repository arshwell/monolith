<?php

namespace Arshwell\Monolith\DevTool;

use Arshwell\Monolith\Folder;

/**
 * Static class about Arshwell framework.

 * @package https://github.com/arshwell/monolith
 */
final class DevToolData {

    /**
     * Function used by DevPanel.
     * Gets this framework's version from vendor/composer/installed.json.

     * @return string no matter what
     */
    static function ArshwellVersion (): string {
        $installed = json_decode(file_get_contents(Folder::root() . 'vendor/composer/installed.json'), true);

        foreach ($installed["packages"] as $package) {
            if (trim($package['name']) == "arshwell/monolith") {
                return $package['version'];
            }
        }

        return "";
    }

    /**
     * Function used by DevPanel.
     * Extract only xyz numbers from version name.

     * @return string because version can start with zero (ex: v0.1.4)
     */
    static function ArshwellVersionNumber (): string {
        $version = strtolower(DevToolData::ArshwellVersion());

        if (preg_match("/^[v\.]+? (\d+\.\d+ (.\d+)? )/x", $version, $matches)) {
            return $matches[1];
        }

        return preg_replace("/[^0-9.]+/", '', $version) ?: preg_replace("/[^a-z0-9]+/", '', $version) ?: $version;
    }

}
