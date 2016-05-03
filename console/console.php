<?php

$args = getopt('', array('script:', 'domain:', 'subdir:', 'data:', 'debug'));

if (!isset($args['script'])) {
	listScripts();
	exit("use: console script_name [--domain=dev] [--subdir=ttb] [--data='json_data'] [--debug]\n");
}

if (!isset($args['domain'])) {
	$args['domain'] = 'dev';
}

if (!isset($args['subdir'])) {
	$args['subdir'] = 'ttb';
}

define('__DIR__', dirname(__FILE__));
define('__BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = "{$args['domain']}.local";
$_SERVER['DOCUMENT_ROOT'] = dirname(__BASEDIR__);
$_SERVER['REQUEST_URI'] = "/{$args['subdir']}/";

require __BASEDIR__ . '/app/conf.php';
require __DIR__ . '/scripts/AppShell.php';
require __DIR__ . "/scripts/{$args['script']}.php";

$class_name = Utiles::pascalize($args['script']);

$script = new $class_name;

if (isset($args['data'])) {
	$script->data = json_decode($args['data'], true);
	if ($script->data === null) {
		$script->out('El argumento --data no tiene un json valido');
		exit;
	}
}

$script->debug = isset($args['debug']);
$script->main();

function listScripts() {
	echo "Script names:\n";
	echo "=============\n";
	$files = scandir(dirname(__FILE__) . '/scripts');
	foreach ($files as $file) {
		if ($file !== 'AppShell.php' && preg_match('/\.php$/i', $file)) {
			echo preg_replace('/^([^\.]+).*$/', '$1', $file) . "\n";
		}
	}
	echo "\n";
}
