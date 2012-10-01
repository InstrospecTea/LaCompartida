<?php

 
$Slim=Slim::getInstance('default',true);
$Slim->hook('hook_js_gastos', 'BotonEmitirBorrador');

function BotonEmitirBorrador() {
              
 
echo '{
								"sExtends":    "text",
								"sButtonText": "Generar '.   __('Cobro')  .'",
								"fnClick": function ( nButton, oConfig, oFlash ) {';
									
echo "									if(!jQuery('#codcliente').val() || jQuery('#codcliente').val()!=1) {
											alert('Esta función sólo puede utilizarse si filtra los gastos para un cliente en particular');
											return false;
										}
										if(jQuery('#balance').val()>0) {
											
										
										
										var fecha_ini=jQuery('#fecha1').val();
										if(jQuery('#fecha2').val()!='') {
											var fecha_fin=jQuery('#fecha2').val();
										} else {
											 
											var fecha_fin= '". date('d-m-Y')."';
										}
										var cantborradores=0;
										var borradores='';
										jQuery.each(contratos, function(index,value) {    
											if(1*value>0) {
											jQuery.ajax({type:'POST',async:false, url: 'genera_cobros_guarda.php?id_contrato='+value+'&incluye_honorarios=0&individual=true&incluye_gastos=1&generar_silenciosamente=1&fecha_fin='+fecha_fin+'&fecha_ini='+fecha_ini, data:jQuery('#form_gastos').serialize()}).complete(function(data) {
										";
								echo "				if (data.status==200) {
													borradores+='<li><a href=\"cobros5.php?id_cobro='+data.responseText+'&popup=1\">".  __('Cobro') ." #'+data.responseText+'</a></li>';
													cantborradores++;
												}
											});
											}
										});";
									echo	"	if(cantborradores>0) {
												if(typeof(window.tablagastos.fnDraw)=='function'  )  window.tablagastos.fnDraw();
												jQuery('#dialog-confirm').attr('title','Aviso').append('<p style=\"text-align:center;padding:10px;\">Se ha generado los siguientes borradores:<ul>'+borradores+'</ul>');
													jQuery( '#dialog:ui-dialog' ).dialog( 'destroy' );
													jQuery( '#dialog-confirm' ).dialog({
														resizable: false,						autoOpen:true,						height:200,						width:450,
														modal: true,
														close:function(ev,ui) {
															jQuery(this).html('');
														},
														buttons: {";
													echo 		'" '. __('OK')  .'": function() {
																jQuery( this ).dialog( "close" );
																return true;
																} 
															 }
														 });
									 	}
									} else { ';
								echo "		alert('Esta función sólo puede utilizarse para un cliente que registre deuda no cobrada mayor a cero');
											return false;
									}
									 
									 
								}
							},";

}
