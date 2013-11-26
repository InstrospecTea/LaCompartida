<?php

SimpleReport_Autoloader::Register();
// check mbstring.func_overload
if (ini_get('mbstring.func_overload') & 2) {
	throw new Exception('Multibyte function overloading in PHP must be disabled for string functions (2).');
}

/**
 * SimpleReport_Autoloader
 *
 * @category	SimpleReport
 * @package		SimpleReport
 */
class SimpleReport_Autoloader {

	/**
	 * Register the Autoloader with SPL
	 *
	 */
	public static function Register() {
		if (function_exists('__autoload')) {
			//	Register any existing autoloader function with SPL, so we don't get any clashes
			spl_autoload_register('__autoload');
		}
		//	Register ourselves with SPL
		return spl_autoload_register(array('SimpleReport_Autoloader', 'Load'));
	}

//	function Register()

	/**
	 * Autoload a class identified by name
	 *
	 * @param	string	$pClassName		Name of the object to load
	 */
	public static function Load($pClassName) {
		if ((class_exists($pClassName)) || (strpos($pClassName, 'SimpleReport') !== 0)) {
			//	Either already loaded, or not a SimpleReport class request
			return FALSE;
		}

		$pObjectFilePath = SIMPLEREPORT_ROOT .
			str_replace('_', DIRECTORY_SEPARATOR, $pClassName) .
			'.php';

		if ((file_exists($pObjectFilePath) === false) || (is_readable($pObjectFilePath) === false)) {
			//	Can't load
			return FALSE;
		}
		
		require($pObjectFilePath);
	}

//	function Load()
}