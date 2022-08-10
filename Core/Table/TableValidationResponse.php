<?php

namespace Arsh\Core\Table;

use Arsh\Core\Session;
use Arsh\Core\Web;

/**
 * Object class for working with table validation response.

 * @package https://github.com/arshavin-dev/ArshWell
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

    final function __construct (array $data) {
        $this->data = array_merge($this->data, $data);
    }

    final function submitted (): bool {
        return $this->data['submitted'];
    }
    final function expired (): bool {
        return $this->data['expired'];
    }

    /**
     * Check if form, or certain field, is valid.
     *
     * @param string $key (optional)
     *
     * @return bool
     */
    final function valid (string $key = NULL): bool {
        if ($key) {
            return ($this->data['errors'][$key] == NULL);
        }
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
    final function setError (string $key, string $value = NULL) {
        $this->data['errors'][$key] = $value;

        $this->data['valid'] = (count(array_filter($this->data['errors'], function ($error) {
            return $error != NULL;
        })) == 0);
    }

    final function __set (string $key, $value): void {
        $this->data['values'][$key] = $value;
    }
    final function values (array $keys = NULL, bool $preserve_keys = true): array {
        if (!$keys) {
            return ($preserve_keys ? $this->data['values'] : array_values($this->data['values']));
        }
        $keys = array_flip($keys);
        $keys = array_replace($keys, array_intersect_key($this->data['values'], $keys));

        return ($preserve_keys ? $keys : array_values($keys));
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
