<?php

$args = getopt('', array('script:', 'domain:'));
define('__DIR__', dirname(__FILE__));
define('__BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = "{$args['domain']}.local";
require dirname(__DIR__) . '/conf.php';

if (!function_exists('autoload')) {

	function autoload($class_name) {
		$class_name = explode('\\', $class_name);
		$class_name = str_replace('_', DIRECTORY_SEPARATOR, end($class_name));

		if (is_readable(__DIR__ . "/scripts/{$class_name}.php")) {
			require_once __DIR__ . "/scripts/{$class_name}.php";
		} else if (is_readable(__BASEDIR__ . "/app/classes/{$class_name}.php")) {
			require_once __BASEDIR__ . "/app/classes/{$class_name}.php";
		} else if (is_readable(__BASEDIR__ . "/fw/classes/{$class_name}.php")) {
			require_once __BASEDIR__ . "/fw/classes/{$class_name}.php";
		} else {
			return false;
		}
	}

	spl_autoload_register('autoload');
}

require __DIR__ . "/scripts/{$args['script']}.php";

$script = new $args['script'];
$script->main();