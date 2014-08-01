<?php

require_once('../app/conf.php');

apache_setenv('force-response-1.0', 'TRUE');
apache_setenv('downgrade-1.0', 'TRUE'); #Esto es lo más importante

$Sesion = new Sesion();
$ns = "urn:TimeTracking";

require_once('lib2/nusoap.php');

#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.

$server = new soap_server();
$server->configureWSDL('WsDummyFacturacionCl', $ns);
$server->wsdl->schemaTargetNamespace = $ns;

/**
 * login
 */

$server->wsdl->addComplexType(
	'logininfo',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'Usuario' => array('name' => 'Usuario', 'type' => 'xsd:string'),
		'Rut' => array('name' => 'Rut', 'type' => 'xsd:string'),
		'Clave' => array('name' => 'Clave', 'type' => 'xsd:string'),
		'Puerto' => array('name' => 'Puerto', 'type' => 'xsd:string'),
	)
);

/**
 * Respuesta
 */

$server->wsdl->addComplexType(
	'replyDocumento',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'Folio' => array('name' => 'Folio', 'type' => 'xsd:integer'),
		'TipoDte' => array('name' => 'TipoDte', 'type' => 'xsd:integer'),
		'Operacion' => array('name' => 'Operacion', 'type' => 'xsd:string'),
		'Fecha' => array('name' => 'Fecha', 'type' => 'xsd:string'),
		'Resultado' => array('name' => 'Resultado', 'type' => 'xsd:string'),
	)
);

$server->wsdl->addComplexType(
	'replyDetalle',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'Documento' => array('name' => 'Documento', 'type' => 'tns:replyDocumento'),
	)
);

$server->wsdl->addComplexType(
	'wsPlano',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'Resultado' => array('name' => 'Resultado', 'type' => 'xsd:string'),
		'Mensaje' => array('name' => 'Mensaje', 'type' => 'xsd:string'),
		'Detalle' => array('name' => 'Detalle', 'type' => 'tns:replyDetalle'),
	)
);


/**
 * Métodos
 */

$server->register(
	'Procesar', array(
		'login' => 'tns:logininfo',
		'file' => 'xsd:string',
		'formato' => 'xsd:integer'
	),
	array('ProcesarResult' => 'xsd:string'),
	$ns
);

$server->register(
	'ObtenerLink', array(
		'login' => 'tns:logininfo',
		'tpomov' => 'xsd:char',
		'folio' => 'xsd:integer',
		'tipo' => 'xsd:integer',
		'cedible' => 'xsd:string'
	),
	array('ProcesarResult' => 'xsd:string'),
	$ns
);

$server->register(
	'getXMLDte', array(
		'login' => 'tns:logininfo',
		'tpomov' => 'xsd:string',
		'folio' => 'xsd:integer',
		'tipo' => 'xsd:integer'
	),
	array('ProcesarResult' => 'xsd:string'),
	$ns
);

$server->register(
	'EliminarDoc', array(
		'login' => 'tns:logininfo',
		'tpomov' => 'xsd:char',
		'folio' => 'xsd:integer',
		'tipo' => 'xsd:integer'
	),
	array('EliminarDocResult' => 'xsd:string'),
	$ns
);

$server->register(
	'Online',
	array(),
	array('OnlineResult' => 'xsd:string'),
	$ns
);


function Procesar($login, $file, $formato) {
	global $Sesion;
	$Sesion->cache_xml = $file;
	$xml = base64_decode($file);
	$sxmle = new SimpleXMLElement($xml);
	$aXML = XML2Array($sxmle);;
	$Folio = $aXML['Documento']['Encabezado']['IdDoc']['Folio'];
	$TipoDTE = $aXML['Documento']['Encabezado']['IdDoc']['TipoDTE'];
	$FchEmis = date('Y-m-d\TH:i:s');

	return "<WSPLANO>
	<Resultado>True</Resultado>
	<Mensaje>Proceso existoso.</Mensaje>
	<Detalle>
		<Documento>
			<Folio>{$Folio}</Folio>
			<TipoDte>{$TipoDTE}</TipoDte>
			<Operacion>VENTA</Operacion>
			<Fecha>$FchEmis</Fecha>
			<Resultado>True</Resultado>
		</Documento>
	</Detalle>
</WSPLANO>";
}

function EliminarDoc() {
	$operacion = ($tpomov == 'V') ? 'VENTA' : 'COMPRA';
	return "<WSPLANO>
	<Resultado>True</Resultado>
	<Mensaje>Documento Eliminado.</Mensaje>
	<Detalle>
		<Documento>
			<Folio>{$Folio}</Folio>
			<TipoDte>{$TipoDTE}</TipoDte>
			<Operacion>$operacion</Operacion>
			<Resultado>True</Resultado>
		</Documento>
	</Detalle>
</WSPLANO>";
}

function ObtenerLink($login, $tpomov, $folio, $tipo) {
	$file = base64_encode('https://s3.amazonaws.com/timebilling-uploads/factura_prueba.pdf');
	return "<WSPLANO>
	<Mensaje>
		$file
	</Mensaje>
</WSPLANO>";
}

function getXMLDte($login, $tpomov, $folio, $tipo) {
	$xml = "<WSPLANO>
	<XmlDTE>
		<tpomov>$tpomov</tpomov>
		<folio>$folio</folio>
		<tipo>$tipo</tipo>
	</XmlDTE>
</WSPLANO>";
	return base64_encode($xml);
}

function Online() {
	return 'Online=1';
}

function XML2Array(SimpleXMLElement $parent) {
	$array = array();

	foreach ($parent as $name => $element) {
		($node = & $array[$name]) && (1 === count($node) ? $node = array($node) : 1) && $node = & $node[];
		$node = $element->count() ? XML2Array($element) : trim($element);
	}

	return $array;
}


function _error($msg) {
	return new soap_fault('Client', '', $msg, '');
}

$server->service($HTTP_RAW_POST_DATA);
