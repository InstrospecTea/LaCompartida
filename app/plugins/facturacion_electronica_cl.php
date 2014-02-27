<?php
/**
 * Ofrece exportar las facturas al WS de Facturación electrónica México
 *
 * @package The Time Billing
 * @subpackage Plugins
 */

require_once dirname(__FILE__) . '/../conf.php';

$Slim = Slim::getInstance('default', true);

$clase = 'FacturacionElectronicaCl';

$Slim->hook('hook_factura_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));
$Slim->hook('hook_factura_metodo_pago', array($clase, 'InsertaMetodoPago'));
$Slim->hook('hook_validar_factura', array($clase, 'ValidarFactura'));
$Slim->hook('hook_cobro6_javascript_after', array($clase, 'InsertaJSFacturaElectronica'));
$Slim->hook('hook_cobros7_botones_after',  array($clase, 'AgregarBotonFacturaElectronica'));
$Slim->hook('hook_genera_factura_electronica',  array($clase, 'GeneraFacturaElectronica'));
$Slim->hook('hook_anula_factura_electronica',  array($clase, 'AnulaFacturaElectronica'));
