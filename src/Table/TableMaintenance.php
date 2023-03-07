<?php

namespace ArshWell\Monolith\Table;

use ArshWell\Monolith\Table;
use ArshWell\Monolith\Cache;

/*
 * Class used for maintenance configuration.
*/
abstract class TableMaintenance extends Table {

    abstract static function route (): string;

    static function setActive (bool $active) {
        if (!is_file(Cache::file('vendor/arshwell/monolith/maintenance')) || !Cache::fetch('vendor/arshwell/monolith/maintenance')) {
            $maintenance = array(
                'route' => static::route(),
                'active' => $active,
                'smart' => true
            );
        }
        else {
            $maintenance = Cache::fetch('vendor/arshwell/monolith/maintenance');

            $maintenance['active'] = $active;
        }

        Cache::store('vendor/arshwell/monolith/maintenance', $maintenance);
    }

    static function setSmart (bool $smart) {
        if (!is_file(Cache::file('vendor/arshwell/monolith/maintenance')) || !Cache::fetch('vendor/arshwell/monolith/maintenance')) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => $smart
            );
        }
        else {
            $maintenance = Cache::fetch('vendor/arshwell/monolith/maintenance');

            $maintenance['smart'] = $smart;
        }

        Cache::store('vendor/arshwell/monolith/maintenance', $maintenance);
    }

    static function isActive (): bool {
        $maintenance = Cache::fetch('vendor/arshwell/monolith/maintenance');

        if (!is_file(Cache::file('vendor/arshwell/monolith/maintenance')) || !$maintenance) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => false
            );

            Cache::store('vendor/arshwell/monolith/maintenance', $maintenance);
        }

        return $maintenance['active'];
    }

    static function isSmart (): bool {
        $maintenance = Cache::fetch('vendor/arshwell/monolith/maintenance');

        if (!is_file(Cache::file('vendor/arshwell/monolith/maintenance')) || !$maintenance) {
            $maintenance = array(
                'route' => static::route(),
                'active' => false,
                'smart' => false
            );

            Cache::store('vendor/arshwell/monolith/maintenance', $maintenance);
        }

        return $maintenance['smart'];
    }
}
