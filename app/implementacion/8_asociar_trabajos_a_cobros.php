<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';
	require_once Conf::ServerDir().'/../app/classes/NeteoDocumento.php';
		
	$sesion = new Sesion();
	$cobro = new Cobro($sesion);

		set_time_limit(1000);
		
		$query = "SELECT id_cobro, codigo_asunto, fecha_cobro 
								FROM cobro 
							LEFT JOIN asunto ON cobro.id_contrato=asunto.id_contrato 
							ORDER BY fecha_cobro, id_cobro ASC "; 
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		
		while( $row = mysql_fetch_assoc($resp) )
		{
			$query2 = "UPDATE trabajo SET id_cobro = ".$row['id_cobro']." 
									WHERE codigo_asunto = '".$row['codigo_asunto']."' 
										AND fecha <= '".$row['fecha_cobro']."' 
										AND ( id_cobro IS NULL OR id_cobro = '' )";
			mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
			
			echo mysql_affected_rows().' trabajos affectados !! ----  ';
		}
	
?>
