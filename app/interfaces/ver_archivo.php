<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Archivo.php';

$sesion = new Sesion('');
$archivo  = new Archivo($sesion);

if(!empty($id_archivo))
{
	if($archivo->Load($id_archivo))
	{
		header("Content-type: ".$archivo->fields['data_tipo']);
		header('Content-Length: '.strlen($archivo->fields['archivo_data']));
		header("Content-Disposition: attachment; filename=\"".$archivo->fields['archivo_nombre']."\"");
		echo stripslashes($archivo->fields['archivo_data']);
	}
}
exit;
?>
