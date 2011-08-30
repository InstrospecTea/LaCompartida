<?
require_once("../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Trabajo.php';

apache_setenv("force-response-1.0", "TRUE");
apache_setenv("downgrade-1.0", "TRUE"); #Esto es lo más importante

$sesion = new Sesion();
$ns = "urn:TimeTracking";

if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevaLibreriaNusoap') ) || ( method_exists('Conf','NuevaLibreriaNusoap') && Conf::NuevaLibreriaNusoap() ) )
	require_once("lib2/nusoap.php");
else
	require_once("lib/nusoap.php");

#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.

$server = new soap_server();
$server->configureWSDL('TimeTrackingWebServices',$ns);
$server->wsdl->schemaTargetNamespace = $ns;

$server->wsdl->addComplexType(
	'CodigoGlosa',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'codigo' => array('name' => 'codigo', 'type' => 'xsd:string'),
		'glosa' => array('name' => 'glosa', 'type' => 'xsd:string'),
		'codigo_padre' => array('name' => 'codigo_padre', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'ListaCodigoGlosa',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:CodigoGlosa[]')),
	'tns:CodigoGlosa'
);

$server->wsdl->addComplexType(
	'LogTrabajo',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'tiempo' => array('name' => 'tiempo', 'type' => 'xsd:string'),
		'cliente' => array('name' => 'cliente', 'type' => 'xsd:string'),
		'asunto' => array('name' => 'asunto', 'type' => 'xsd:string'),
		'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'ListaLogTrabajo',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:LogTrabajo[]')),
	'tns:LogTrabajo'
);

$server->wsdl->addComplexType(
	'LogDocumento',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'nombre' => array('name' => 'nombre', 'type' => 'xsd:string'),
		'trabajos' => array('name' => 'trabajos', 'type' => 'tns:ListaLogTrabajo')
	)
);

$server->wsdl->addComplexType(
	'ListaLogDocumento',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:LogDocumento[]')),
	'tns:LogDocumento'
);

$server->wsdl->addComplexType(
	'LogPrograma',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'path' => array('name' => 'path', 'type' => 'xsd:string'),
		'nombre' => array('name' => 'nombre', 'type' => 'xsd:string'),
		'documentos' => array('name' => 'documentos', 'type' => 'tns:ListaLogDocumento')
	)
);

$server->wsdl->addComplexType(
	'ListaLogPrograma',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:LogPrograma[]')),
	'tns:LogPrograma'
);

$server->register('CargarTrabajo',
			array('usuario' => 'xsd:string', 
					'password' => 'xsd:string',
					'id_trabajo_local' => 'xsd:string',
					'codigo_asunto' => 'xsd:string',
					'codigo_actividad' => 'xsd:string',
					'descripcion' => 'xsd:string',
					'fecha' => 'xsd:string',
					'duracion' => 'xsd:string',
					),
			array('resultado' => 'xsd:string'),
			$ns);
$server->register('CargarTrabajo2',
			array('usuario' => 'xsd:string', 
					'password' => 'xsd:string',
					'id_trabajo_local' => 'xsd:string',
					'codigo_asunto' => 'xsd:string',
					'codigo_actividad' => 'xsd:string',
					'descripcion' => 'xsd:string',
					'ordenado_por' => 'xsd:string',
					'fecha' => 'xsd:string',
					'duracion' => 'xsd:string',
					),
			array('resultado' => 'xsd:string'),
			$ns);
$server->register('EntregarListaActividades',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_actividades' => 'tns:ListaCodigoGlosa'),
			$ns);
$server->register('EntregarListaClientes',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_clientes' => 'tns:ListaCodigoGlosa'),
			$ns);
$server->register('EntregarListaAsuntos',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_asuntos' => 'tns:ListaCodigoGlosa'),
			$ns);
$server->register('ActividadesObligatorias',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string', 'codigo_asunto' => 'xsd:string'),
			array('resultado' => 'xsd:string'),
			$ns);
$server->register('Intervalo',
			array(),
			array('resultado' => 'xsd:int'),
			$ns);
$server->register('Telefono',
			array(),
			array('resultado' => 'xsd:int'),
			$ns);
$server->register('Setups',
			array(),
			array('ordenado_por' => 'xsd:int'),
			$ns);
$server->register('GetTimeLastWork',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('fecha_ultimo_trabajo' => 'xsd:string'),
			$ns);
$server->register('Titulo',
			array(),
			array('titulo' => 'xsd:string'),
			$ns);
$server->register('TituloAsunto',
			array(),
			array('titulo_asunto' => 'xsd:string'),
			$ns);
$server->register('IngresarLog',
			array('usuario' => 'xsd:string',
					'password' => 'xsd:string', 
					'inicio' => 'xsd:string',
					'fin' => 'xsd:string',
					'programas' => 'tns:ListaLogPrograma'),
			array('resultado' => 'xsd:int'),
			$ns);

function Intervalo()
{
	global $sesion;
	if( method_exists('Conf','GetConf') )
		return Conf::GetConf($sesion,'Intervalo');
	else
		return Conf::Intervalo();
}
function Telefono()
{
	global $sesion;
	if( method_exists('Conf','GetConf') )
		return Conf::GetConf($sesion,'Telefono');
	else
		return Conf::Telefono();
}
function Setups()
{
	global $sesion;
	if( method_exists('Conf','GetConf') )
		return Conf::GetConf($sesion,'OrdenadoPor');
	else
		return Conf::Ordenado_por();
}
function Titulo()
{
	global $sesion;
	if( method_exists('Conf','GetConf') )
		return Conf::GetConf($sesion,'PdfLinea1');
	else
		return Conf::PdfLinea1();
}
function TituloAsunto()
{
	return __('Asunto');
}
function GetTimeLastWork($rut, $password)
{
		$sesion = new Sesion();
		if($sesion->VerificarPassword($rut,$password))
		{
			$query = "SELECT fecha FROM trabajo WHERE id_usuario=(SELECT id_usuario FROM usuario WHERE rut='$rut') ORDER BY fecha DESC LIMIT 0,1";
			if(! ($resp = mysql_query($query, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query,'');
			list($fecha) = mysql_fetch_array($resp);
			if($fecha == '')//Significa que esta persona nunca ha ingresado fechas
				$fecha = '1900-01-01';
			return $fecha;
		}
		return new soap_fault('Client', '','Usuario o Contraseña Incorrectos','');
}
function EntregarListaClientes($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();
	$lista_clientes = array();
	if($sesion->VerificarPassword($usuario,$password))
	{
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$select_codigo="codigo_cliente_secundario as codigo_cliente";
		}
		else
		{
			$select_codigo="codigo_cliente";
		}
		$query = "SELECT $select_codigo ,glosa_cliente FROM cliente  WHERE activo='1' ORDER BY glosa_cliente";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( list($cod,$client) = mysql_fetch_array($resp) )
		{
			$cliente['codigo'] = $cod;
			$cliente['glosa'] = $client;
			$cliente['codigo_padre'] = "";
			array_push($lista_clientes,$cliente);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_clientes','ListaCodigoGlosa',$lista_clientes);
}
function ActividadesObligatorias($usuario, $password, $codigo_asunto)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();

	$lista_asuntos = array();

	if($sesion->VerificarPassword($usuario,$password))
	{
		$query = "SELECT actividades_obligatorias FROM asunto WHERE codigo_asunto='$codigo_asunto'";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		list($obligatorias) = mysql_fetch_array($resp);

		return $obligatorias;
	}
	else
		return new soap_fault('Client', '','Error de login.','');
}
function EntregarListaAsuntos($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();

	$lista_asuntos = array();

	if($sesion->VerificarPassword($usuario,$password))
	{
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$query = "SELECT asunto.codigo_asunto_secundario ,asunto.glosa_asunto,cliente.codigo_cliente_secundario
								FROM asunto 
								JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
								WHERE asunto.activo=1 AND cliente.activo=1 ORDER BY asunto.glosa_asunto";
		}
		else
		{
			$query = "SELECT asunto.codigo_asunto ,asunto.glosa_asunto, asunto.codigo_cliente FROM asunto 
								JOIN cliente ON cliente.codigo_cliente = asunto.codigo_cliente 
								WHERE asunto.activo=1 AND cliente.activo ORDER BY glosa_asunto";
		}
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( list($cod,$asunt,$client) = mysql_fetch_array($resp) )
		{
			$asunto['codigo'] = $cod;
			$asunto['glosa'] = $asunt;
			$asunto['codigo_padre'] = $client;
			array_push($lista_asuntos,$asunto);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_asuntos','ListaCodigoGlosa',$lista_asuntos);
}
function EntregarListaActividades($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();

	$lista_actividades = array();

	if($sesion->VerificarPassword($usuario,$password))
	{
		$query = "SELECT codigo_actividad,glosa_actividad,codigo_asunto FROM actividad ORDER BY glosa_actividad";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( list($cod,$activ,$asunt) = mysql_fetch_array($resp) )
		{
			$actividad['codigo'] = $cod;
			$actividad['glosa'] = $activ;
			$actividad['codigo_padre'] = $asunt;
			array_push($lista_actividades,$actividad);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_actividades','ListaCodigoGlosa',$lista_actividades);
}
function CargarTrabajo2($usuario, $password, $id_trabajo_local, $codigo_asunto, $codigo_actividad, $descripcion, $ordenado_por, $fecha, $duracion)
{
	return CargarTrabajoDB($usuario, $password, $id_trabajo_local, $codigo_asunto, $codigo_actividad, $descripcion, $ordenado_por, $fecha, $duracion);
}
function CargarTrabajo($usuario, $password, $id_trabajo_local, $codigo_asunto, $codigo_actividad, $descripcion, $fecha, $duracion)
{
	return CargarTrabajoDB($usuario, $password, $id_trabajo_local, $codigo_asunto, $codigo_actividad, $descripcion, $ordenado_por, $fecha, $duracion);
}

function CargarTrabajoDB($usuario, $password, $id_trabajo_local, $codigo_asunto, $codigo_actividad, $descripcion, $ordenado_por,  $fecha, $duracion)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();

	if($sesion->VerificarPassword($usuario,$password))
	{
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$query_codigo="SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario='$codigo_asunto'";
			if(!($resp_codigo = mysql_query($query_codigo, $sesion->dbh) ))
				return new soap_fault('Client', '',mysql_error(),'');
			list($codigo_asunto) = mysql_fetch_array($resp_codigo);
		}
		
		$query = "SELECT contrato.id_moneda, asunto.cobrable FROM contrato JOIN asunto on asunto.id_contrato = contrato.id_contrato WHERE codigo_asunto='$codigo_asunto'";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '',mysql_error(),'');
		list($id_moneda, $cobrable) = mysql_fetch_array($resp);

		$minutos = $duracion / 60;
		$min = $minutos % 60;
		$hora = ($minutos - $min) / 60;
		$min = $min < 10 ? "0$min" : $min; 
		$hora = $hora < 10 ? "0$hora" : $hora; 

		$query = "SELECT id_usuario,dias_ingreso_trabajo FROM usuario WHERE rut='$usuario'";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '',mysql_error(),'');
		list($id_usuario,$dias_ingreso_trabajo) = mysql_fetch_array($resp);

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
		if(strtotime($fecha) < mktime(0,0,0,date("m"),date("d")-$dias_ingreso_trabajo,date("Y")))
			$fecha=date("Y-m-d");

		$descripcion=addslashes($descripcion);
		$ordenado_por=addslashes($ordenado_por);
		$query = "INSERT INTO trabajo SET 
								id_usuario='$id_usuario',
								id_trabajo_local='$id_trabajo_local',
								codigo_asunto='$codigo_asunto',
								codigo_actividad=$codigo_actividad,
								descripcion='$descripcion',
								solicitante='$ordenado_por',
								id_moneda=$id_moneda,
								cobrable='$cobrable',
								fecha_creacion=NOW(),
								fecha=DATE_SUB('$fecha', INTERVAL $duracion SECOND),
								duracion='$hora:$min:00',
								duracion_cobrada='$hora:$min:00' 
							";
		
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '',mysql_error(). ". Query: $query",'');
		else {
			$trabajo = new Trabajo( $sesion );
			$trabajo->Load( mysql_insert_id( $sesion->dbh ) );
			$trabajo->InsertarTrabajoTarifa();
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('resultado','xsd:string',"OK");
}

function IngresarLog($usuario, $password, $inicio, $fin, $programas)
{
return 1; //Temporal, porque por el momento genera sobre carga en los servidores
	$sesion = new Sesion();
	if($sesion->VerificarPassword($usuario, $password))
	{
		try{
			$trabajos = array();

			$id_usuario = _campo("id_usuario", "usuario", array("rut" =>$usuario), $sesion);

			$id_log = _insert("log", array(
				"id_usuario" => $id_usuario,
				"inicio" => $inicio,
				"fin" => $fin), $sesion);
		}
		catch(Exception $e){ return _error('No se pudo crear el log: '.$e->getMessage()); }

		try{
			foreach($programas as $programa){
				$id_programa = _select_insert('id_programa', 'log_programa', array(
					"path" => $programa['path'],
					"nombre" => $programa['nombre']), $sesion);

				foreach($programa['documentos'] as $documento){
					$id_documento = _select_insert('id_documento', 'log_documento', array(
						"id_programa" => $id_programa,
						"nombre" => $documento['nombre']), $sesion);

					foreach($documento['trabajos'] as $trabajo){
						$idx = $trabajo['cliente'].'_'.$trabajo['asunto'].'_'.$trabajo['descripcion'];
						if($trabajo['cliente'] === null) $id_trabajo = null;
						else if(isset($trabajos[$idx])) $id_trabajo = $trabajos[$idx];
						else{
							$id_trabajo = _select_insert('id_trabajo', 'log_trabajo', array(
								"id_usuario" => $id_usuario,
								"codigo_cliente" => $trabajo['cliente'] ? $trabajo['cliente'] : null,
								"codigo_asunto" => $trabajo['asunto'] ? $trabajo['asunto'] : null,
								"descripcion" => $trabajo['descripcion']), $sesion);

							$trabajos[$idx] = $id_trabajo;
						}

						_insert("log_item", array(
								"id_log" => $id_log,
								"id_documento" => $id_documento,
								"id_trabajo" => $id_trabajo ? $id_trabajo : null,
								"tiempo" => $trabajo['tiempo']), $sesion);
					}
				}
			}

			return 1;
		}
		catch(Exception $e){ return _error('Error de datos: '.$e->getMessage()); }
	}
	return _error('Usuario o Contraseña Incorrectos');
}

function _query($query, $sesion){
	if(!($resp = mysql_query($query, $sesion->dbh) ))
		throw new Exception(mysql_error()."\n\nquery: ".$query);
	return $resp;
}

function _campo($nombre, $tabla, $campos, $sesion){
	$conds = array();
	foreach($campos as $campo => $valor)
		$conds[] = $campo.($valor===null ? " IS NULL" : " = '".addslashes($valor)."'");

	list($campo) = mysql_fetch_array(_query("SELECT $nombre FROM $tabla WHERE ".implode(' AND ', $conds)." LIMIT 0,1", $sesion));
	return $campo;
}

function _insert($tabla, $campos, $sesion){
	$conds = array();
	foreach($campos as $campo => $valor)
		$conds[] = $campo." = ".($valor===null ? "NULL" : "'".addslashes($valor)."'");

	_query("INSERT INTO $tabla SET ".implode(', ', $conds), $sesion);
	return mysql_insert_id($sesion->dbh);
}

function _select_insert($id_nombre, $tabla, $campos, $sesion){
	$id = _campo($id_nombre, $tabla, $campos, $sesion);
	if($id) return $id;
	return _insert($tabla, $campos, $sesion);
}

function _error($msg){
	return new soap_fault('Client', '', $msg, '');
}

#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>


