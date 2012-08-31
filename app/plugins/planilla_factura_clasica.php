<?php

$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Clasica');
$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Clasica');

function Ofrece_Planilla_Clasica() {
	  echo '<script>jQuery(\'#boton_descarga\').hide();</script><input type="button" value="'. __('Descargar Excel') .'" class="btn"   name="boton_excel" onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas_listado_xls.php?opc=buscar&exportar_excel=1\').submit();">';
}

function Descarga_Planilla_Clasica() {
	include_once Conf::ServerDir().'/interfaces/facturas_listado_xls.php';
	exit;
}