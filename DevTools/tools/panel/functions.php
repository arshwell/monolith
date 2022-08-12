<?php

use Arsavinel\Arshwell\Folder;

/**
 * Function used only by DevPanel.
 * Gets this framework's version from vendor/composer/installed.json.

 * @return string no matter what
 */
function DevPanelVersion (): string {
    $installed = json_decode(file_get_contents(Folder::root() . 'vendor/composer/installed.json'), true);

    foreach ($installed["packages"] as $package) {
        if (trim($package['name']) == "arsavinel/arshwell") {
            return $package['version'];
        }
    }

    return "";
}

/**
 * Function used only by DevPanel.
 * Extract only x.y.z numbers from version name.

 * @return string because version can start with zero (ex: v0.1.4)
 */
function DevPanelVersionNumber (): string {
    $version = strtolower(DevPanelVersion());

    if (preg_match("/^[v\.]+? (\d+\.\d+ (.\d+)? )/x", $version, $matches)) {
        return $matches[1];
    }

    return preg_replace("/[^0-9.]+/", '', $version) ?: preg_replace("/[^a-z0-9]+/", '', $version) ?: $version;
}
