<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/implementacion/0_instrucciones_y_configuraciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';
	$sesion = new Sesion();
	
	ini_set("memory_limit","128M");
		
	$query = "SELECT id_cobro FROM cobro";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,$sesion->dbh);
	
	while( list($id_cobro) = mysql_fetch_array($resp) )
	{
		$query = "SELECT
					group_concat(idDocLegal) as listaDocLegal
					FROM (
					SELECT
					 CONCAT(if(f.id_documento_legal != 0, if(f.letra is not null, if(f.letra != '',concat('LETRA ',f.letra), CONCAT(p.codigo,' ',f.numero)), CONCAT(p.codigo,' ',f.numero)), ''),IF(f.anulado=1,'(ANULADO)','')) as idDocLegal
					,f.id_cobro
					FROM factura f, prm_documento_legal p
					WHERE f.id_documento_legal = p.id_documento_legal
					AND id_cobro = '".$id_cobro."'
					)zz
					GROUP BY id_cobro";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,$sesion->dbh);

		while( list($listaDocLegal) = mysql_fetch_array($resp) )
		{
			$query = "UPDATE TABLE cobro SET documento = '".$listaDocLegal."'";
		}

	}
	
	
	
?>
