<?php

require_once("../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
$sesion = new Sesion();
header('Content-Type: text/javascript; charset=utf8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 3628800');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require 'Slim/Slim.php';

$slimttb = new Slim();
$slimttb->config('debug', false);


$slimttb->map('/EntregarListaClientes(/:callback)', 'EntregarListaClientes')->via('GET', 'POST');

	function EntregarListaClientes($callback='') {
            global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
				$timestamp=$esteslim->request()->post('timestamp');
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			$lista_clientes = array();
                        
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
				if ( UtilesApp::GetConf($sesion,'CodigoSecundario') )
				{
					$select_codigo="codigo_cliente_secundario as codigo_cliente";
				} 	else	{
					$select_codigo="codigo_cliente";
				}
				$query = "SELECT $select_codigo ,glosa_cliente, unix_timestamp(fecha_touch) as fechatouch FROM cliente  WHERE activo='1' ORDER BY glosa_cliente";
				if(!($resp = mysql_query($query, $sesion->dbh) )) 	die('["Error SQL."] '.mysql_error());
				while( list($cod,$client,$fechatouch) = mysql_fetch_array($resp) )
				{
                                        $cliente=array();
					$cliente['codigo'] = $cod;
					$cliente['glosa'] = utf8_encode($client);
					$cliente['codigo_padre'] = "";
                                        $cliente['fecha_touch'] = date("Y-m-d",$fechatouch);
                                    if(!$timestamp || ($timestamp && $fechatouch>($timestamp-86400))) array_push($lista_clientes,$cliente);	
				   
				}
				if($callback!='') {
				echo $callback.' ('.json_encode($lista_clientes).');';
				} else {
				echo json_encode($lista_clientes);
				}
			
	}

$slimttb->map('/EntregarListaAsuntos(/:callback)', 'EntregarListaAsuntos')->via('GET', 'POST');

	function EntregarListaAsuntos($callback='') {
            global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
				$timestamp=$esteslim->request()->post('timestamp');
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			$lista_asuntos = array();
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
				if ( UtilesApp::GetConf($sesion,'CodigoSecundario') )
				{
					$select_codigo="asunto.codigo_asunto_secundario,cliente.codigo_cliente_secundario";
				} 	else	{
					$select_codigo="asunto.codigo_asunto , cliente.codigo_cliente";
				}
				$query = "SELECT $select_codigo ,asunto.glosa_asunto,unix_timestamp(greatest(cliente.fecha_touch, asunto.fecha_touch)) as fechatouch FROM asunto 
								JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
								WHERE asunto.activo=1 AND cliente.activo=1 ORDER BY asunto.glosa_asunto";
		
				
		
				
				if(!($resp = mysql_query($query, $sesion->dbh) )) 	die('["Error SQL."]'.mysql_error());
				while( list($codigoasunto,$codigocliente,$glosa_asunto,$fechatouch) = mysql_fetch_array($resp) )
				{
				    if(!$timestamp || ($timestamp && $fechatouch>($timestamp-86400))):
					$asunto=array();
                                        $asunto['codigoasunto'] = $codigoasunto;
					$asunto['codigocliente'] = $codigocliente;
					$asunto['glosaasunto'] = utf8_encode($glosa_asunto);
					$asunto['fecha_touch'] = date("Y-m-d",$fechatouch);
					array_push($lista_asuntos,$asunto);
				    endif;
					
				}
				if($callback!='') {
				echo $callback.' ('.json_encode($lista_asuntos).');';
				} else {
				echo json_encode($lista_asuntos);
				}
			
	}


//POST route
$slimttb->map('/EntregarDatos(/:callback)', 'EntregarDatos')->via('GET', 'POST');

    function EntregarDatos($callback='') {
		 global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
				$timestamp=$esteslim->request()->post('timestamp');
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			$lista_asuntos = array();
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
			
			$queryuser="select id_usuario, nombre, apellido1 from usuario where rut='$usuario'";
		
			 list($idusuario,$nombre,$apellido) = mysql_fetch_row(mysql_query($queryuser, $sesion->dbh) );
			
			
	if($callback!='') {
		echo $callback.' ('.json_encode(array($idusuario,$nombre,$apellido,UtilesApp::GetConf($sesion,'Intervalo'),Conf::AppName() )).');';
		} else {
		echo json_encode( array($idusuario,$nombre,$apellido,UtilesApp::GetConf($sesion,'Intervalo'),Conf::AppName() )  );
		}
}

//POST route
$slimttb->map('/EntregarDatosClientes(/:callback)', 'EntregarDatosClientes')->via('GET', 'POST');

    function EntregarDatosClientes($callback='') {
		 global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
				 
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			$usuarios=array();
		
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
			
			
		
	   $queryuser = "SELECT id_usuario, nombre, apellido1, apellido2, u.id_categoria_usuario, id_categoria_lemontech, u.activo
                            FROM usuario u JOIN prm_categoria_usuario p 
                            ON u.id_categoria_usuario = p.id_categoria_usuario                            ";   
			
			$respuser=mysql_query($queryuser, $sesion->dbh) or die(mysql_error());
			
			
			while($fila=mysql_fetch_assoc($respuser )) {
			$usuarios[]=$fila;
			}
		
	 echo json_encode($usuarios);
}


//POST route
$slimttb->map('/DatosPanel(/:callback)', 'DatosPanel')->via('GET', 'POST');

    function DatosPanel($callback='') {
		 global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
			 
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			$lista_datos = array();
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
			
			$querydatos="select * from

(select count(*) as gastos from cta_corriente) cc,
(select count(*) as tramites from tramite) tram,
(SELECT sum( if( estado != 'CREADO' && estado != 'EN REVISION', 1, 0 ) ) AS emitidos, sum( if( estado = 'PAGADO', 1, 0 ) ) AS pagados
FROM cobro) cobros,
(select count(*) as facturas from factura) fc,

(select 
sum(if(tr.fecha>=fechas.inicio_ano,time_to_sec(tr.duracion)/3600,0)) as HH_ANO,
sum(if(tr.fecha>=fechas.inicio_mes and tr.fecha<=fechas.fin_mes,time_to_sec(tr.duracion)/3600,0)) as HH_MES,
sum(if(tr.fecha>=fechas.iniciosemana and tr.fecha<=fechas.finsemana,time_to_sec(tr.duracion)/3600,0)) as HH_SEMANA

from trabajo tr,
(select 
YEAR(CURDATE())*10000+101 inicio_ano,
date_format(LAST_DAY(now() - interval 1 month),'%Y%m%d') fin_mes,
concat(date_format(LAST_DAY(now() - interval 1 month),'%Y%m'),'01') inicio_mes,
 date_format(subdate(now(), INTERVAL 7+weekday(now()) DAY),'%Y%m%d') iniciosemana,
date_format(subdate(now(), INTERVAL 1+weekday(now()) DAY),'%Y%m%d') finsemana) fechas) trabajos";
		
			 $respuesta = mysql_fetch_assoc(mysql_query($querydatos, $sesion->dbh) );
			
			
 
		echo json_encode( $respuesta );
		 
}


$slimttb->map('/CargarTrabajo(/:callback)', 'CargarTrabajo')->via('GET', 'POST');

    function CargarTrabajo($callback='') {
		 global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');
				$starttimer=$esteslim->request()->post('starttimer');
				$idescritorio=$esteslim->request()->post('idescritorio');
				$codigo_asunto=$esteslim->request()->post('codigoasunto');
				$duracion=intval($esteslim->request()->post('duracion'));
				$descripcion=utf8_decode($esteslim->request()->post('descripcion'));
				$ordenado_por=$esteslim->request()->post('userid');
				$fecha=date('Y-m-d',strtotime($starttimer/1000)+86400);
				$id_trabajo_local=$ordenado_por.$starttimer;
				$codigo_actividad = "";
				$id_moneda = "";
			
				
		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
			
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$query_codigo="SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario='$codigo_asunto'";
			if(!($resp_codigo = mysql_query($query_codigo, $sesion->dbh) ))				die(mysql_error());
			list($codigo_asunto) = mysql_fetch_array($resp_codigo);
		}
		
		$query = "SELECT contrato.id_moneda, asunto.cobrable FROM contrato JOIN asunto on asunto.id_contrato = contrato.id_contrato WHERE codigo_asunto='$codigo_asunto'";
		if(!($resp = mysql_query($query, $sesion->dbh) ))			die(mysql_error());
		list($id_moneda, $cobrable) = mysql_fetch_array($resp);

		$minutos = $duracion / 60;
		$min = intval($minutos % 60);
		$hora = intval(($minutos - $min) / 60);
		$min = $min < 10 ? "0".$min : $min; 
		$hora = $hora < 10 ? "0".$hora : $hora; 

		$query = "SELECT id_usuario, id_categoria_usuario ,dias_ingreso_trabajo FROM usuario WHERE rut='$usuario'";
		if(!($resp = mysql_query($query, $sesion->dbh) )) die(mysql_error());
			
		list($id_usuario, $id_categoria_usuario, $dias_ingreso_trabajo) = mysql_fetch_array($resp);

		if($codigo_actividad == "")
			$codigo_actividad = "NULL";
		else
			$codigo_actividad = "'$codigo_actividad'";

		if($id_moneda == "")
			$id_moneda = "1";
		else
			$id_moneda = "'$id_moneda'";
		//Todo a mayusculas segun conf
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TodoMayuscula') ) || ( method_exists('Conf','TodoMayuscula') && Conf::TodoMayuscula() ) )
		{
			$descripcion = strtoupper($descripcion);
			$ordenado_por = strtoupper($ordenado_por);
		}

		$fecha_antigua=$fecha;
		if( $dias_ingreso_trabajo > 0 ) if(strtotime($fecha) < mktime(0,0,0,date("m"),date("d")-$dias_ingreso_trabajo,date("Y")))	$fecha=date("Y-m-d");

		$id_area_trabajo = !empty($area_trabajo) ? "'$area_trabajo'" : "NULL";
			
		$descripcion=addslashes($descripcion);
		$ordenado_por=addslashes($ordenado_por);
		$query = "INSERT INTO trabajo SET 
								id_usuario='$id_usuario',
                                                                id_categoria_usuario='$id_categoria_usuario',
								id_trabajo_local='$id_trabajo_local',
								codigo_asunto='$codigo_asunto',
								codigo_actividad=$codigo_actividad,
								descripcion='$descripcion',
								solicitante='$ordenado_por',
								id_moneda=$id_moneda,
								cobrable='$cobrable',
								fecha_creacion=NOW(),
								fecha='$fecha',
								duracion='$hora:$min:00',
						duracion_cobrada='$hora:$min:00',
						id_area_trabajo = $id_area_trabajo
							";
		//die($duracion.' '. $query);
		if(!($resp = mysql_query($query, $sesion->dbh) )) die(mysql_error());
		else {
		    $id_trabajo=mysql_insert_id( $sesion->dbh );
		    
			$trabajo = new Trabajo( $sesion );
			
			$trabajo->Load($id_trabajo );
			$query = "UPDATE usuario SET retraso_max_notificado = 0 WHERE id_usuario = '$id_usuario'";
						mysql_query($query,$sesion->dbh) or die(mysql_error());

			
				
		
		
		$dbh = $sesion->dbh;
		
		$contrato = new Contrato($sesion);
		$contrato->LoadByCodigoAsunto($codigo_asunto);
		
		
		
		$query = "SELECT 
									prm_moneda.id_moneda, 
									( SELECT usuario_tarifa.tarifa 
											FROM usuario_tarifa 
											LEFT JOIN contrato ON contrato.id_tarifa = usuario_tarifa.id_tarifa 
											LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato 
										WHERE usuario_tarifa.id_usuario = '$id_usuario' AND 
													asunto.codigo_asunto = '$codigo_asunto' 
													AND usuario_tarifa.id_moneda = prm_moneda.id_moneda)
								FROM prm_moneda";
		    $resp = mysql_query($query, $dbh) or die(mysql_error());

		    while( list( $id_moneda, $valor ) = mysql_fetch_array($resp) )
		    {
			    if( empty($valor) ) $valor = 0;
			    $query_insert = "INSERT trabajo_tarifa 
													    SET id_trabajo = '$id_trabajo',
															    id_moneda = '$id_moneda',
															    valor = '$valor' 
												    ON DUPLICATE KEY UPDATE valor = '$valor' ";
			    mysql_query($query_insert, $dbh)  or die(mysql_error());

			    if( $contrato->fields['id_moneda'] == $id_moneda ) {
				$queryfinal = "UPDATE trabajo SET tarifa_hh = $valor WHERE id_trabajo = '$id_trabajo'";
				 mysql_query($queryfinal, $dbh)  or die(mysql_error());
			    }
		    }
			
			
		}
			
			
	if($callback!='') {
		echo $callback.' ('.json_encode(array($idescritorio, $id_trabajo)).');';
		} else {
		echo json_encode(array($idescritorio, $id_trabajo));
		}
}




$slimttb->run();