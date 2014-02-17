<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Archivo.php';

$sesion = new Sesion('');
$archivo  = new Archivo($sesion);

$archivo->Download($id_archivo);
?>
