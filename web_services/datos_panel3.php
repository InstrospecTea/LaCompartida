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
		'id_usuario' => array('name' => 'id_usuario', 'type' => 'xsd:int'),
		'nombre' => array('name' => 'nombre', 'type' => 'xsd:string'),
		'apellido1' => array('name' => 'apellido1', 'type' => 'xsd:string'),
		'apellido2' => array('name' => 'apellido2', 'type' => 'xsd:string'),
		'id_categoria_lemontech' => array('name' => 'id_categoria_usuario', 'type' => 'xsd:int'),
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
	// Busca los datos de usuarios del clientes en usuario y mandalos en el array $datos_cliente
	if($sesion->VerificarPassword($usuario,$password))
	{
            
                $query = "SELECT id_usuario, nombre, apellido1, apellido2, u.id_categoria_usuario, id_categoria_lemontech
                            FROM usuario u JOIN prm_categoria_usuario p 
                            ON u.id_categoria_usuario = p.id_categoria_usuario
                            WHERE activo =1";                                   // JOIN to Select the id_categoria_lemontech
                
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL. --- '.$query,'');
		while( list($id_usuario,$nombre,$apellido1,$apellido2,$id_categoria_usuario, $id_categoria_lemontech) = mysql_fetch_array($resp) )
		{
			$query2= "SELECT id_usuario FROM usuario_permiso WHERE id_usuario=".$id_usuario." AND codigo_permiso='PRO'";
			if(!($resp2 = mysql_query($query2, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL. --- '.$query2,'');

			list($id_usuario)=mysql_fetch_array($resp2);
			if($id_usuario !='') 
				$datos_cliente['id_usuario'] = $id_usuario+1000000;
			else
				$datos_cliente['id_usuario'] = $id_usuario;
			$datos_cliente['nombre'] = strip_tags($nombre);
			$datos_cliente['apellido1'] = strip_tags($apellido1);
			$datos_cliente['apellido2'] = strip_tags($apellido2);
                        $datos_cliente['id_categoria_lemontech'] = ($id_categoria_lemontech); 

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