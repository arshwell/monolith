<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Table;
use Arsavinel\Arshwell\Cache;

/*
 * Class used for maintenance configuration.
*/
abstract class TableMaintenance extends Table {

    abstract static function route (): string;

    static function setActive (bool $active) {
        if (!is_file(Cache::file('vendor/arsavinel/arshwell/maintenance')) || !Cache::fetch('vendor/arsavinel/arshwell/maintenance')) {
            $maintenance = array(
                'route' => static::route(),
                'active' => $active,
                'smart' => true
            );
        }
        else {
            $maintenance = Cache::fetch('vendor/arsavinel/arshwell/maintenance');

            $maintenance['active'] = $active;
        }

        Cache::store('vendor/arsavinel/arshwell/maintenance', $maintenance);
    }

    static function setSmart (bool $smart) {
        if (!is_file(Cache::file('vendor/arsavinel/arshwell/maintenance')) || !Cache::fetch('vendor/arsavinel/arshwell/maintenance')) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => $smart
            );
        }
        else {
            $maintenance = Cache::fetch('vendor/arsavinel/arshwell/maintenance');

            $maintenance['smart'] = $smart;
        }

        Cache::store('vendor/arsavinel/arshwell/maintenance', $maintenance);
    }

    static function isActive (): bool {
        $maintenance = Cache::fetch('vendor/arsavinel/arshwell/maintenance');

        if (!is_file(Cache::file('vendor/arsavinel/arshwell/maintenance')) || !$maintenance) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => false
            );

            Cache::store('vendor/arsavinel/arshwell/maintenance', $maintenance);
        }

        return $maintenance['active'];
    }

    static function isSmart (): bool {
        $maintenance = Cache::fetch('vendor/arsavinel/arshwell/maintenance');

        if (!is_file(Cache::file('vendor/arsavinel/arshwell/maintenance')) || !$maintenance) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => false
            );

            Cache::store('vendor/arsavinel/arshwell/maintenance', $maintenance);
        }

        return $maintenance['smart'];
    }
}
