<?php

require_once(dirname(__FILE__) . '/../conf.php');

$sesion = new Sesion();
$factura = $factura = new Factura($sesion);
$factura->Load($_REQUEST['id_factura']);
$url = 'http://api.contable.pe/api/';

$WsFacturacionSatcom = new WsFacturacionMateriaSoftware($url);
$xml = $WsFacturacionSatcom->emitirFactura($factura);

echo $xml;
