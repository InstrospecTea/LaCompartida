<?php

function autocargattb($class_name) {
		$class_name = explode('\\', $class_name);
		$class_name = str_replace('_', DIRECTORY_SEPARATOR,  end($class_name));

	if (is_readable(dirname(__FILE__) . '/../app/classes/' . $class_name . '.php')) {
		require_once dirname(__FILE__) . '/../app/classes/' . $class_name . '.php';
	} else if (is_readable(dirname(__FILE__) . '/../fw/classes/' . $class_name . '.php')) {
		require_once dirname(__FILE__) . '/../fw/classes/' . $class_name . '.php';
	} else {
		return false;
	}
}

spl_autoload_register('autocargattb');

if (!class_exists('Slim') && is_readable(dirname(__FILE__) . '/../fw/classes/Slim/Slim.php')) {
	require_once dirname(__FILE__) . '/../fw/classes/Slim/Slim.php';
}