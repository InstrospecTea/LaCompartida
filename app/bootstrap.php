<?php

require_once dirname(__FILE__) . '/conf.php';

define('APP_PATH', dirname(__FILE__));
define('ROOT_PATH', dirname(APP_PATH));
define('LAYER_PATH', APP_PATH . '/layers');

$uri = str_replace(dirname($_SERVER['PHP_SELF']), '', $_SERVER['REQUEST_URI']);
$uri = preg_replace('/^\/|\?.*/', '', $uri);

$parse_url = parse_url($_SERVER['REQUEST_URI']);
parse_str($parse_url['query'], $_GET);

$exploded_uri = explode('/', $uri);
$route['controller'] = array_shift($exploded_uri);
$route['method'] = array_shift($exploded_uri);

$auri = array_merge(
	array('controller' => 'Abstract', 'method' => 'index'),
	array_filter($route)
);

new ControllerLoader($auri['controller'], $auri['method'], array_filter($exploded_uri));
