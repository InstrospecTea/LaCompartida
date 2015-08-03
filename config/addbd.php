<?php

list($subdominio)=explode('.',$_SERVER['HTTP_HOST']);

ini_set('error_log', "/var/www/html/logs/{$subdominio}_error_log.log");

if( $_SERVER['REMOTE_ADDR'] == '152.231.82.18' && $subdominio == 'palcantarcol' ) {
	ini_set('display_errors', 'On');
	error_reporting(E_ERROR);

} else {
	ini_set('display_errors', 'Off');
}

list($_SERVER['DUMMY'],$_SERVER['SUBDIR'],$_SERVER['SUBSUBDIR']) = explode('/', $_SERVER['REQUEST_URI']);

$script_url = $_SERVER['SCRIPT_NAME'];
$subdir     = $_SERVER['SUBDIR'];

if (extension_loaded('newrelic')) {
	newrelic_capture_params();

	if ($subdir == 'juicios' || strpos($script_url, "juicios")) {
			newrelic_set_appname("Case Tracking");
	} else {
		newrelic_set_appname("The Time Billing");
	}

	newrelic_add_custom_parameter ('subdominio', $subdominio);

	if( strpos($script_url, 'cron') || strpos($script_url, 'web_services') ) {
		newrelic_ignore_apdex(true);
		newrelic_background_job(true);
		//newrelic_ignore_transaction();
		newrelic_disable_autorum();
	}
}

if( $_SERVER['DOCUMENT_ROOT'] . $script_url == __FILE__ ) {
	header('HTTP/1.0 403 Forbidden');
	echo '<div style="margin:50px auto;text-align:center;font-family:Arial;">';
	echo '<h2>Error 403</h2>';
	echo '<img  src="//static.thetimebilling.com/cartas/img/lemontech_logo400.png"  style="margin:auto;width:400px;height:126px;display:block;"  alt="Lemontech"/> ';
	echo '<h4>No se puede acceder directamente a este script</h4></div>';
	die();
}

defined('SUBDOMAIN') || define('SUBDOMAIN', $subdominio);
defined('DOCROOT') || define('DOCROOT', dirname(__FILE__));
defined('ROOTDIR') || define('ROOTDIR', $subdir);

$llave = $subdominio . '.' . $subdir;

defined('LLAVE') || define('LLAVE',$llave);

if( !isset($memcache) || !is_object($memcache) ) {
	$memcache = new Memcache;
	$memcache->connect('ttbcache.tmcxaq.0001.use1.cache.amazonaws.com', 11211);
}

if (!function_exists('decrypt')) {
	function decrypt($msg, $k) {

		$msg = base64_decode($msg);
		$k = substr($k, 0, 32);
		# open cipher module (do not change cipher/mode)
		if (!$td = mcrypt_module_open('rijndael-256', '', 'ctr', ''))
			return false;

		$iv = substr($msg, 0, 32); // extract iv
		$mo = strlen($msg) - 32; // mac offset
		$em = substr($msg, $mo); // extract mac
		$msg = substr($msg, 32, strlen($msg) - 64); // extract ciphertext

		if (@mcrypt_generic_init($td, $k, $iv) !== 0)
			return false;

		$msg = mdecrypt_generic($td, $msg);
		$msg = unserialize($msg);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $msg;
	}
}

use Aws\Common\Aws;
use Aws\DynamoDb\Exception\DynamoDbException;

$arrayconfig=array(
   // 'includes' => array('_aws', '_sdk1'),
	'key'    => 'AKIAIQYFL5PYVQKORTBA',
	'secret' => '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',
	'region' => 'us-east-1',

	'default_cache_config' =>  array(
		array(
			'host' => 'ttbcache.tmcxaq.0001.use1.cache.amazonaws.com',
			'port' => '11211'
		)
	)
);

$aws = Aws::factory($arrayconfig);

if( ! $dynamodbresponse = @unserialize( $memcache->get('dynamodbresponse_' . $llave) ) ) {
	try {
		$dynamodb = $aws->get('dynamodb');

		$result = $dynamodb->getItem(array(
			'TableName' => 'thetimebilling',
			'Key'       => array( 'HashKeyElement' => array('S' => $llave ))
		));

		$dynamodbresponse = $result['Item']; //$response->body->Item->to_array();

	} catch (Exception $e) {
		print_r($e);

	} catch (DynamoDbException $e) {
		echo 'The item could not be retrieved.';
	}

	$memcache->set( 'dynamodbresponse_'.$llave, serialize($dynamodbresponse), false, 90);

}

foreach ($dynamodbresponse as $tipo => $valor) {
	if (is_string($valor)) {
		$dynamodbresponse[$llave][strtoupper($tipo)] = $valor;
	} else if (isset($valor['S'])) {
		$dynamodbresponse[$llave][strtoupper($tipo)] = $valor['S'];
	} else if (isset($valor['N'])) {
		$dynamodbresponse[$llave][strtoupper($tipo)] = $valor['N'];
	}

	if (strtoupper($tipo) == 'DBPASS') {
		define('DBPASS', decrypt($dynamodbresponse[$llave]['DBPASS'], BACKUPDIR));
	}

	defined(strtoupper($tipo)) || define(strtoupper($tipo), $dynamodbresponse[$llave][strtoupper($tipo)]);
}

if (defined('BACKUP') && (BACKUP == 3 || BACKUP == '3')) {
	include('offline.php');
	die();
}

defined('APPDOMAIN') || define('APPDOMAIN', DOMINIO);

if (defined('FILEPATH')) {
	$_SERVER['FILEPATH'] = FILEPATH;
	defined('APPPATH') || define('APPPATH', DOCROOT . '/' . FILEPATH);

} else {
	defined('APPPATH') || define('APPPATH', $_SERVER['DOCUMENT_ROOT'] . '/' . ROOTDIR);
}

$_SERVER['APPPATH'] = APPPATH;
$_SERVER['ROOTDIR'] = ROOTDIR;
$_SERVER['DBUSER']  = DBUSER;
$_SERVER['DBHOST']  = DBHOST;
$_SERVER['DBNAME']  = DBNAME;

if (!function_exists('apache_setenv')) {
	function apache_setenv() {
		return;
	}
}


