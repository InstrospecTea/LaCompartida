<?php

require_once '../conf.php';
$Sesion = new Sesion();
$Factura = new Factura($Sesion);
$Factura->Load(5722);

$hookArg = array('Factura' => $Factura);
FacturacionElectronicaNubox::GeneraFacturaElectronica($hookArg);