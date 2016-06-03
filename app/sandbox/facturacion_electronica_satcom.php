<?php

require_once(dirname(__FILE__) . '/../conf.php');

$WsFacturacionSatcom = new WsFacturacionSatcom();
$WsFacturacionSatcom->emitirFactura();
