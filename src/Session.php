<?php

namespace Arsavinel\Arshwell;

use Arsavinel\Arshwell\Web;

/**
 * Class for backend programming which has routine functions.

 * @package https://github.com/arsavinel/ArshWell
*/
final class Session {
	private static $session	= NULL;
	private static $key		= NULL;
	private static $is_new	= false;

	static function repair (): void {
		session_id(uniqid());
		session_start();
		session_regenerate_id();
	}

    static function set (string $key): bool {
		if (!isset(self::$session)) {
			self::$key = $key;

			if (!isset($_SESSION[$key]['App']) || !isset($_SESSION[$key]['arsavinel']['ArshWell'])) {
				$_SESSION[$key] = array(
					'arsavinel' => array(
                        'ArshWell' => self::default()
                    ),
					'App' => array()
				);

				self::$is_new = true;
			}
			self::$session = &$_SESSION[$key]['arsavinel']['ArshWell'];

			if (!empty(self::$session['views'])) {
				foreach (self::$session['views'] as $class => $views) {
					foreach ($views as $time => $ids) {
						($class)::update(
							array(
								'set'	=> 'selected_at = ?',
								'where'	=> ($class)::PRIMARY_KEY .' IN ('. implode(', ', $ids) .')'
							),
							array($time)
						);
					}
				}

				self::$session['views'] = array();
			}

			$_SESSION = &$_SESSION[$key]['App'];

			return true;
		}
		return false;
	}

	static private function default (): array {
		return array(
			'design'	=> 0, // is modified by mxdvcwdthflg (comes from AJAX)
			'languages'	=> array(),
			'form'		=> array(),
			'auth'		=> array(),
			'token'		=> array(
				'form'      => md5(uniqid(rand(), true)),
				'ajax'      => md5(uniqid(rand(), true))
			),
			'history'	=> array(),
			'panel'		=> array(
				'active' => false, // it gets activated when supervisor displays the button
				'button.position.top'	=> 'unset',
				'button.position.bottom'=> 0,
				'button.position.left'	=> 'unset',
				'button.position.right'	=> 0
			)
		);
	}

	static function memorize (): void {
		$array_key_last = array_key_last(self::$session['history']);

		$route = array(
			'key'		=> Web::key(),
			'request'	=> Web::request(),
			'params'	=> Web::params(),
			'language'	=> Web::language(),
			'page'		=> Web::page(),
			'$_GET'		=> $_GET
		);

		if ($array_key_last) {
			$route['instances'] = self::$session['history'][$array_key_last]['instances'];

			if (serialize(self::$session['history'][$array_key_last]) == serialize($route)) {
				$route['instances']++;

				self::$session['history'][$array_key_last] = $route;
				return;
			}
		}

		$route['instances'] = 1;

		self::$session['history'][(int)microtime(true)] = $route;
	}

	static function isNew (): bool {
		return self::$is_new;
	}

	// getters
	static function tokens (): array {
		return self::$session['token'];
	}
	static function token (string $name): string {
		return self::$session['token'][$name];
	}
	static function design (): string {
		return self::$session['design'];
	}
	static function language (string $class = NULL): ?string {
		if ($class) {
			return (self::$session['languages'][$class] ?? NULL);
		}
		return self::$session['languages'][array_key_last(self::$session['languages'])];
	}
	static function forms (): array {
		return self::$session['form'];
	}
	static function form (string $name, string $column = NULL): ?array {
		return ($column ? (self::$session['form'][$name][$column] ?? NULL) : (self::$session['form'][$name] ?? NULL));
	}
	static function auths (): array {
		return self::$session['auth'];
	}
	static function auth (string $table, string $column = NULL) {
		return ($column ? (self::$session['auth'][$table][$column] ?? NULL) : (self::$session['auth'][$table] ?? NULL));
	}
	static function panel (string $name): ?string {
		return (self::$session['panel'][$name] ?? NULL);
	}
	static function history (): array {
		return self::$session['history'];
	}

	// setters
	static function setDesign (int $width): void {
		self::$session['design'] = $width;
	}
	static function setLanguage (string $class, string $lang): void {
		self::$session['languages'][$class] = $lang;
	}
	static function setForm (string $name, array $value): void {
		self::$session['form'][$name] = $value;
	}
	static function setAuth (string $name, array $value): void {
		self::$session['auth'][$name] = $value;
	}
	static function setPanel (string $name, string $value): void {
		self::$session['panel'][$name] = $value;
	}
	static function setView (string $class, int $id): void {
		self::$session['views'][$class][time()][] = $id;
	}

	// unsetter
	static function unset (string $name, $callable = NULL): bool {
		if (is_callable($callable)) {
			$return = false;
			foreach (self::$session[$name] as $key => $value) {
				if ($callable($key, $value)) {
					unset(self::$session[$name][$key]);
					$return = true;
				}
			}
			return $return;
		}
		else {
			unset(self::$session[$name][$callable]);
		}

		return true;
	}
	static function empty (): void {
		// reinit ArshWell session
	    self::$session = self::default();

	    // empty App session
	    $_SESSION = NULL; // unset() doesn't work because you don't delete memory, but only reference

	    // We don't use session_destroy() for not removing also another site's sessions.
	}

	// helpers
	static function count (string $key): int {
		return count(self::$session[$key]);
	}

	static function all (bool $also_me = true, bool $also_arshwell_session = false): array {
		$my_session_id	= session_id();
		$sessions		= array();

		foreach (scandir(session_save_path()) as $name) {
		    if (strpos($name, '.') === false) { // This skips temp files that aren't sessions
				$name = preg_replace("/^sess_/", '', $name);

				if (preg_match("/^[a-zA-Z0-9-]+$/", $name)
				&& ($name != $my_session_id || $also_me)) {
			        session_abort();
			        session_id($name);
			        session_start();

					if (!empty($_SESSION[self::$key]['arsavinel']['ArshWell'])) {
						$sessions[$name] = ($also_arshwell_session ? $_SESSION[self::$key] : $_SESSION[self::$key]['App']);
				    }
				}
			}
		}

		session_abort();
		session_id($my_session_id);
		session_start();

		return $sessions;
	}
}
