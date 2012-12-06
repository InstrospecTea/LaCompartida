<?php

function autocargattb($class_name) {
	$class_name = str_replace('_' , DIRECTORY_SEPARATOR, $class_name);

	if (is_readable(APPPATH.'/app/classes/' . $class_name . '.php')) {
		require_once APPPATH.'/app/classes/'  . $class_name . '.php';
	} elseif (is_readable(APPPATH.'/fw/classes/' . $class_name . '.php')) {
		require_once APPPATH.'/fw/classes/' . $class_name . '.php';
	} else {
		return false;
	}
}
spl_autoload_register('autocargattb');

if (!class_exists('Slim') && is_readable(APPPATH . '/fw/classes/Slim/Slim.php')) {
	require_once APPPATH.'/fw/classes/Slim/Slim.php';
}