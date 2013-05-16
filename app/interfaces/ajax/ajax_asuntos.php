<?php
require_once dirname(dirname(dirname(__FILE__))).'/conf.php';

$sesion = new Sesion();
 $mostrar_codigo_asuntos = "asunto.glosa_asunto";
		if (UtilesApp::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
			$mostrar_codigo_asuntos = "concat(asunto.codigo_asunto,' ',asunto.glosa_asunto)";
			if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
				$mostrar_codigo_asuntos = "concat(asunto.codigo_asunto_secundario,' ',asunto.glosa_asunto)";
			}
		}
$AsuntosContrato=array();
		
		if(intval($_GET['id_contrato'])>0) {
			$prequery="select ".intval($_GET['id_contrato'])." as id_contrato, $mostrar_codigo_asuntos as asuntos from  asunto where id_contrato=".intval($_GET['id_contrato'])." order by activo DESC, codigo_asunto ASC";
		} else {
			$prequery="select id_contrato,  $mostrar_codigo_asuntos as asuntos from contrato left join asunto using (id_contrato) ";
			
		}
		//debug($prequery);
		$asuntosST=$sesion->pdodbh->query($prequery);
		$asuntosRS=$asuntosST->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
	foreach ($asuntosRS as $contrato=>$asuntos) {
		
		$AsuntosContrato[$contrato]=  array_map( function($t){ return is_string($t) ? utf8_encode($t) : $t; }, $asuntos );
	}
 
echo json_encode($AsuntosContrato);