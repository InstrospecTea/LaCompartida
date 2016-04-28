<?php

require_once dirname(__FILE__) . '/../ttbloader.php';

list($subdominio) = explode('.', $_SERVER['HTTP_HOST']);
ini_set('error_log', "/var/www/html/logs/{$subdominio}_error_log.log");

list($_SERVER['DUMMY'], $_SERVER['SUBDIR'], $_SERVER['SUBSUBDIR']) = explode('/', $_SERVER['REQUEST_URI']);

$script_url = $_SERVER['SCRIPT_NAME'];
$subdir = $_SERVER['SUBDIR'];

if (extension_loaded('newrelic')) {
	newrelic_capture_params();
	if ($subdir == 'juicios' || strpos($script_url, "juicios")) {
		newrelic_set_appname("Case Tracking");
	} else {
		newrelic_set_appname("The Time Billing");
	}
	newrelic_add_custom_parameter('subdominio', $subdominio);
	if (strpos($script_url, 'cron') || strpos($script_url, 'web_services')) {
		newrelic_ignore_apdex(true);
		newrelic_background_job(true);
		newrelic_disable_autorum();
	}
}

$llave = $subdominio . '.' . $subdir;

defined('LLAVE') || define('LLAVE', $llave);

if (!isset($memcache) || !is_object($memcache)) {
	$memcache = new Memcache;
	$memcache->connect('localhost', 11211);
}

if (!$result = @unserialize($memcache->get('teneninformation_' . $llave))) {
	$array_config = [
		'default_cache_config' => [
			[
				'host' => 'localhost',
				'port' => '11211'
			]
		]
	];
	try {
		$DynamoDb = new DynamoDb($array_config);
		$result = $DynamoDb->get([
			'TableName' => 'thetimebilling',
			'Key' => array('HashKeyElement' => array('S' => $llave))
		]);
	} catch (Exception $e) {
		echo 'The item could not be retrieved.';
	}

	$memcache->set('teneninformation_' . $llave, serialize($result), false, 90);
}

$result['dbpass'] = Utiles::decrypt($result['dbpass'], $result['backupdir']);

Conf::setStatic('dbHost', $result['dbhost']);
Conf::setStatic('dbName', $result['dbname']);
Conf::setStatic('dbUser', $result['dbuser']);
Conf::setStatic('dbPass', $result['dbpass']);


foreach ($result as $tipo => $valor) {
	$static = strtoupper($tipo);
	defined($static) || define($static, $valor);
}

if (defined('BACKUP') && (BACKUP == 3 || BACKUP == '3')) {
	include('offline.php');
	die();
}

defined('SUBDOMAIN') || define('SUBDOMAIN', $subdominio);
defined('DOCROOT') || define('DOCROOT', dirname(__FILE__));
defined('ROOTDIR') || define('ROOTDIR', $subdir);
defined('APPDOMAIN') || define('APPDOMAIN', DOMINIO);

if (defined('FILEPATH')) {
	$_SERVER['FILEPATH'] = FILEPATH;
	defined('APPPATH') || define('APPPATH', DOCROOT . '/' . FILEPATH);
} else {
	defined('APPPATH') || define('APPPATH', $_SERVER['DOCUMENT_ROOT'] . '/' . ROOTDIR);
}

$_SERVER['APPPATH'] = APPPATH;
$_SERVER['ROOTDIR'] = ROOTDIR;
$_SERVER['DBUSER'] = DBUSER;
$_SERVER['DBHOST'] = DBHOST;
$_SERVER['DBNAME'] = DBNAME;

// Let's Talk credentials
define('LT_KEY', 'Ija1Kg0AVx2B33P3AXdDdw');
define('LT_TOKEN', '5TspRDk1LbYL7jArFhQSVQ');
