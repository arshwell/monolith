<?php

namespace ArshWell\Monolith;

/**
 * Class for backend programming which has routine functions.

 * @package https://github.com/arshwell/monolith
*/
/*
	array(
		'route'		=> route name,
		'params'	=> params,
		'request'	=> request,
		'time'		=> time
	)
*/
final class History {
    static function get (int $index = 1, string $request = NULL): array {
        return [];
	}

	static function list (string $request = NULL, int $limit = NULL): array {
        return [];
	}

	static function select (\closure $function, int $limit = NULL): array {
        return [];
	}

	static function rGet (int $index = 1, string $request = NULL): array {
        return [];
	}

	static function rList (string $request = NULL, int $limit = NULL): array {
        return [];
	}

	static function rSelect (\closure $function, int $limit = NULL): array {
        return [];
	}
}
