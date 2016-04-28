<?php

//Clase Debug, para escribir Debugs que solo sean visible para usuario Admin Lemontech
require_once dirname(__FILE__) . '/../conf.php';

/**
 * Class Debug
 * Para escribir Debugs que solo sean visible para usuario Admin Lemontech
 */
class Debug {

	static $pr_template;

	/**
	 * @param $sesion
	 * @param $str
	 * @return string
	 */
	static public function debug_echo(&$sesion, $str) {
		return $sesion->usuario->TienePermiso('SADM') ? $str : '';
	}

	/**
	 * @param $sesion
	 * @param $arreglo
	 * @return bool|void
	 */
	static public function debug_print_r(&$sesion, $arreglo) {
		if ($sesion->usuario->TienePermiso('SADM')) {
			echo '<pre>';
			print_r($arreglo);
			echo '</pre>';
			return true;
		} else
			return;
	}

	/**
	 * @param $sesion
	 * @param $str
	 * @return bool|void
	 */
	static public function h1(&$sesion, $str) {
		if ($sesion->usuario->TienePermiso('SADM')) {
			echo '<h1>';
			echo $str;
			echo '</h1>';
			return true;
		} else
			return;
	}

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
