<?php
require_once __DIR__ . '/vendor/autoload.php';

$directorios = array(
	'/app/classes/',
	'/app/classes/Graficos/',
	'/app/layers/business.support/',
	'/app/layers/business/',
	'/app/layers/dao.framework/',
	'/app/layers/dao/',
	'/app/layers/dao/exceptions/',
	'/app/layers/dto/',
	'/app/layers/report.support/',
	'/app/layers/report.support/calculators/',
	'/app/layers/report.support/engine/',
	'/app/layers/report.support/filters/',
	'/app/layers/report.support/groupers/',
	'/app/layers/report/',
	'/app/layers/report/calculators/',
	'/app/layers/report/engine/',
	'/app/layers/report/filters/',
	'/app/layers/report/groupers/',
	'/app/layers/scope.support/',
	'/app/layers/scope/',
	'/app/layers/service.support/',
	'/app/layers/service/',
	'/app/layers/utilities/',
	'/app/layers/utilities/twig/',
	'/app/layers/view/helpers/',
	'/database/lib/'
	'/fw/classes/',
);

function autocargattb($class_name) {
	global $directorios;

	$class_name = explode('\\', $class_name);
	$class_name = str_replace('_', DIRECTORY_SEPARATOR,  end($class_name));

	foreach ($directorios as $directorio) {
		if (is_readable(dirname(__FILE__) . $directorio . $class_name . '.php')) {
			require_once dirname(__FILE__) . $directorio . $class_name . '.php';
			break;
		}
	}
}

spl_autoload_register('autocargattb');

if (!class_exists('Slim') && is_readable(dirname(__FILE__) . '/fw/classes/Slim/Slim.php')) {
	require_once dirname(__FILE__) . '/fw/classes/Slim/Slim.php';
}
