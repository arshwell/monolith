<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Session;
use Arsavinel\Arshwell\Table;
use Arsavinel\Arshwell\ENV;

/*
 * Static and non-static methods, for manipulating MySQL DB tables,
 * which help you to organize the code and create help custom methods
 * inside every class which extends Table class.
*/
abstract class TableAuth extends Table {
    // static
    final static function exists (string $password) {
        return (bool)self::count(static::PASSWORD .' = ?', array(password_hash($password)));
    }


    // static
    final static function setCookieID (int $id, int $expires): bool {
        return setcookie("ArshWell[".static::class."]", $id, time() + $expires, ENV::root() ?: '/');
    }
    // object
    final function setCookie (int $expires): bool {
        return setcookie("ArshWell[".static::class."]", $this->id_table, time() + $expires, ENV::root() ?: '/');
    }

    // static
    final static function issetCookieID (int $id = 0): bool {
        if (!$id) {
            return isset($_COOKIE['ArshWell'][static::class]);
        }
        return isset($_COOKIE['ArshWell'][static::class]) && $_COOKIE['ArshWell'][static::class] == $id;
    }
    // object
    final function issetCookie (): bool {
        return isset($_COOKIE['ArshWell'][static::class]) && $_COOKIE['ArshWell'][static::class] == $this->id_table;
    }

    // static
    final static function getCookieID (): ?int {
        return $_COOKIE['ArshWell'][static::class] ?? NULL;
    }


    // static
    final static function loginID (int $id, array $data = array()): void {
        $data[static::PRIMARY_KEY] = $id;
        Session::setAuth(static::TABLE, $data);
    }
    // object
    final function login (array $data = array()): void {
        $data[static::PRIMARY_KEY] = $this->id_table;
        Session::setAuth(static::TABLE, $data);
    }


    // static
    final static function loggedInID (int $id = 0): bool {
        return (
            Session::auth(static::TABLE) &&
            ($id == 0 || $id == Session::auth(static::TABLE, static::PRIMARY_KEY))
        );
    }
    // object
    final function loggedIn (): bool {
        return ($this->id_table == Session::auth(static::TABLE, static::PRIMARY_KEY));
    }


    // static
    final static function auth (string $key = NULL) {
        return Session::auth(static::TABLE, $key);
    }


    // static
    final static function logoutID (): bool {
        if (Session::auth(static::TABLE)) {
            Session::unset('auth', static::TABLE);
            return true;
        }
        return false;
    }
    // object
    final function logout (): bool {
        if (Session::auth(static::TABLE, static::PRIMARY_KEY) == $this->id_table) {
            Session::unset('auth', static::TABLE);
            return true;
        }
        return false;
    }
}
