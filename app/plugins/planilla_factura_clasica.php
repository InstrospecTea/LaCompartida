<?php

$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Clasica');
$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Clasica');

function Ofrece_Planilla_Clasica() {
	// echo '<input type="button" value="'. __('Descargar Excel') .' Formato Antiguo" class="btn" name="boton_excel" onclick="BuscarFacturas(this.form, \'exportar_excel\')">';
}

function Descarga_Planilla_Clasica() {
	include_once Conf::ServerDir().'/interfaces/facturas_listado_xls.php';
	exit;
}