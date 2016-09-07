<?php

require_once(dirname(__FILE__) . '/../conf.php');

$sesion = new Sesion();
$factura = $factura = new Factura($sesion);
$factura->Load($_REQUEST['id_factura']);
$url = 'http://190.108.68.38:9010/Bridge/WcfBridge.svc';

$WsFacturacionSatcom = new WsFacturacionSatcom($url);
$xml = $WsFacturacionSatcom->crearXML($factura);

// var_dump($factura->fields);
echo $xml;
