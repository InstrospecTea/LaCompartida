<?php
$args = getopt('', array('script:', 'domain:', 'subdir:', 'debug'));

if (!isset($args['script']) || !isset($args['domain']) || !isset($args['subdir'])) {
	exit("use: console script_name --domain=dev --subdir=ttb [--debug]\n");
}

define('__DIR__', dirname(__FILE__));
define('__BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = "{$args['domain']}.local";
$_SERVER['DOCUMENT_ROOT'] = dirname(__BASEDIR__);
$_SERVER['REQUEST_URI'] = "/{$args['subdir']}/";

require __BASEDIR__ . '/app/conf.php';
require __DIR__ . '/scripts/AppShell.php';
require __DIR__ . "/scripts/{$args['script']}.php";

$class_name = \TTB\Utiles::pascalize($args['script']);

$script = new $class_name;
$script->debug = isset($args['debug']);
$script->main();
