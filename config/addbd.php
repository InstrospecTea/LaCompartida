<?php

require_once dirname(__FILE__) . '/../ttbloader.php';

if ($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'] == __FILE__) {
	header('HTTP/1.0 403 Forbidden');
	echo '<div style="margin:50px auto;text-align:center;font-family:Arial;">';
	echo '<h2>Error 403</h2>';
	echo '<img  src="//static.thetimebilling.com/cartas/img/lemontech_logo400.png" style="margin:auto;width:400px;height:126px;display:block;"  alt="Lemontech"/> ';
	echo '<h4>No se puede acceder directamente a este script</h4></div>';
	die();
}


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
	$memcache->connect('ttbcache.tmcxaq.0001.use1.cache.amazonaws.com', 11211);
}

use Aws\DynamoDb\Exception\DynamoDbException;

if (!$result = @unserialize($memcache->get('teneninformation_' . $llave))) {
	try {
		$array_config = ['default_cache_config' => [
				[
					'host' => 'ttbcache.tmcxaq.0001.use1.cache.amazonaws.com',
					'port' => '11211'
				]
			]
		];
		$DynamoDB = new DynamoDB($array_config);
		$result = $DynamoDB->get(array(
			'TableName' => 'thetimebilling',
			'Key' => array('HashKeyElement' => array('S' => $llave))
		));
	} catch (Exception $e) {
		echo 'The item could not be retrieved.';
	} catch (DynamoDbException $e) {
		echo 'The item could not be retrieved.';
	}

	$memcache->set('teneninformation_' . $llave, serialize($result), false, 90);
}

$result['dbpass'] = Utiles::decrypt($result['dbpass'], $result['backupdir']);
foreach ($result as $tipo => $valor) {
	defined(strtoupper($tipo)) || define(strtoupper($tipo), $valor);
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
