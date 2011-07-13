<?php
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
		'cantidad_gastos' => array('name' => 'cantidad_gastos', 'type' => 'xsd:integer'),
		'cantidad_tramites' => array('name' => 'cantidad_tramites', 'type' => 'xsd:integer'),
		'cobros_emitidos' => array('name' => 'cobros_emitidos', 'type' => 'xsd:integer'),
		'cobros_pagados' => array('name' => 'cobros_pagados', 'type' => 'xsd:integer'),
		'facturas_creadas' => array('name' => 'facturas_creadas', 'type' => 'xsd:integer')
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
						(SELECT COUNT(*) FROM cta_corriente as t1) as cantidad_gastos, 
						(SELECT COUNT(*) FROM tramite as t2) as cantidad_tramites, 
						(SELECT COUNT(*) FROM cobro as t3 WHERE ( estado != 'CREADO' AND estado!= 'EN REVISION' AND estado != '' ) ) as cobros_emitidos,
						(SELECT COUNT(*) FROM cobro as t4 WHERE estado = 'PAGADO' ) as cobros_pagados,
						(SELECT COUNT(*) FROM factura as t5 WHERE ( estado != 'ANULADA' AND estado != '' ) ) as facturas_creadas";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL. --- '.$query,'');
		while( list($cantidad_gastos,$cantidad_tramites,$cobros_emitidos, $cobros_pagados, $facturas_creadas) = mysql_fetch_array($resp) )
		{
			$datos_cliente['cantidad_gastos'] = round($cantidad_gastos,2);
			$datos_cliente['cantidad_tramites'] = round($cantidad_tramites,2);
			$datos_cliente['cobros_emitidos'] = round($cobros_emitidos,2);
			$datos_cliente['cobros_pagados'] = round($cobros_pagados,2);
			$datos_cliente['facturas_creadas'] = round($facturas_creadas,2);
			
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