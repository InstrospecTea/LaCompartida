<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once dirname(__FILE__).'/../classes/AlertaCron.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion (null, true);
	$alerta = new Alerta ($sesion);

	$query = "SELECT id_log_correo, subject, mensaje, mail, nombre FROM log_correo WHERE enviado=0";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

	while(list($id, $subject, $mensaje, $mail, $nombre)=mysql_fetch_array($resp))
	{
		$correos=array();
		$correo=array( 'nombre' => $nombre, 'mail' => $mail );
		array_push($correos,$correo);
		if(Utiles::EnviarMail($sesion,$correos,$subject,$mensaje))
		{
			$query2 = "UPDATE log_correo SET enviado=1 WHERE id_log_correo=".$id;
			$resp2 = mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
		}
	}
?>
