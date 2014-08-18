<?php
/**
 * Ofrece exportar las facturas al WS de Facturación electrónica México
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$Slim = Slim::getInstance('default', true);

$clase = 'FacturacionElectronicaNubox';

$Slim->hook('hook_factura_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));
$Slim->hook('hook_factura_metodo_pago', array($clase, 'InsertaMetodoPago'));
$Slim->hook('hook_factura_dte_estado', array($clase, 'InsertaEstadoDTE'));
$Slim->hook('hook_validar_factura', array($clase, 'ValidarFactura'));
$Slim->hook('hook_cobro6_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));

$Slim->hook('hook_cobros7_botones_after', function($hookArg) {
  return FacturacionElectronicaNubox::AgregarBotonFacturaElectronica($hookArg);
});

$Slim->hook('hook_genera_factura_electronica', function($hookArg) {
  return FacturacionElectronicaNubox::GeneraFacturaElectronica($hookArg);
});

$Slim->hook('hook_anula_factura_electronica', function($hookArg) {
  return FacturacionElectronicaNubox::AnulaFacturaElectronica($hookArg);
});
