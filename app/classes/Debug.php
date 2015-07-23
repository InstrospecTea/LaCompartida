<?php
//Clase Debug, para escribir Debugs que solo sean visible para usuario Admin Lemontech
require_once dirname(__FILE__) . '/../conf.php';

/**
 * Class Debug
 * Para escribir Debugs que solo sean visible para usuario Admin Lemontech
 */
class Debug {

	/**
	 * @param $sesion
	 * @param $str
	 * @return string
	 */
	function debug_echo(&$sesion, $str) {
		return $sesion->usuario->TienePermiso('SADM') ? $str : '';
	}

	/**
	 * @param $sesion
	 * @param $arreglo
	 * @return bool|void
	 */
	function debug_print_r(&$sesion, $arreglo) {
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
	function h1(&$sesion, $str) {
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
	function pr($variable) {
		echo '<pre>';
		print_r($variable);
		echo '</pre>';
	}
}