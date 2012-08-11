<?php

/*
 *Este plugin agrega 
 * 
 */

 
$Slim=Slim::getInstance('default',true);
$Slim->hook('hook_cobro_factura_pago', 'Campos_Extra_En_Factura_Pago');


function Campos_Extra_En_Factura_Pago() {
	global $query;
	 
	$query=str_replace('subtotal_factura, factura.iva','subtotal_factura, factura.honorarios factura_subtotal_honorarios ,  factura.subtotal_gastos  factura_subtotal_gastos, factura.subtotal_gastos_sin_impuesto  factura_subtotal_gastos_sin_impuesto, factura.iva' ,$query);
	$query=str_replace('concepto, fp.descripcion','concepto, prm_moneda.simbolo, fp.descripcion ',$query);
	
}

 