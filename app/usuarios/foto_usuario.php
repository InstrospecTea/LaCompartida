<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';

    $sesion = new Sesion('' );
    $pagina = new Pagina($sesion);

	$query = "SELECT tipo_foto, data_foto FROM usuario_foto WHERE rut_usuario = '$rut'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($tipo, $data) = mysql_fetch_array($resp);

	if($tipo != "")
	{
		header ("Content-type: $tipo");
		echo($data);
		exit;
	}
	else
	{
		$dataFile = fopen("../../templates/img/pix.gif","r");
		if ( $dataFile )
		{
			header ("Content-type: image/gif");
			while (!feof($dataFile))
			{
				$buffer = fgets($dataFile, 4096);
				echo $buffer;
			}
			fclose($dataFile);
		}
		else
		{
			$pagina->AddError( "fopen ha fallado" ) ;
		}
	}
