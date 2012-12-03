<?php
//ini_set('display_errors', 'On');
date_default_timezone_set('America/Santiago');
require_once '/var/www/html/addbd.php';
function autocargattb($class_name) {
	$class_name=str_replace('_', DIRECTORY_SEPARATOR,$class_name);

	if (is_readable(APPPATH.'/app/classes/' . $class_name . '.php')) {
		require_once APPPATH.'/app/classes/'  . $class_name . '.php';
	} else if (is_readable(APPPATH.'/fw/classes/' . $class_name . '.php')) {
		require_once APPPATH.'/fw/classes/' . $class_name . '.php';
	} else {
		return false;
	}
}
spl_autoload_register('autocargattb');

if (!class_exists('Slim') && is_readable(APPPATH.'/fw/classes/Slim/Slim.php')) 		require_once APPPATH.'/fw/classes/Slim/Slim.php';
if (!is_object($memcache)) {
		$memcache = new Memcache;
		$memcache->connect('ttbcache.tmcxaq.0001.use1.cache.amazonaws.com', 11211);
	}

defined('ROOTDIR') || define('ROOTDIR',str_replace('//','/','/'.Conf::RootDir()));
defined('DBUSER') || define('DBUSER',Conf::dbUser());
defined('DBHOST') || define('DBHOST',Conf::dbHost());
defined('DBNAME') || define('DBNAME',Conf::dbName());
defined('DBPASS') || define('DBPASS',Conf::dbPass());
defined('BACKUPDIR') || define('BACKUPDIR','/tmp');
defined('USERWS') || define('USERWS',Conf::PasswordWS());
defined('PASSWS') || define('PASSWS',Conf::UsuarioWS());




require_once APPPATH.'/fw/funciones/funciones.php';
require_once APPPATH.'/app/lang/es.php';		//Para que cargue el idioma por defecto
require_once APPPATH.'/app/lang/abogado.php';	//Por si hay palabras especificas relacionadas con el rubro
?>
