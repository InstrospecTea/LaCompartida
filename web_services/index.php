<?php

require_once dirname(__FILE__).'/../app/conf.php';
$Slim=Slim::getInstance('default')? Slim::getInstance('default') : new Slim();
$sesion = new Sesion();

$Slim->hook('slim.before', "Authenticate");
  
 

if(!$sesion->pdodbh) {
			try {

						$sesion->pdodbh = new PDO(
								'mysql:dbname=' . Conf::dbName() . ';host=' . Conf::dbHost(),
								Conf::dbUser(),
								Conf::dbPass());
						$sesion->pdodbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					} catch (PDOException $e) {

						echo "Error Connection: " . $e->getMessage();
						
					}
			}



function Authenticate()  {
	global $sesion;
	$Slim=Slim::getInstance('default',true);
		$usuario= $Slim->request()->params('usuario');
		$password= $Slim->request()->params('password');
		if (empty($usuario) || empty($password) || $usuario == "" || $password == "") {
					$Slim->response()->status(401);
					$Slim->halt(401,'["Debe entregar el usuario y el password."]');
				} else if (!$sesion->VerificarPassword($usuario, $password)) {
					$Slim->response()->status(401);
					$Slim->halt(401,'["Usuario o Password incorrectos"]');
				}
		header('Content-Type: text/javascript; charset=utf8');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Max-Age: 3628800');
		header('Access-Control-Allow-Methods: GET, POST');

}

$Slim->map('/EntregarListaClientes(/:callback)', 'EntregarListaClientes')->via('GET', 'POST');

	function EntregarListaClientes($callback='') {
        
				$Slim=Slim::getInstance('default',true);
				$usuario= $Slim->request()->params('usuario');
				$password= $Slim->request()->params('password');
				$timestamp=$Slim->request()->params('timestamp');
		 
			
			$lista_clientes = array();
                        
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

$Slim->map('/EntregarListaAsuntos(/:callback)', 'EntregarListaAsuntos')->via('GET', 'POST');

	function EntregarListaAsuntos($callback='') {
            global $sesion;
				$Slim=Slim::getInstance('default',true);
				$usuario= $Slim->request()->params('usuario');
				$password= $Slim->request()->params('password');
				$timestamp=$Slim->request()->params('timestamp');
				
			 
			
	$lista_asuntos = array();
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
$Slim->map('/EntregarDatos(/:callback)', 'EntregarDatos')->via('GET', 'POST');

    function EntregarDatos($callback='') {
		 global $sesion;
				
			
			$queryuser="select id_usuario, nombre, apellido1 from usuario where rut='$usuario'";
		
			 list($idusuario,$nombre,$apellido) = mysql_fetch_row(mysql_query($queryuser, $sesion->dbh) );
			
			 
			
	if($callback!='') {
		echo $callback.' ('.json_encode(array($idusuario,$nombre,$apellido,UtilesApp::GetConf($sesion,'Intervalo'),UtilesApp::GetConf($sesion,'NombreEmpresa') )).');';
		} else {
		echo json_encode( array($idusuario,$nombre,$apellido,UtilesApp::GetConf($sesion,'Intervalo'),UtilesApp::GetConf($sesion,'NombreEmpresa') )  );
		}
}

//POST route
$Slim->map('/EntregarDatosClientes(/:callback)', 'EntregarDatosClientes')->via('GET', 'POST');

    function EntregarDatosClientes($callback='') {
		 global $sesion;
				
			
				
			$usuarios=array();
		
			 
			
			if(existecampo('activo_juicio', 'usuario', $sesion->dbh)) {
				$queryuser = "SELECT id_usuario, nombre, apellido1, apellido2, u.id_categoria_usuario, id_categoria_lemontech, u.activo, u.activo_juicio
			               FROM usuario u left JOIN prm_categoria_usuario p 
                            ON u.id_categoria_usuario = p.id_categoria_usuario                            ";   
			} else {
		
				$queryuser = "SELECT id_usuario, nombre, apellido1, apellido2, u.id_categoria_usuario, id_categoria_lemontech, u.activo
			               FROM usuario u left JOIN prm_categoria_usuario p 
                            ON u.id_categoria_usuario = p.id_categoria_usuario                            ";   
			}
			$respuser=mysql_query($queryuser, $sesion->dbh) or die(mysql_error());
			
			
			while($fila=mysql_fetch_assoc($respuser )) {
			$fila['nombre']=utf8_encode($fila['nombre']);
			$fila['apellido1']=utf8_encode($fila['apellido1']);
			$fila['apellido2']=utf8_encode($fila['apellido2']);
				$usuarios[]=$fila;
			}
		
	 echo json_encode($usuarios);
}


//POST route
$Slim->map('/DatosPanel(/:callback)', 'DatosPanel')->via('GET', 'POST');

    function DatosPanel($callback='') {
		 global $sesion;
			
				
			
				$consultatablas=$sesion->pdodbh->query( "SHOW tables like  'j_causa'");
				$arraytablas=$consultatablas->fetchALL(PDO::FETCH_COLUMN );
					if(in_array('j_causa',$arraytablas) ) {
						$causas=", (SELECT count(*) as causas FROM 	j_causa WHERE 	url_cuaderno IS NOT NULL AND 	url_cuaderno <> '' AND 		eliminada = 0) as causas";

					} else {
						$causas="";
					}
					
			
				$versionct=$sesion->pdodbh->query( "SHOW COLUMNS FROM  version_db");
				$versionctcampos=$versionct->fetchALL(PDO::FETCH_COLUMN );
					if(in_array('version_ct',$versionctcampos) ) {
						$version_ct=",(select max(version_ct)  version_ct from version_db) as version_ct";
					} else {
						$version_ct="";
					}
					
			$lista_datos = array();
 			
			$querydatos="select * from

(select count(*) as gastos from cta_corriente) cc,
(select count(*) as tramites from tramite) tram,
(SELECT sum( if( estado != 'CREADO' && estado != 'EN REVISION', 1, 0 ) ) AS emitidos, sum( if( estado = 'PAGADO', 1, 0 ) ) AS pagados
FROM cobro) cobros,
(select count(*) as facturas from factura) fc,

(select 
sum(if(tr.fecha>=fechas.inicio_ano,time_to_sec(tr.duracion)/3600,0)) as HH_ANO,
sum(if(tr.fecha>=fechas.inicio_mes and tr.fecha<=fechas.fin_mes,time_to_sec(tr.duracion)/3600,0)) as HH_MES,
sum(if(tr.fecha>=fechas.iniciosemana and tr.fecha<=fechas.finsemana,time_to_sec(tr.duracion)/3600,0)) as HH_SEMANA,
(select max(version)  version_tt from version_db) as version_tt
$version_ct
$causas
from trabajo tr,
(select 
YEAR(CURDATE())*10000+101 inicio_ano,
date_format(LAST_DAY(now() - interval 1 month),'%Y%m%d') fin_mes,
concat(date_format(LAST_DAY(now() - interval 1 month),'%Y%m'),'01') inicio_mes,
 date_format(subdate(now(), INTERVAL 7+weekday(now()) DAY),'%Y%m%d') iniciosemana,
date_format(subdate(now(), INTERVAL 1+weekday(now()) DAY),'%Y%m%d') finsemana) fechas) trabajos";
	 
			 $respuesta =$sesion->pdodbh->query( $querydatos); 
			
			$datos = $respuesta->fetchALL(PDO::FETCH_ASSOC );
			$datos = $datos[0];

			$datos['path_real'] = realpath(dirname(__FILE__) . '/../');
			//ultima version disponible en el update
			//(se parsea el archivo porque las versiones viejan del update no tienen ese dato)
			$up = file_get_contents(Conf::ServerDir() . '/update.php');
			preg_match_all('/case\s+(\d+\.\d+)\s*:/', $up, $matches);
			$datos['ultima_version_tt'] = end($matches[1]);

			if(empty($datos['version_tt']) && file_exists(Conf::ServerDir() . '/version.php')){
				$_GET['show'] = 0;
				include(Conf::ServerDir() . '/version.php');
				$datos['version_tt'] = $VERSION;
			}
 
		echo json_encode($datos);
		 
}


$Slim->map('/CargarTrabajo(/:callback)', 'CargarTrabajo')->via('GET', 'POST');

    function CargarTrabajo($callback = '') {
	global $sesion;
	$Slim = Slim::getInstance();
	$usuario = $Slim->request()->params('usuario');
	$password = $Slim->request()->params('password');
	$starttimer = $Slim->request()->params('starttimer'); // timestamp local del cliente de escritorio
	$idescritorio = $Slim->request()->params('idescritorio'); // opcional, puede venir en cero, el webservice le responde al cliente con su idescritorio definitivo
	$codigo_asunto = $Slim->request()->params('codigoasunto');
	$duracion = intval($Slim->request()->params('duracion'));
	$descripcion = utf8_decode($Slim->request()->params('descripcion'));
	$ordenado_por = $Slim->request()->params('userid');  // opcional, puede venir vacio, NO Cero
	$codigo_actividad = $Slim->request()->params('codigo_actividad');  // opcional, puede venir vacio, NO Cero
	$area_trabajo = $Slim->request()->params('area_trabajo');  // opcional, puede venir vacio, NO Cero
	$fecha = $Slim->request()->params('fecha');  // opcional, puede venir vacio, NO Cero
	if ($fecha == "")		$fecha = date('Y-m-d', strtotime($starttimer / 1000) + 86400);

	




	if ($usuario == "" || $password == "") {
		
		  $Slim->halt(401, '["Debe entregar el usuario y el password."]');
	} else if (!$sesion->VerificarPassword($usuario, $password)) {
		 
		  $Slim->halt(401, '["Usuario o Password incorrectos"]');
	}
	if (!isset($id_usuario) || !$id_usuario) $id_usuario=$sesion->usuario->fields['id_usuario'];

	$trabajo = new Trabajo($sesion);
	
	 
	$trabajo->Edit("id_usuario", $id_usuario);
	$trabajo->Edit('id_cobro', 'NULL');
	$trabajo->Edit('id_trabajo_local', $id_usuario . $starttimer);  // esto equivale a hacerlo único


	$asunto = new Asunto($sesion);
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
		$codigo_asunto = $asunto->fields['codigo_asunto'];
	} else {
		$asunto->LoadByCodigo($codigo_asunto);
	}
	$trabajo->Edit('codigo_asunto', $codigo_asunto);
$trabajo->Edit('descripcion', $descripcion);

	if ($asunto->fields['cobrable'] == 0) {
		$trabajo->Edit("cobrable", '0');
		$trabajo->Edit("visible", '0');
	} else {
		$trabajo->Edit("cobrable", '1');
		$trabajo->Edit("visible", '1');
	}
	$contrato = new Contrato($sesion);
	$contrato->Load($asunto->fields['id_contrato']);

	$trabajo->Edit('tarifa_hh', Funciones::Tarifa($sesion, $id_usuario, $contrato->fields['id_moneda'], $codigo_asunto));

	$trabajo->Edit('costo_hh', Funciones::TarifaDefecto($sesion, $id_usuario, $contrato->fields['id_moneda']));



	$trabajo->Edit("id_moneda", $contrato->fields['id_moneda']);


	$minutos = $duracion / 60;
	$min = intval($minutos % 60);
	$hora = intval(($minutos - $min) / 60);
	$min = $min < 10 ? "0" . $min : $min;
	$hora = $hora < 10 ? "0" . $hora : $hora;
	$trabajo->Edit("duracion", "$hora:$min:00");
	if ($trabajo->fields['cobrable'] == 0) {
		$trabajo->Edit('duracion_cobrada', '00:00:00');
	} else {
		$trabajo->Edit("duracion_cobrada", "$hora:$min:00");
	}

	$query = "SELECT id_usuario, id_categoria_usuario ,dias_ingreso_trabajo FROM usuario WHERE rut='$usuario'";
	if (!($resp = mysql_query($query, $sesion->dbh) ))
		die(mysql_error());
	list($id_usuario, $id_categoria_usuario, $dias_ingreso_trabajo) = mysql_fetch_array($resp);

	$trabajo->Edit("id_usuario", $id_usuario);
	$trabajo->Edit("id_categoria_usuario", $id_categoria_usuario);


	if ($codigo_actividad == "")
		$codigo_actividad = "NULL";
	$trabajo->Edit("codigo_actividad", $codigo_actividad);
	$id_area_trabajo = !empty($area_trabajo) ? "'$area_trabajo'" : "NULL";
	$trabajo->Edit("id_area_trabajo", $id_area_trabajo);


	//Todo a mayusculas segun conf
	if (UtilesApp::GetConf($sesion, 'TodoMayuscula')) {
		$descripcion = strtoupper($descripcion);
		$ordenado_por = strtoupper($ordenado_por);
	}
	$trabajo->Edit("solicitante", $descripcion);
	$trabajo->Edit("id_moneda", $ordenado_por);

	$fecha_antigua = $fecha;
	if ($dias_ingreso_trabajo > 0) {
		if (strtotime($fecha) < mktime(0, 0, 0, date("m"), date("d") - $dias_ingreso_trabajo, date("Y")))
			$fecha = date("Y-m-d");
	}
	$trabajo->Edit("fecha", $fecha); // si intenta ingresar un trabajo con fecha más antigua que su límite, lo ingresa con fecha de hoy 
	//$trabajo->Edit('fecha_creacion', date('Y-m-d H:i:s')); // el sistema le añade NOW de por si, mala cosa.


	if ($trabajo->Write()) {

		$trabajo->InsertarTrabajoTarifa();


		try {
		$sesion->pdodbh->exec("UPDATE usuario SET retraso_max_notificado = 0 WHERE id_usuario = '$id_usuario'");
		} catch (Exception $e) {
			 $Slim->halt(401, $e->getMessage());
		}
	} else {
		  $Slim->halt(401, '["No se ha podido insertar el trabajo."]');
	}

	if ($callback != '') {
		echo $callback . ' (' . json_encode(array($idescritorio, $trabajo->fields['id_trabajo'])) . ');';
	} else {
		echo json_encode(array($idescritorio, $trabajo->fields['id_trabajo']));
	}
}

function existecampo($campo,$tabla,$dbh) { 
    
    $existencampos = mysql_query("show columns  from $tabla like '$campo'", $dbh);
    if(!$existencampos):
	return false;
    elseif(mysql_num_rows($existencampos)>0): 
	return true;
    endif;
        return false;
}

$Slim->run();
