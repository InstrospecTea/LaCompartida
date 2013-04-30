<?php
	require_once dirname(__FILE__).'/../../conf.php';
	

	$sesion = new Sesion();
	$pedazo = strtolower(utf8_decode(addslashes($_GET['term'])));
	
if(!$pedazo) 	{
		$query = "SELECT DISTINCT tabla1.codigo_cliente as id, tabla1.glosa_cliente  as label, tabla1.glosa_cliente  as value FROM
                            (
                                SELECT 
                                SUBSTRING( trabajo.codigo_asunto, 1, 4 ) AS codigo_cliente, 
                                cliente.glosa_cliente as glosa_cliente,
                                trabajo.fecha, 
                                id_trabajo 
                                FROM trabajo  
                                JOIN cliente ON cliente.codigo_cliente = SUBSTRING( codigo_asunto, 1, 4 ) 
                                WHERE trabajo.id_usuario = '".$_POST['id_usuario']."' 
                                ORDER BY trabajo.fecha DESC, id_trabajo DESC 
                                LIMIT 30
                            ) as tabla1
                            LIMIT 5"; 
	} else if(  Conf::GetConf($sesion,'CodigoSecundario') )	{
		$query = "SELECT codigo_cliente_secundario as id, glosa_cliente as label, glosa_cliente as value
				FROM cliente
				WHERE activo=1 AND lcase(glosa_cliente) LIKE '%$pedazo%' 
				ORDER BY glosa_cliente
				LIMIT 10";
	
	} else 	{
		$query = "SELECT codigo_cliente as id, glosa_cliente as label, glosa_cliente as value
				FROM cliente
				WHERE activo=1 AND lcase(glosa_cliente) LIKE '%$pedazo%'
				ORDER BY glosa_cliente
				LIMIT 10";
	}
 	
	$resp = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
	
 	if(sizeof($resp)>0) {
 		
 		 echo  json_encode(UtilesApp::utf8izar($resp));

 		
 	} else {
 		echo json_encode(array("id"=>"cualquiera", "value"=>__('Cualquiera')));
 	}
	
