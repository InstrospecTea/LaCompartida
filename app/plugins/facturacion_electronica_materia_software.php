<?php
/**
 * Ofrece exportar las facturas al API de Facturación electrónica de Materia Software
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$clase = 'FacturacionElectronicaMateriaSoftware';
$Slim = Slim::getInstance('default', true);

$Slim->hook(
	'hook_factura_javascript_after',
	[$clase, 'InsertaJSFacturaElectronica']
);

$Slim->hook(
	'hook_validar_factura',
	[$clase, 'ValidarFactura']
);

$Slim->hook(
	'hook_cobro6_javascript_after',
	[$clase, 'InsertaJSFacturaElectronica']
);

$Slim->hook(
	'hook_descargar_pdf_factura_electronica',
	[$clase, 'DescargarPdf']
);

$Slim->hook('hook_cobros7_botones_after', function($hookArg) {
	return FacturacionElectronicaMateriaSoftware::AgregarBotonFacturaElectronica(
		$hookArg
	);
});

$Slim->hook('hook_genera_factura_electronica', function($hookArg) {
	return FacturacionElectronicaMateriaSoftware::GeneraFacturaElectronica(
		$hookArg
	);
});
