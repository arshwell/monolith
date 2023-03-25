<?php

namespace Arshwell\Monolith\Table;

use Arshwell\Monolith\Session;
use Arshwell\Monolith\Table;
use Arshwell\Monolith\StaticHandler;

/*
 * Static and non-static methods, for manipulating MySQL DB tables,
 * which help you to organize the code and create help custom methods
 * inside every class which extends Table class.
*/
abstract class TableAuth extends Table {
    // static
    final static function exists (string $password, int $algorithm) {
        return (bool)self::count((static::class)::PASSWORD .' = ?', array(password_hash($password, $algorithm)));
    }


    // static
    final static function setCookieID (int $id, int $expires): bool {
        return setcookie("arsavinel[Arshwell][".static::class."]", $id, time() + $expires, StaticHandler::getEnvConfig()->getSiteRoot() ?: '/');
    }
    // object
    final function setCookie (int $expires): bool {
        return setcookie("arsavinel[Arshwell][".static::class."]", $this->id_table, time() + $expires, StaticHandler::getEnvConfig()->getSiteRoot() ?: '/');
    }

    // static
    final static function issetCookieID (int $id = 0): bool {
        if (!$id) {
            return isset($_COOKIE['arsavinel']['Arshwell'][static::class]);
        }
        return isset($_COOKIE['arsavinel']['Arshwell'][static::class]) && $_COOKIE['arsavinel']['Arshwell'][static::class] == $id;
    }
    // object
    final function issetCookie (): bool {
        return isset($_COOKIE['arsavinel']['Arshwell'][static::class]) && $_COOKIE['arsavinel']['Arshwell'][static::class] == $this->id_table;
    }

    // static
    final static function getCookieID (): ?int {
        return $_COOKIE['arsavinel']['Arshwell'][static::class] ?? NULL;
    }


    // static
    final static function loginID (int $id, array $data = array()): void {
        $data[(static::class)::PRIMARY_KEY] = $id;
        Session::setAuth((static::class)::TABLE, $data);
    }
    // object
    final function login (array $data = array()): void {
        $data[(static::class)::PRIMARY_KEY] = $this->id_table;
        Session::setAuth((static::class)::TABLE, $data);
    }


    // static
    final static function loggedInID (int $id = 0): bool {
        return (
            Session::auth((static::class)::TABLE) &&
            ($id == 0 || $id == Session::auth((static::class)::TABLE, (static::class)::PRIMARY_KEY))
        );
    }
    // object
    final function loggedIn (): bool {
        return ($this->id_table == Session::auth((static::class)::TABLE, (static::class)::PRIMARY_KEY));
    }


    // static
    final static function auth (string $key = NULL) {
        return Session::auth((static::class)::TABLE, $key);
    }


    // static
    final static function logoutID (): bool {
        if (Session::auth((static::class)::TABLE)) {
            Session::unset('auth', (static::class)::TABLE);
            return true;
        }
        return false;
    }
    // object
    final function logout (): bool {
        if (Session::auth((static::class)::TABLE, (static::class)::PRIMARY_KEY) == $this->id_table) {
            Session::unset('auth', (static::class)::TABLE);
            return true;
        }
        return false;
    }
}
