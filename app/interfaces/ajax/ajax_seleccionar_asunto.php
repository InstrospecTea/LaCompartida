<?php
	require_once dirname(__FILE__).'/../../conf.php';
 
	$sesion = new Sesion();
	$pedazo = strtolower(utf8_decode($_GET['term']));
	$codigo_cliente = $_GET['codigo_cliente'];
	
	if(!$pedazo)
	{
		$query = "SELECT DISTINCT SUBSTRING(codigo_asunto, 6, 4) AS codigo_asunto
					FROM trabajo
					WHERE id_usuario='".$_POST['id_usuario']."'
					ORDER BY fecha DESC
					LIMIT 5";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$where = '';
		while(list($codigo_asunto) = mysql_fetch_array($resp))
		{
			$where .= "codigo_asunto='$codigo_asunto' OR ";
		}
		$where .= 0;
		$query = "SELECT codigo_asunto as id, glosa_asunto as value
				FROM asunto
				WHERE $where";
	} 	else if( Conf::GetConf($sesion,'CodigoSecundario'))  {
		if( $codigo_cliente != '0')
		{
			$query = "SELECT codigo_asunto_secundario as id, glosa_asunto as value
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					AND codigo_cliente = '$codigo_cliente'
					ORDER BY glosa_asunto
					LIMIT 10";
		}
		else
		{
			$query = "SELECT codigo_asunto_secundario as id, glosa_asunto as value
					FROM asunto
					WHERE activo=1 AND glosa_asunto LIKE '%$pedazo%'
					
					ORDER BY glosa_asunto
					LIMIT 10";
		}
	
	} else	{
		
		if($codigo_cliente) 	{
			$query = "SELECT codigo_asunto as id, glosa_asunto as value
					FROM asunto
					WHERE activo=1 AND lower(glosa_asunto) LIKE '%$pedazo%'
					AND codigo_cliente = '$codigo_cliente'
					ORDER BY glosa_asunto
					LIMIT 10";
		} 	else 		{
			$query = "SELECT codigo_asunto as id, glosa_asunto as value
					FROM asunto
					WHERE activo=1 AND lower(glosa_asunto) LIKE '%$pedazo%'
					
					ORDER BY glosa_asunto
					LIMIT 10";
		}
	}
 //echo $query;
	$resp = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
	
 	if(sizeof($resp)>0) {
 			
 			echo  json_encode(UtilesApp::utf8izar($resp));
 		
 	} else {
 		echo json_encode(array("id"=>"cualquiera", "value"=>__('Cualquiera')));
 	}
	

