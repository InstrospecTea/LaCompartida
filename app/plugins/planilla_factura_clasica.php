<?php

$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Clasica');
$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Factura_Clasica');
$Slim->hook('hook_factura_pago_fin', 'Ofrece_Planilla_Factura_Pago_Clasica');

function Ofrece_Planilla_Factura_Clasica() {
	  echo '<script>jQuery(\'#boton_descarga\').hide();</script><a class="btn botonizame" icon="xls"    name="boton_excel" onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas_listado_xls.php?opc=buscar&exportar_excel=1\').submit();">'. __('Descargar Excel').'</a>';
}

function Ofrece_Planilla_Factura_Pago_Clasica() {
	  echo '<script>jQuery(\'#boton_descarga\').hide();</script><a class="btn botonizame" icon="xls"    name="boton_excel" onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas_pagos_listado_xls.php?opc=buscar&exportar_excel=1\').submit();">'. __('Descargar Excel').'</a>';
}

function Descarga_Planilla_Clasica() {
	include_once Conf::ServerDir().'/interfaces/facturas_listado_xls.php';
	exit;
}