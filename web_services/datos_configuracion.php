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
	'DatosConfiguracion',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'glosa_opcion' => array('name' => 'glosa_opcion', 'type' => 'xsd:string'),
		'valor_opcion' => array('name' => 'valor_opcion', 'type' => 'xsd:string'),
		'valores_posibles' => array('name' => 'valores_posibles', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'ListaDatosConfiguracion',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosConfiguracion[]')),
	'tns:DatosConfiguracion'
);

$server->register('EntregarDatosConfiguracion',
			array('usuario' => 'xsd:string', 'password' => 'xsd:string'),
			array('lista_datos_configuracion' => 'tns:ListaDatosConfiguracion'),
			$ns);
			
			
function EntregarDatosConfiguracion($usuario, $password)
{
	if($usuario == "" || $password == "")
		return new soap_fault(
			'Client', '',
			'Debe entregar el usuario y el password.',''
		); 
		
		$sesion = new Sesion();
		$lista_datos_configuracion = array();
		
		if($sesion->VerificarPassword($usuario,$password))
		{
			$query = "SELECT glosa_opcion, valor_opcion, valores_posibles FROM configuracion";
			if(!($resp = mysql_query($query, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL. --- '.$query,'');
			
			while( list( $glosa_opcion, $valor_opcion, $valores_posibles ) = mysql_fetch_array($resp) )
			{
				$datos_configuracion = array();
				$datos_configuracion['glosa_opcion'] = strip_tags($glosa_opcion);
				$datos_configuracion['valor_opcion'] = strip_tags($valor_opcion);
				$datos_configuracion['valores_posibles'] = strip_tags($valores_posibles);                         
				array_push($lista_datos_configuracion, $datos_configuracion); 
			}
		}
		else
			return new soap_fault('Client', '','Error de login.','');
		return new soapval('lista_datos_configuracion','ListaDatosConfiguracion',$lista_datos_configuracion);
}


#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>