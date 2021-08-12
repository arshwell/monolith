<?php

namespace Arsh\Core\Table;

use Arsh\Core\Session;
use Arsh\Core\Web;

/**
 * Object class for working with table validation response.

 * @package App
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
 * @link www.iscreambrands.ro
*/
final class TableValidationResponse {
    private $data = array(
        'values'    => array(),
        'tags'      => array(),
        'errors'    => array(),
        'valid'     => true,
        'submitted' => false,
        'expired'   => false,
        'immortal'  => false
    );

    final function __construct ($data) {
        $this->data = array_merge($this->data, $data);
    }

    final function submitted (): bool {
        return $this->data['submitted'];
    }
    final function expired (): bool {
        return $this->data['expired'];
    }
    final function valid (): bool {
        return $this->data['valid'];
    }
    final function invalid (): bool {
        return !$this->data['invalid'];
    }

    final function errors (array $keys = NULL): array {
        if (!$keys) {
            return $this->data['errors'];
        }
        $keys = array_flip($keys);

        return array_replace($keys, array_intersect_key($this->data['errors'], $keys));
    }
    final function error (string $key, string $value = NULL): ?string {
        return ($this->data['errors'][$key] ?? NULL);
    }

    final function __set (string $key, $value): void {
        $this->data['values'][$key] = $value;
    }
    final function values (array $keys = NULL, bool $keep_keys = false): array {
        if (!$keys) {
            return $this->data['values'];
        }
        $keys = array_flip($keys);

        return ($keep_keys ?
            array_replace($keys, array_intersect_key($this->data['values'], $keys)) :
            array_values(array_intersect_key($this->data['values'], $keys))
        );
    }
    final function value (string $key) {
        return ($this->data['values'][$key] ?? NULL);
    }
    final function array (string $key): array {
        return ($this->data['values'][$key] ?? array());
    }

    final function remember (bool $immortal, string $suffix = NULL): void {
        Session::setForm(($suffix ? ($suffix.'.'.Web::key().'.'.$suffix) : Web::key()), array(
            'values'    => $this->data['values'],
            'errors'    => $this->data['errors'],
            'valid'     => $this->data['valid'],
            'expired'   => false,
            'immortal'  => $immortal,
            'route'     => Web::key()
        ));
    }
    final function forget (string $suffix = NULL): void {
        $name = ($this->data['route'] ?? Web::key());
        Session::unset('form', ($suffix ? ($suffix.'.'.$name.'.'.$suffix) : $name));
    }

    final function json (): string {
        return json_encode($this->data);
    }
}
