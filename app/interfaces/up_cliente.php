<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
  require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
  require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	
	$sesion = new Sesion(array('DAT'));
	
	$pagina = new Pagina($sesion);
	
	$query = "SELECT id_cliente, id_contrato, codigo_cliente FROM cliente WHERE (id_contrato IS  NULL || id_contrato = '') ORDER BY id_cliente";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while( list($id_cliente, $id_contrato, $codigo_cliente) = mysql_fetch_array($resp) )
	{
		$query_insert = "INSERT INTO `contrato` SET activo = 'SI', codigo_cliente = '".$codigo_cliente."',	
		fecha_creacion = NOW(), fecha_modificacion = NOW()";
		$resp_insert = mysql_query($query_insert, $sesion->dbh) or Utiles::errorSQL($query_insert,__FILE__,__LINE__,$sesion->dbh);
		$id = mysql_insert_id($sesion->dbh);
		
		$query_up = "UPDATE cliente SET id_contrato = $id WHERE codigo_cliente = '".$codigo_cliente."' LIMIT 1";
		$resp_up = mysql_query($query_up, $sesion->dbh) or Utiles::errorSQL($query_up,__FILE__,__LINE__,$sesion->dbh);
		
		echo $id_cliente.':'.$codigo_cliente.' >         Id: contrato:'.$id.'<br>';
	}
?>