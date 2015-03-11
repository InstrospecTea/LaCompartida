<?php

require_once dirname(__FILE__) . '/conf.php';

define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', dirname(__FILE__));
define('LAYER_PATH', dirname(__FILE__) . '/layers');

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

$class_name = "{$auri['controller']}Controller";
$filename = LAYER_PATH . "/controller/{$class_name}.php";
if (!file_exists($filename)) {
	die('404 File not found!');
}
require_once(LAYER_PATH . '/controller/AbstractController.php');
require_once($filename);
$instance = new $class_name;
$instance->_dispatch($auri['method'], array_filter($exploded_uri));
