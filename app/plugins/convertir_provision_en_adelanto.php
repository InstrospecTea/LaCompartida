<?php

$Slim = Slim::getInstance('default', true);


$Slim->hook('hook_agregar_gasto_inicio', 'Boton_Convertir_Adelanto');

 

function Boton_Convertir_Adelanto() {
	global $sesion, $gasto;

	if($gasto->fields['ingreso']>0 ) {
	$text='&nbsp;&nbsp;<a class="fr btn botonizame" icon="ui-icon-invoice2"   style="margin:2px;" id="boton_convertir_en_adelanto" name="boton_convertir_en_adelanto" 
					onclick="jQuery(\'#opcion\').val(\'convertir_en_adelanto\');jQuery(\'#form_gastos\').attr(\'action\',\'agregar_gasto.php?popup=1&opcion=convertir_en_adelanto&id_gasto='.$gasto->fields['id_movimiento'].'\').submit();">' . __('Convertir en Adelanto') . '</a>&nbsp;&nbsp;';
	echo $text;
 

	if ($_GET['opcion']=='convertir_en_adelanto') {
		
		$documento=new Documento($sesion);
 	
		try {
			$descripciondocumento='Creado desde '.__('Provisión').' #'.$gasto->fields['id_movimiento'].' ('.$gasto->fields['descripcion'].')';
	$id_documento = $documento->IngresoDocumentoPago(null, null, $gasto->fields['codigo_cliente'], $gasto->fields['ingreso'], 
						$gasto->fields['id_moneda'], 'T', $gasto->fields['numero_documento'], $gasto->fields['fecha'], 
						$descripciondocumento, null, 						null, null, null, $gasto->fields['id_moneda'], 
						array(), array(), null, 1, 0, 1, false, $gasto->extra_fields['id_contrato'], false,$gasto->fields['id_usuario_ingresa'],$gasto->fields['id_usuario_orden'], null, $gasto->fields['codigo_asunto']);	
 		$gasto->Edit('ingreso',0,true);
 	$gasto->Edit('monto_cobrable',0,true);
 	$gasto->Edit('cobrable',0,true);
 	$gasto->Edit('descripcion',$gasto->fields['descripcion'].'. Convertido en Adelanto #'.$id_documento,true);
if($gasto->Write()) {

echo '<br><br><div class="alert alert-success">	La '.__('Provisión').' se ha convertido en el '; 
echo '<a href="ingresar_documento_pago.php?id_documento='.$id_documento.'&adelanto=1&popup=1">';
echo __('Adelanto').' #'.$id_documento;
echo '</a>';
echo ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>'; 	

			?>
			<script language='javascript'>
				if(  parent.window.Refrescarse ) {
					parent.window.Refrescarse(); 
				} else if( window.opener.Refrescar ) {
					window.opener.Refrescar(); 
				}
				jQuery('#boton_convertir_en_adelanto').remove();
			</script>
			<?php		}		
		} catch (Exception $e) {
		
echo '<br><br><div class="alert">Ha ocurrido un problema: <br>'. $e->getMessage().'	<button type="button" class="close" data-dismiss="alert">&times;</button>			  </div>';
		}

	}
	}
 
}

