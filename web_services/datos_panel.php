<?
require_once("lib/nusoap.php");
require_once("../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';

apache_setenv("force-response-1.0", "TRUE");
apache_setenv("downgrade-1.0", "TRUE"); #Esto es lo más importante


$ns = "urn:TimeTracking";

#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.

$server = new soap_server();
$server->configureWSDL('TimeTrackingWebServices',$ns);
$server->wsdl->schemaTargetNamespace = $ns;

$server->wsdl->addComplexType(
	'DatosCliente',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'numero_usuario' => array('name' => 'numero_usuarios', 'type' => 'xsd:int'),
		'numero_socios' => array('name' => 'numero_socios', 'type' => 'xsd:int'),
		'numero_asociado_socios' => array('name' => 'numero_asociado_socios', 'type' => 'xsd:int'),
		'numero_abogado_asociados' => array('name' => 'numero_abogado_asociados', 'type' => 'xsd:int'),
		'numero_procuradores' => array('name' => 'numero_procuradores', 'type' => 'xsd:int'),
		'numero_socio_capitalistas' => array('name' => 'numero_socio_capitalistas', 'type' => 'xsd:int'),
		'numero_socio_contractuales' => array('name' => 'numero_socio_contractuales', 'type' => 'xsd:int'),
		'horas_anio' => array('name' => 'horas_anio', 'type' => 'xsd:float'),
		'horas_anio_promedio' => array('name' => 'horas_anio_promedio', 'type' => 'xsd:float'),
		'horas_mes' => array('name' => 'horas_mes', 'type' => 'xsd:float'),
		'horas_mes_promedio' => array('name' => 'horas_mes_promedio', 'type' => 'xsd:float'),
		'horas_semana' => array('name' => 'horas_semana', 'type' => 'xsd:float'),
		'horas_semana_promedio' => array('name' => 'horas_semana_promedio', 'type' => 'xsd:float')
	)
);

$server->wsdl->addComplexType(
	'DatosCliente2',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id_usuario' => array('name' => 'id_usuarios', 'type' => 'xsd:int'),
		'nombre' => array('name' => 'nombre', 'type' => 'xsd:varchar'),
		'apellido1' => array('name' => 'apellido1', 'type' => 'xsd:varchar'),
		'apellido2' => array('name' => 'apellido2', 'type' => 'xsd:varchar'),
		'id_categoria_usuario' => array('name' => 'id_categoria_usuario', 'type' => 'xsd:int')
	)
);


$server->wsdl->addComplexType(
	'ListaDatosCliente',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosCliente[]')),
	'tns:DatosCliente'
);

$server->wsdl->addComplexType(
	'ListaDatosCliente2',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosCliente2[]')),
	'tns:DatosCliente2'
);

$server->register('EntregarDatosClientes',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_datos_clientes' => 'tns:ListaDatosCliente'),
			$ns);
			
$server->register('EntregarDatos',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_datos_clientes_2' => 'tns:ListaDatosCliente2'),
			$ns);

function EntregarDatosClientes($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 

	$sesion = new Sesion();
	$lista_datos_clientes = array();
	if($sesion->VerificarPassword($usuario,$password))
	{
		//--> Calculacion de los dias del Trabajo para este año hasta ahora
		$DiasTrabajoEsteAnio=0;
		$fecha=mktime(0,0,0,1,2,date("Y",time()));
		while( $fecha <= time() )
		{
			if(date("w",$fecha) != 0 && date("w",$fecha) != 6 )
			{
			$DiasTrabajoEsteAnio++;
			}
			$fecha=mktime(0,0,0,date('m',$fecha),date('d',$fecha)+1,date('Y',$fecha));
		} 
		
		//-->Calculacion de los dias del Trabajo para ultimo mes
		$DiasTrabajoUltimoMes=0;
		$fecha=mktime(0,0,0,date('m',time())-1,1,date("Y",time()));
		while($fecha < mktime(0,0,0,date('m',time()),1,date("Y",time())))
		{
			if(date("w",$fecha) != 0 && date("w",$fecha) != 6 )
			{
			$DiasTrabajoUltimoMes++;
			}
			$fecha=mktime(0,0,0,date('m',$fecha),date('d',$fecha)+1,date('Y',$fecha));
		}
		
		$query = "SELECT 
							(SELECT COUNT(*) FROM usuario as u1) as numero_usuario,
							(SELECT COUNT(*) FROM usuario as u2 WHERE u2.id_categoria_usuario=1) as numero_socios,
							(SELECT COUNT(*) FROM usuario as u3 WHERE u3.id_categoria_usuario=2) as numero_asociado_socios,
							(SELECT COUNT(*) FROM usuario as u4 WHERE u4.id_categoria_usuario=3) as numero_abogado_asociados,
							(SELECT COUNT(*) FROM usuario as u5 WHERE u5.id_categoria_usuario=4) as numero_procuradores,
							(SELECT COUNT(*) FROM usuario as u6 WHERE u6.id_categoria_usuario=5) as numero_socio_capitalistas,
							(SELECT COUNT(*) FROM usuario as u7 WHERE u7.id_categoria_usuario=6) as numero_socio_contractuales,
							(SELECT SUM(TIME_TO_SEC(t1.duracion))/3600 FROM trabajo as t1 WHERE YEAR(fecha) = YEAR(NOW())) as horas_anio,
							(SELECT SUM(TIME_TO_SEC(t2.duracion))/(3600*'$DiasTrabajoEsteAnio') FROM trabajo as t2  WHERE YEAR(fecha) = YEAR(NOW())) as horas_anio_promedio,
							(SELECT SUM(TIME_TO_SEC(t3.duracion))/3600 FROM trabajo as t3 WHERE MONTH(fecha) = MONTH(NOW())-1 AND YEAR(fecha)=YEAR(NOW())) as horas_mes,
							(SELECT SUM(TIME_TO_SEC(t4.duracion))/(3600*'$DiasTrabajoUltimoMes') FROM trabajo as t4 WHERE MONTH(fecha) = MONTH(NOW())-1 AND YEAR(fecha)=YEAR(NOW())) as horas_mes_promedio,
							(SELECT SUM(TIME_TO_SEC(t5.duracion))/3600 FROM trabajo AS t5 WHERE YEARWEEK(fecha,1)=YEARWEEK(NOW(),1)-1) as horas_semana,
							(SELECT SUM(TIME_TO_SEC(t6.duracion))/(5*3600) FROM trabajo AS t6 WHERE YEARWEEK(fecha,1)=YEARWEEK(NOW(),1)-1) as horas_semana_promedio";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL. --- '.$query,'');
		while( list($numero_usuario,$numero_socios,$numero_asociado_socios,$numero_abogado_asociados,$numero_procuradores,$numero_socio_capitalistas,$numero_socio_contractuales,$horas_anio,$horas_anio_promedio,$horas_mes,$horas_mes_promedio,$horas_semana,$horas_semana_promedio) = mysql_fetch_array($resp) )
		{
			$datos_cliente['numero_usuario'] = strip_tags($numero_usuario);     // HTML Tags create Errors in SOAP
			$datos_cliente['numero_socios'] = strip_tags($numero_socios);
			$datos_cliente['numero_asociado_socios'] = strip_tags($numero_asociado_socios);
			$datos_cliente['numero_abogado_asociados'] = strip_tags($numero_abogado_asociados);
			$datos_cliente['numero_procuradores'] = strip_tags($numero_procuradores);
			$datos_cliente['numero_socio_capitalistas'] = strip_tags($numero_socio_capitalistas);
			$datos_cliente['numero_socio_contractuales'] = strip_tags($numero_socio_contractuales);
			$datos_cliente['horas_anio'] = round($horas_anio,2);
			$datos_cliente['horas_anio_promedio'] = round($horas_anio_promedio,2);
			$datos_cliente['horas_mes'] = round($horas_mes,2);
			$datos_cliente['horas_mes_promedio'] = round($horas_mes_promedio,2);
			$datos_cliente['horas_semana'] = round($horas_semana,2);
			$datos_cliente['horas_semana_promedio'] = round($horas_semana_promedio,2);
			array_push($lista_datos_clientes,$datos_cliente);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_datos_clientes','ListaDatosCliente',$lista_datos_clientes);
}

function EntregarDatos($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 
//$fecha=mktime(0,0,0,1,1,2000);
	$sesion = new Sesion();
	$lista_datos_clientes = array();
	if($sesion->VerificarPassword($usuario,$password))
	{
		$query2 = "SELECT id_usuario, nombre, apellido1, apellido2, id_categoria_usuario
							FROM usuario ";
		if(!($resp2 = mysql_query($query2, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL. --- '.$query2,'');
		while( list($id_usuario,$nombre,$apellido1,$apellido2,$id_categoria_usuario) = mysql_fetch_array($resp2) )
		{
			$datos_cliente['id_usuario'] = $id_usuario;
			$datos_cliente['nombre'] = strip_tags($nombre);          // HTML Tags create Errors in SOAP
			$datos_cliente['apellido1'] = strip_tags($apellido1);
			$datos_cliente['apellido2'] = strip_tags($apellido2);
			$datos_cliente['id_categoria_usuario'] = $id_categoria_usuario;
			array_push($lista_datos_clientes_2,$datos_cliente);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_datos_cliente_2','ListaDatosCliente2',$lista_datos_cliente_2);
}

#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>