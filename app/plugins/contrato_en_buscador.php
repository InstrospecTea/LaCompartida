<?php

/*
 *Este plugin agrega la capacidad de cambiar un asunto de un contrato a otro
 * 
 */

 
$Slim=Slim::getInstance('default',true);
 
$Slim->hook('hook_imprimir_buscador', 'ImprimeContratoEnBuscador');
$Slim->hook('hook_editar_contrato', 'CamposContrato');  
$Slim->hook('hook_guardar_contrato', 'GuardarContrato');  

$Slim->hook('hook_query_seguimiento_cobro', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_trabajos', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_generar_cobro', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_facturas', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_reporte_liq_no_facturadas', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_reporte_avanzado', 'Codigo_Contrato_En_Query');
$Slim->hook('hook_query_asuntos', 'Codigo_Contrato_En_Query');

$Slim->hook('hook_buscador_asuntos', 'Codigo_Contrato_En_Buscador');

$Slim->hook('hook_filtros_seguimiento_cobro', 'Filtro_CodigoContrato');
$Slim->hook('hook_filtros_generacion_cobro', 'Filtro_CodigoContrato');
$Slim->hook('hook_filtros_facturas', 'Filtro_CodigoContrato');
$Slim->hook('hook_filtros_trabajos', 'Filtro_CodigoContrato');
$Slim->hook('hook_filtros_reporte_liq_no_facturadas', 'Filtro_CodigoContrato');
$Slim->hook('hook_filtros_reporte_avanzado', 'Filtro_CodigoContrato');

$Slim->hook('hook_filtros_solicitudes_adelanto', 'Filtro_CodigoContrato_Readonly');
$Slim->hook('hook_filtros_asunto_contrato', 'Filtro_CodigoContrato_Readonly');


$Slim->hook('hook_activacion', function() {
	global $newactivos;
	print_r($newactivos);
	print_r($_SERVER);
	
	});

	function Filtro_CodigoContrato() {
		 
	echo ' <tr>
			<td class="buscadorlabel">Código '. __('Contrato').'</td>';
			echo '<td colspan="2" align="left">	<input type="text" name="codigo_contrato" id="codigo_contrato" value="'.$_REQUEST['codigo_contrato'].'" size="10"/>		</td>		</tr>';
	}

	function Filtro_CodigoContrato_Readonly() {
		global $id_contrato, $Contrato;
		$codigo = isset($_REQUEST['codigo_contrato']) ? $_REQUEST['codigo_contrato'] : '';
		if(empty($codigo) && $id_contrato){
			$Contrato->Load($id_contrato);
			$codigo = $Contrato->fields['codigo_contrato'];
		}
		echo ' <tr>
			<td align="right">Código '. __('Contrato').'</td>
			<td colspan="2" align="left">
				<input type="text" name="codigo_contrato" id="codigo_contrato" value="'.$codigo.'" size="10" readonly="readonly"/>
			</td>
		</tr>';
	}

	function Codigo_Contrato_En_Buscador() {
		global $b;
		$b->AgregarEncabezado("contrato.codigo_contrato", __('Contrato'), "class='al'");
	}
	
	function Codigo_Contrato_En_Query() {
		global  $where, $query, $groupby;
			
			$query.=", contrato.codigo_contrato ";
			$groupby.=", contrato.codigo_contrato ";
			 
			if(isset($_REQUEST['codigo_contrato']) && $_REQUEST['codigo_contrato']!='') {
				$where.=" AND contrato.codigo_contrato like '%".$_REQUEST['codigo_contrato']."%' ";
			}  
	}
		
	


function CamposContrato() {
	global $contrato,$cliente;
	
	 echo '<tr   class="controls controls-row "><script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
		 <td class="al"><div class="span4">'. __('Cliente').' Titular del '.__('Contrato').'
							</div></td>
								<td class="al">
								<input type="hidden" name="codigo_cliente_original" value="'.$contrato->fields['codigo_cliente'].'"/>
								<input  class="span6" id="codigo_glosa_cliente" disabled="disabled" size="70" type="text" name="codigo_glosa_cliente" value="'.$contrato->fields['codigo_cliente'] .'"  />
									</td>
		</tr>';
		
  echo '<tr   class="controls controls-row ">
						<td class="al"><div class="span4">
	 '. __('Código').' '.__('Contrato').'
							</div></td>
								<td class="al">
								
								<input  class="span1" id="codigo_contrato" size="10" type="text" name="codigo_contrato" value="'.$contrato->fields['codigo_contrato'].'"  />
									<script>
								 
								 
								jQuery(document).ready(function() {
								var codigo_cliente="'.$contrato->fields['codigo_cliente'].'"; 
									var url_ajax = "ajax.php?accion=glosa_cliente_segun_codigo_cliente&codigo_cliente="+codigo_cliente;
									jQuery.get(url_ajax,function(glosa) {
										jQuery("#codigo_glosa_cliente").val(codigo_cliente+" - "+glosa);
									});
									jQuery("#codigo_contrato").on("blur",function() {
										var id_contrato="'.$contrato->fields['id_contrato'].'";
										
											if(jQuery("#codigo_contrato").val()!=""  ) {
												var url_ajax = "ajax.php?accion=idcontrato_segun_codigo&codigo_contrato="+jQuery("#codigo_contrato").val();
												jQuery.get(url_ajax,function(datacontrato) { 
													if(datacontrato==id_contrato || datacontrato==\'0\') {
														jQuery("#alertacodigo").html("<div class=\'alert alert-success\'>Código Confirmado <a class=\'close\' data-dismiss=\'alert\'>×</a></div>");
													} else {
														jQuery("#alertacodigo").html("<div class=\'alert alert-error\'>Error! El  '. __('Código').' '.__('Contrato').' <a href=\'agregar_contrato.php?popup=1&id_contrato="+datacontrato+"\' target=\'_blank\'>"+jQuery("#codigo_contrato").val()+"</a> está en uso <a class=\'close\' data-dismiss=\'alert\'>×</a></div>");
														jQuery("#codigo_contrato").val("").focus();
													}
												});
											}
									});
								});
						 
						 
								</script></td>
							 
				 
					</tr><tr><td>&nbsp;</td><td id="alertacodigo">&nbsp;</td></tr>		';	
	
}

function GuardarContrato() {
	global $contrato;
	
	if($contrato->extra_fields['codigo_contrato']) $contrato->Edit('codigo_contrato',$contrato->extra_fields['codigo_contrato']);
}

 

function ImprimeContratoEnBuscador() {
	global $html,$contratofields;
	if($contratofields['codigo_contrato']=='') $contratofields['codigo_contrato']='N/A';
	$html.= '</br></br>'.__('Código').' '.__('Contrato').': '."<a href='javascript:void(0)' style='font-size:10px' onclick=\"nuovaFinestra('Editar_Contrato',800,600,'agregar_contrato.php?popup=1&id_contrato=".$contratofields['id_contrato']."');\" title='".__('Editar Información Comercial')."'>".$contratofields['codigo_contrato'].'</a></br>';
	
}
 
	 