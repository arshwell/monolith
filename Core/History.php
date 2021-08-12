<?php

namespace Arsh\Core;

/**
 * Core class for backend programming which has rutine functions.

 * @package App
 * @author Tanasescu Valentin <valentin_tanasescu.2000@yahoo.com>
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

	}

	static function list (string $request = NULL, int $limit = NULL): array {

	}

	static function select (closure $function, int $limit = NULL): array {

	}

	static function rGet (int $index = 1, string $request = NULL): array {

	}

	static function rList (string $request = NULL, int $limit = NULL): array {

	}

	static function rSelect (closure $function, int $limit = NULL): array {

	}
}
