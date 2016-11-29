<?php

namespace TTB;
/**
* Class Debug
* Para escribir Debugs que solo sean visible para usuario Admin Lemontech
*/
class Debug {
	static $pr_template;

	/**
	 * @param $variable
	 */
	static public function pr($variable) {
		$template = self::getPrTemplate();
		printf($template, print_r($variable, 1));
	}

	private static function getPrTemplate() {
		if (!empty(self::$pr_template)) {
			return self::$pr_template;
		}

		if (isset($_SERVER['SHELL']) || preg_match('/^curl\/.*/', $_SERVER['HTTP_USER_AGENT'])) {
			self::$pr_template = "%s\n";
		} else {
			self::$pr_template = '<pre>%s</pre>';
		}
		return self::$pr_template;
	}
}
