<?php
/**
 * Ofrece exportar las facturas al WS de Facturaci�n electr�nica Satcom (http://satcomec.com/)
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$Slim = Slim::getInstance('default', true);

$clase = 'FacturacionElectronicaSatcom';

$Slim->hook('hook_factura_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));
$Slim->hook('hook_factura_metodo_pago', array($clase, 'InsertaMetodoPago'));
$Slim->hook('hook_factura_dte_estado', array($clase, 'InsertaEstadoDTE'));
$Slim->hook('hook_validar_factura', array($clase, 'ValidarFactura'));
$Slim->hook('hook_cobro6_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));
$Slim->hook('hook_descargar_pdf_factura_electronica', array($clase, 'DescargarPdf'));

$Slim->hook('hook_cobros7_botones_after', function($hookArg) {
  return FacturacionElectronicaSatcom::AgregarBotonFacturaElectronica($hookArg);
});

$Slim->hook('hook_genera_factura_electronica', function($hookArg) {
  return FacturacionElectronicaSatcom::GeneraFacturaElectronica($hookArg);
});

$Slim->hook('hook_anula_factura_electronica', function($hookArg) {
  return FacturacionElectronicaSatcom::AnulaFacturaElectronica($hookArg);
});
