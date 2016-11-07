<?php

require_once(dirname(__FILE__) . '/../conf.php');

$Sesion = new Sesion();

$Factura = $Factura = new Factura($Sesion);
$Factura->Load($_REQUEST['id_factura']);

$Estudio = new PrmEstudio($Sesion);
$Estudio->Load($Factura->fields['id_estudio']);

$Moneda = new Moneda($Sesion);
$Moneda->Load($Factura->fields['id_moneda']);

$WsFacturacionMateriaSoftware = new WsFacturacionMateriaSoftware(
	$Estudio->GetMetaData('facturacion_electronica_materia_software.Url'),
	$Estudio->GetMetaData('facturacion_electronica_materia_software.Authorization')
);

$factura_emitida = $WsFacturacionMateriaSoftware->emitirFactura($Factura, $Moneda);

echo '<div>', $factura_emitida, '</div>';
echo '<div>', $WsFacturacionMateriaSoftware->getErrorMessage(), '</div>';
