<?php
// $url = 'https://prc.thetimebilling.com/time_tracking/web_services/integracion_contabilidad4.php?wsdl';
$url = 'http://local.dev/ttb/web_services/integracion_contabilidad4.php?wsdl';

$client = new SoapClient($url, array("trace" => 1, "exception" => 1, 'encoding' => 'ISO-8859-1'));

try {
	$result = $client->__soapCall("ListaCobrosFacturados", array(
		'usuario' => 'prc',
		'password' => 'prc',
		'timestamp' => '1414713600'
	));

	var_dump(count($result));
	var_dump($result);
} catch(Exception $e) {
	echo $client->__getLastResponse();
	echo $e->getTraceAsString();
	echo '<br/>';
	echo $e->getMessage();
}
