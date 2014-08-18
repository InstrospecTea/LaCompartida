<?php
$args = getopt('', array('script:', 'domain:', 'debug'));

define('__DIR__', dirname(__FILE__));
define('__BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = "{$args['domain']}.local";
$_SERVER['DOCUMENT_ROOT'] = __BASEDIR__;

require __BASEDIR__ . '/app/conf.php';
require __DIR__ . '/scripts/AppShell.php';
require __DIR__ . "/scripts/{$args['script']}.php";

$script = new $args['script'];
$script->debug = isset($args['debug']);
$script->main();
