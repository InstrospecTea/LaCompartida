<?php

$Slim=Slim::getInstance('default',true);

// Esta función queda comentada, es serious business y no hay que usarla esta vez
//$Slim->hook('hook_footer_popup', 'Honorarios_Notariales_Js_Footer');

$Slim->hook('hook_agregar_factura', 'Honorarios_Notariales_En_Tramites');


$Slim->hook('hook_factura_javascript_after', 'Honorarios_Notariales_Js_Footer_Light');

 

function Honorarios_Notariales_Js_Footer_Light() {
	global $trabajos_disponibles, $tramites_disponibles,$honorario,$monto_honorario,$_LANG;
	$monto_trabajo=(isset($honorario) ? $honorario : $monto_honorario) - $tramites_disponibles;
	$boton_tramites='<a style=\"margin:2px;\" class=\"btn botonizame\"  alt=\"'.floatval($tramites_disponibles) .'\" icon=\"ui-icon-invoice\" rel=\"'.__('Trámites').'\" id=\"facturar_tramites\" setwidth=\"250\" >'.  __('Facturar '. __('Trámites')) .'</a><br/>';
	$boton_trabajos='<br/><a style=\"margin:2px;\" class=\"btn botonizame\" alt=\"'.floatval($monto_trabajo).'\"  icon=\"ui-icon-invoice2\"  rel=\"'.__('Honorarios profesionales').'\"  id=\"facturar_trabajos\"   setwidth=\"250\"  >'.  __('Facturar '. __('Honorarios Profesionales')) .'</a>';
	$checkbox_tramites='<input style=\"display:none;\"  type=\"checkbox\" value=\"1\" id=\"checkbox_tramites\" name=\"checkbox_tramites\" /> ';
	
	echo "jQuery('#glosa_honorarios_legales').html( 'Concepto');";
	echo "jQuery('#mainttb').prepend('<div id=\"divright\" style=\"width:270px;position:absolute;top:-5px;right:30px;\"></div>');";
	
	echo "jQuery('#divright').prepend(\"".$boton_tramites."\");";
	echo "jQuery('#divright').prepend(\"<br/> \");";
	echo "jQuery('#divright').prepend(\"".$boton_trabajos."\");";
	echo "jQuery('#form_facturas').append(\"".$checkbox_tramites ."\"); ";
	
	echo "jQuery('#facturar_tramites').click(function() {
		jQuery('#descripcion_honorarios_legales').text( jQuery(this).attr('rel') ).val(jQuery(this).attr('rel'));	
		jQuery('#monto_honorarios_legales').val( parseInt( jQuery(this).attr('alt')).toFixed(cantidad_decimales) ) ; 
		jQuery('#checkbox_tramites').attr('checked','checked');
		desgloseMontosFactura(jQuery('#form_facturas').get(0));
	});";
	echo "jQuery('#facturar_trabajos').click(function() {
		jQuery('#descripcion_honorarios_legales').text( jQuery(this).attr('rel') ).val(jQuery(this).attr('rel'));	
		jQuery('#monto_honorarios_legales').val( parseInt( jQuery(this).attr('alt')).toFixed(cantidad_decimales) ) ; 
		jQuery('#checkbox_tramites').removeAttr('checked');
		desgloseMontosFactura(jQuery('#form_facturas').get(0));
	});";
}



function Honorarios_Notariales_Js_Footer() {
	
global $simbolo, $impuesto,$honorario,$monto_honorario, $descripcion_tramites, $subtotal_tramites, $simbolo,$_LANG;
$subtotal_tramites=intval($subtotal_tramites);
if (empty($honorario)) $honorario= $monto_honorario;
$monto_trabajo=$honorario- $monto_tramite;
$porcentaje_impuesto=$impuesto/$honorario;
$impuesto_tramite=$porcentaje_impuesto*$subtotal_tramites;
$impuesto_trabajo=$impuesto-$impuesto_tramite;

$descripcion_tramites= (!empty($descripcion_tramites)? $descripcion_tramites: __('Trámites') ) ;


$montotrabajo=  $simbolo.' <input type=\"text\" name=\"monto_trabajos\" class=\"aproximable sumarFrom\"  id=\"monto_trabajos\" value=\"'.$monto_trabajo.'\" size=\"10\" maxlength=\"30\" ><br/>';
$imptotrabajo=  $simbolo.' <input type=\"text\" name=\"monto_iva_trabajos\" class=\"aproximable sumarTo\"   id=\"monto_iva_trabajos\"  value=\"0\"  disabled=\"true\" value=\"0\" size=\"10\" maxlength=\"30\" ><br/>';


$textonotarial='<tr id=\"fila_descripcion_tramites\">';
$textonotarial.='<td align=\"right\">'.  __('Trámites') .'</td>';
$textonotarial.='<td align=\"left\"><input type=\"text\" name=\"descripcion_tramites\" id=\"descripcion_tramites\" value=\"'. trim($descripcion_tramites).'\" maxlength=\"300\" size=\"40\" /></td>';
$textonotarial.= '<td align=left nowrap>'.  $simbolo.' <input type=\"text\" name=\"monto_tramites\" class=\"aproximable sumarFrom\"  id=\"monto_tramites\" value=\"'.$subtotal_tramites.'\" size=\"10\" maxlength=\"30\" ></td>';
$textonotarial.='<td align=left nowrap>'.  $simbolo.' <input type=\"text\" name=\"monto_iva_tramites\" class=\"aproximable sumarTo\"   id=\"monto_iva_tramites\" value=\"0\" disabled=\"true\" value=\"0\" size=\"10\" maxlength=\"30\" ></td>';
$textonotarial.= '</tr>';
			
	echo "jQuery('#td_honorarios_legales').prepend(\"".$montotrabajo."\");";
	echo "jQuery('#td_impto_honorarios_legales').prepend(\"".$imptotrabajo."\");";
	echo "jQuery('#fila_descripcion_honorarios_legales').before(\"".$textonotarial."\");";
	
	echo "jQuery('#monto_trabajos, #monto_tramites').live('keyup',function() {
			jQuery('#monto_honorarios_legales').attr('readonly','readonly').val(1*jQuery('#monto_trabajos').val() + 1* jQuery('#monto_tramites').val() );
			jQuery('#monto_iva_honorarios_legales').val(  parseFloat(porcentaje_impuesto*jQuery('#monto_honorarios_legales').val()/100).toFixed(cantidad_decimales));
			desgloseMontosFactura(jQuery('#form_facturas').get(0));

			jQuery('#monto_honorarios_legales').change();
			
		});
		
	";
	
	
}

function Honorarios_Notariales_En_Tramites() {
 
	global $sesion, $factura,$subtotal_tramites,$descripcion_tramites,$_LANG;
 
	if(!UtilesApp::ExisteCampo( 'subtotal_tramites','factura',$sesion)) {
	$sesion->pdodbh->exec("ALTER TABLE  `factura` ADD    `subtotal_tramites` DOUBLE NOT NULL DEFAULT  '0' COMMENT ' Es un valor ADICIONAL al monto de honorarios, la diferencia entre  ambos da el monto de trabajos que no son trámite ' ");	
	}
	if(!UtilesApp::ExisteCampo( 'descripcion_tramites','factura',$sesion)) {
		$sesion->pdodbh->exec("ALTER TABLE  `factura` ADD  `descripcion_tramites` VARCHAR( 255 ) NULL COMMENT  'Aparece en la pantalla agregar_factura sólo si un plugin lo inserta'");
	}
	
		$factura->Edit('subtotal_tramites', $subtotal_tramites);
		$factura->Edit('descripcion_tramites', $descripcion_tramites);

			
	
 
}

