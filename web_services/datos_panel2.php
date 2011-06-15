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
		'horas_anio' => array('name' => 'horas_anio', 'type' => 'xsd:float'),
		'horas_mes' => array('name' => 'horas_mes', 'type' => 'xsd:float'),
		'horas_semana' => array('name' => 'horas_semana', 'type' => 'xsd:float')
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


$server->register('EntregarDatosClientes',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_datos_clientes' => 'tns:ListaDatosCliente'),
			$ns);

			
			
function EntregarDatosClientes($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 
//$fecha=mktime(0,0,0,1,1,2000);
	$sesion = new Sesion();
	$lista_datos_clientes = array();
	// Busca el total de las horas trabajadas per año, mes y semana y envialo en $datos_cliente
	if($sesion->VerificarPassword($usuario,$password))
	{
		$query =" SELECT
							(SELECT SUM(TIME_TO_SEC(t1.duracion))/3600 FROM trabajo as t1 WHERE YEAR(fecha) = YEAR(NOW())) as horas_anio,
							(SELECT SUM(TIME_TO_SEC(t2.duracion))/3600 FROM trabajo as t2 WHERE MONTH(fecha) = MONTH(NOW())-1 AND YEAR(fecha)=YEAR(NOW())) as horas_mes,
							(SELECT SUM(TIME_TO_SEC(t3.duracion))/3600 FROM trabajo as t3 WHERE YEARWEEK(fecha,1)=YEARWEEK(NOW(),1)-1) as horas_semana";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL. --- '.$query,'');
		while( list($horas_anio,$horas_mes,$horas_semana) = mysql_fetch_array($resp) )
		{
			$datos_cliente['horas_anio'] = round($horas_anio,2);
			$datos_cliente['horas_mes'] = round($horas_mes,2);
			$datos_cliente['horas_semana'] = round($horas_semana,2);
			array_push($lista_datos_clientes,$datos_cliente);
		}
	}
	else
		return new soap_fault('Client', '','Error de login.','');
	return new soapval('lista_datos_clientes','ListaDatosCliente',$lista_datos_clientes);
}


#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>