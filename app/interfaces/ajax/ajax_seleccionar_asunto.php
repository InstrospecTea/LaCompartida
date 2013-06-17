<?php
	require_once dirname(__FILE__).'/../../conf.php';
 
	$sesion = new Sesion();
	$pedazo = strtolower(utf8_decode($_POST['term']));
	$codigo_cliente = $_POST['codigo_cliente'];
	$id=Conf::GetConf($sesion,'CodigoSecundario') ? 'codigo_asunto_secundario':'codigo_asunto';
	$campocodigocliente=Conf::GetConf($sesion,'CodigoSecundario') ? 'codigo_cliente_secundario':'codigo_cliente';

	$id_usuario=empty($_POST['id_usuario'])?  $sesion->usuario->fields['id_usuario']:$_POST['id_usuario'];
	

	if(empty($pedazo)) 	{



		$query = "(select distinct asunto.{$id}  as id, asunto.glosa_asunto as value
				from trabajo join asunto using (codigo_asunto)
				 WHERE trabajo.id_usuario = {$id_usuario}
				order by trabajo.fecha desc
				limit 0,5)   
				union
				(select distinct asunto.{$id}  as id, asunto.glosa_asunto as value
				from  asunto  
				order by asunto.fecha_creacion desc
				limit 0,5)    
				 ";
	
	
	} else	{
		
		 
			$query = "SELECT $id as id, glosa_asunto as value
						FROM asunto
						WHERE activo=1 AND lower(glosa_asunto) LIKE '%$pedazo%'";
			if($codigo_cliente) {
					$query .="	AND {$campocodigocliente} = '$codigo_cliente'";	
			} 
			$query .="ORDER BY glosa_asunto		LIMIT 10";
	 
	}
 //echo $query;
	$resp = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
	
 	if(sizeof($resp)>0) {
 			
 			echo  json_encode(UtilesApp::utf8izar($resp));
 		
 	} else {
 		echo json_encode(array("id"=>"cualquiera", "value"=>__('Cualquiera')));
 	}
	


