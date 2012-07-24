<?php

/*
 *Este plugin agrega 
 * 
 */

global $TTBplugins;

$TTBplugins['hook_cobro_factura_pago'][]='cobro_factura_pago';

if(!function_exists('cobro_factura_pago')) {
	function cobro_factura_pago($query) {
	
 	return str_replace(array('subtotal_factura, factura.iva', 'concepto, fp.descripcion'),array('subtotal_factura, factura.honorarios factura_subtotal_honorarios
			,  factura.subtotal_gastos  factura_subtotal_gastos
			, factura.subtotal_gastos_sin_impuesto  factura_subtotal_gastos_sin_impuesto, factura.iva','concepto, prm_moneda.simbolo, fp.descripcion ')
			,$query);
		return $query;
	
	}
}
