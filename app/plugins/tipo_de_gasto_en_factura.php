<?php

$Slim=Slim::getInstance('default',true);

// Esta función queda comentada, es serious business y no hay que usarla esta vez
//$Slim->hook('hook_footer_popup', 'Honorarios_Notariales_Js_Footer');

$Slim->hook('hook_factura_javascript_after', 'Tipo_Gasto_En_Factura');
 

 

function Tipo_Gasto_En_Factura() {
	global $sesion, $factura,$_LANG;
	
	$id_cobro=($factura->fields['id_cobro'])? $factura->fields['id_cobro'] : $_GET['id_cobro'];
	 
	 $query="select con_impuesto, group_concat(item separator ', ') items
							from (select  cc.con_impuesto,  (concat(prm_moneda.simbolo,' ', sum(cc.monto_cobrable),' ', pcct.glosa )) item
							from cta_corriente   cc 
							join prm_moneda using (id_moneda)
							join prm_cta_corriente_tipo pcct using (id_cta_corriente_tipo)
							where egreso is not null and incluir_en_cobro='SI' and cc.id_cobro=".$id_cobro." 
							group by prm_moneda.simbolo, pcct.glosa,cc.con_impuesto) tipos_gastos group by con_impuesto";
	 
		
	 
	$querytipogasto= $sesion->pdodbh->query($query);
	$qtg=$querytipogasto->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		 if(isset($qtg['SI'])) echo "jQuery('#descripcion_gastos_con_iva').text('".$qtg['SI'][0]['items']."');";
		 if(isset($qtg['NO'])) echo "jQuery('#descripcion_gastos_sin_iva').text('".$qtg['NO'][0]['items']."');";
	 
}


 
