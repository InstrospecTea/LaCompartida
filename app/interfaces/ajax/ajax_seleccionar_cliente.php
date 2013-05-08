<?php
	require_once dirname(__FILE__).'/../../conf.php';
	

	$sesion = new Sesion();
	$pedazo = strtolower(utf8_decode(addslashes($_POST['term'])));
	
$id=Conf::GetConf($sesion,'CodigoSecundario') ? 'codigo_secundario':'codigo_cliente';
$id_usuario=empty($_POST['id_usuario'])?  $sesion->usuario->fields['id_usuario']:$_POST['id_usuario'];
if(empty($pedazo)) 	{
		$query = "(select distinct cliente.{$id} as id, cliente.glosa_cliente as value
					from trabajo join asunto using (codigo_asunto) join cliente using (codigo_cliente)
					 WHERE trabajo.id_usuario = {$id_usuario}
					order by trabajo.fecha desc
					limit 0,5)   
					union
					(select distinct cliente.{$id}, cliente.glosa_cliente
					from  asunto join cliente using (codigo_cliente)
					order by asunto.fecha_creacion desc
					limit 0,5)    
  					"; 
                     
	} else {
			$query = "SELECT {$id} as id, glosa_cliente as value
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
	
