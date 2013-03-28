<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('COB','DAT'));

	$pagina = new Pagina($sesion);

	$contrato = new Contrato($sesion);

	$cobros = new Cobro($sesion);


	$series_documento = new DocumentoLegalNumero($sesion);

	$query_usuario = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre";

	$query_usuario_activo = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			WHERE activo = 1 ORDER BY nombre";

	$query_cliente = "SELECT codigo_cliente, glosa_cliente FROM cliente WHERE activo = 1 ORDER BY glosa_cliente ASC";

	$query_proceso = "SELECT id_proceso FROM cobro_proceso ORDER BY id_proceso ASC";

	$query_forma_cobro = "SELECT forma_cobro, descripcion FROM prm_forma_cobro";

	if($opc == 'eliminar')
	{
		if($cobros->Load($id_cobro_hide))
		{
		    $documento_cobro = new Documento($sesion);
		    $documento_cobro->LoadByCobro($id_cobro_hide);
		    $lista_pagos = $documento_cobro->ListaPagos();


			/*FFF: cambio esta query para usar clase Documento
			$query = "SELECT count(*) FROM documento WHERE id_cobro = '".$cobros->fields['id_cobro']."'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($cont_documentos) = mysql_fetch_array($resp);*/

			$query = "SELECT count(*) FROM factura WHERE id_cobro = '".$cobros->fields['id_cobro']."'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($cont_facturas) = mysql_fetch_array($resp);

			if ($lista_pagos)
			{
				$pagina->AddError(__('El cobro N°').$cobros->fields['id_cobro'].__(' no se puede borrar porque tiene un pago asociado.'));
			}
			else if( $cont_facturas > 0 )
			{
				$pagina->AddError(__('El cobro N°').$cobros->fields['id_cobro'].__(' no se puede borrar porque tiene un documento tributario asociado.'));
			}
			else if($cobros->Eliminar())
			{
				$pagina->AddInfo(__('Cobro eliminado con éxito'));
			}
		}
	}

	if($opc == 'buscar')
	{
		if($codigo_cliente_secundario)
		{
			$cliente=new Cliente($sesion);
			$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
			$codigo_cliente=$cliente->fields['codigo_cliente'];
		}
		if($codigo_cliente)
		{
			$cliente=new Cliente($sesion);
			$cliente->LoadByCodigo($codigo_cliente);
			$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
		}
		$where = 1;
		if($id_cobro) {
			$where .= " AND cobro.id_cobro = '$id_cobro' ";
		} else if( UtilesApp::GetConf($sesion,'FacturaSeguimientoCobros') && !UtilesApp::GetConf($sesion,'NuevoModuloFactura') && !empty($numero_factura) ) {
			$where .= " AND TRIM(cobro.documento) = TRIM('$numero_factura') ";
		}
		else if( $factura || $tipo_documento_legal || $serie ){
			//$where .= " AND concat(cobro.documento, ',') LIKE '%$tipo_documento_legal $factura %' ";
			$factura_obj = new Factura($sesion);
			$lista_cobros_x_factura = $factura_obj->GetlistaCobroSoyDatoFactura('',$tipo_documento_legal,$factura,$serie);
			if($lista_cobros_x_factura == '')
				$where .= " AND cobro.id_cobro = 0";
			else
			$where .= " AND cobro.id_cobro IN ($lista_cobros_x_factura)";
		}
		else
		{
			/*
				if($proceso)
				$where .= " AND cobro.id_proceso = '$proceso' ";
			*/
			if($id_usuario)
				$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
			if($id_usuario_secundario)
				$where .= " AND contrato.id_usuario_secundario = '$id_usuario_secundario' ";
			if(!empty($forma_cobro))
				$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
			if(!empty($tipo_liquidacion) && intval($tipo_liquidacion)!=0 ) //1-2 = honorarios-gastos, 3 = mixtas
				$where .= " AND contrato.separar_liquidaciones = '".($tipo_liquidacion=='3' ? 0 : 1)."' ";

			if($rango == '' && $usar_periodo == 1)
			{
				$fecha_ini = $fecha_anio.'-'.$fecha_mes.'-01';
				$fecha_fin = $fecha_anio.'-'.$fecha_mes.'-31';
				$where .= " AND cobro.fecha_creacion >= '$fecha_ini' AND cobro.fecha_creacion <= '$fecha_fin 23:59:59' ";
			}
			elseif($fecha_ini != '' && $fecha_fin != '' && $rango == 1 && $usar_periodo == 1)
			{
				$where .= " AND cobro.fecha_creacion >= '".Utiles::fecha2sql($fecha_ini)."' AND cobro.fecha_creacion <= '".Utiles::fecha2sql($fecha_fin)." 23:59:59' ";
			}
			if($codigo_cliente)
				$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
			if(!empty($estado) && $estado[0]!='-1' ) {

				$where .= " AND cobro.estado in ('".implode("','",$estado)."') ";
			}

		}

		/*if($id_concepto)
		{
			$factura_pago = new FacturaPago($sesion);
			$lista_cobros = $factura_pago->GetListaFacturasSoyPago($id_concepto,'id_concepto','id_cobro');
			if(!empty($lista_cobros))
				$where .= " AND cobro.id_cobro IN (".$lista_cobros.")";
			else
				$where .= " AND cobro.id_cobro IS NULL";
		}*/
		if($codigo_asunto)
		{
 			$where.=" AND contrato.id_contrato in (select id_contrato from asunto where asunto.codigo_asunto='$codigo_asunto') ";
		}
		if($codigo_asunto_secundario)
		{
			$where .= " AND a2.codigo_asunto_secundario ='".$codigo_asunto_secundario."' ";
		}
		if(!empty($tipo_liquidacion) && $tipo_liquidacion!='')
			$where .= " AND cobro.incluye_honorarios = '".($tipo_liquidacion&1)."' ". 	" AND cobro.incluye_gastos = '".($tipo_liquidacion&2?1:0)."' ";

		$mostrar_codigo_asuntos = "";
		if (UtilesApp::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
			$mostrar_codigo_asuntos = "asunto.codigo_asunto";
			if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
				$mostrar_codigo_asuntos .= "_secundario";
			}
			$mostrar_codigo_asuntos .= ", ' ', ";
		}
                        // FFF 25-01-2012 el estado de la factura se saca de la tabla factura en el mod viejo, y de prm_estado_factura en el nuevo. Ademas, según el conf sale con o sin N de Serie
                                                        if(UtilesApp::GetConf($sesion,'NuevoModuloFactura')) {

                                                            $joinfactura="left join factura f1 on cobro.id_cobro=f1.id_cobro
                                                                          left join prm_documento_legal prm on f1.id_documento_legal=prm.id_documento_legal
                                                                          left join prm_estado_factura pef on f1.id_estado=pef.id_estado ";
                                                            if (UtilesApp::GetConf($sesion, 'NumeroFacturaConSerie')) {
                                                                $documentof=" group_concat(DISTINCT concat(' ',prm.codigo,' ', lpad(ifnull(serie_documento_legal,1),3,'000'),'-', numero,if(pef.glosa='Anulado', ' (Anulado)','')))    ";
															}  else {
                                                                $documentof=" group_concat(DISTINCT concat(' ',prm.codigo,' ', numero,if(pef.glosa='Anulado', ' (Anulado)',''))) ";
															}
							}  else if(UtilesApp::GetConf($sesion,'PermitirFactura')) {

                                                            $joinfactura="left join factura f1 on cobro.id_cobro=f1.id_cobro
                                                                          left join prm_documento_legal prm on f1.id_documento_legal=prm.id_documento_legal ";
                                                            $documentof=" group_concat(DISTINCT concat(' ',prm.codigo,' ', lpad(ifnull(serie_documento_legal,1),3,'000'),'-', numero,if(f1.anulado=1, ' (Anulado)','')))   ";
														}   else {
															$joinfactura = "";
															$documentof = " cobro.documento ";
														}

						                    if(isset($_POST['tienehonorario'])) $where.=" AND documento.subtotal_honorarios>0";
											if(isset($_POST['tienegastociva'])) $where.=" AND documento.subtotal_gastos>0";
											if(isset($_POST['tienegastosiva'])) $where.=" AND documento.subtotal_gastos_sin_impuesto>0";
											if(isset($_POST['tienetramites'])) $where.=" AND documento.monto_tramites>0";


		($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_query_seguimiento_cobro'):false;



		$query = "SELECT SQL_CALC_FOUND_ROWS
								cobro.id_cobro,
								cobro.monto as cobro_monto,
								cobro.monto_subtotal,
								cobro.descuento,
								cobro.impuesto,
								cobro.monto_gastos as monto_gastos,
								cobro.subtotal_gastos,
								cobro.impuesto_gastos,
								cobro.fecha_ini,
								cobro.fecha_fin,
								moneda.simbolo,
								cobro.id_proceso,
								cobro.codigo_idioma,
								cobro.forma_cobro as cobro_forma,
								$documentof as documento,
								cobro.estado,
								moneda_monto.simbolo as simbolo_moneda_contrato,
								moneda_monto.cifras_decimales as cifras_decimales_moneda_contrato,
								moneda_total.simbolo as simbolo_moneda_total,
								moneda_total.cifras_decimales as cifras_decimales_moneda_total,
								contrato.id_contrato,
								contrato.codigo_cliente,
								cliente.glosa_cliente,
								contrato.forma_cobro,
								contrato.monto,
								moneda.simbolo,
								moneda.cifras_decimales,
								cobro.incluye_honorarios as incluye_honorarios,
								cobro.incluye_gastos as incluye_gastos, ";

							if (UtilesApp::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
								$query.=" GROUP_CONCAT(DISTINCT " . str_replace('asunto.', 'a2.', $mostrar_codigo_asuntos) . " a2.glosa_asunto SEPARATOR ', ') as asuntos_cobro,";
							}

								$query.="	CONCAT(moneda_monto.simbolo, ' ', contrato.monto) AS monto_total,
								tarifa.glosa_tarifa
							FROM contrato join cobro ON cobro.id_contrato = contrato.id_contrato
							 JOIN prm_moneda as moneda ON cobro.id_moneda = moneda.id_moneda
							 JOIN cliente ON cobro.codigo_cliente = cliente.codigo_cliente


							LEFT JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
							LEFT JOIN prm_moneda as moneda_total ON cobro.opc_moneda_total = moneda_total.id_moneda
							LEFT JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
							left JOIN cobro_asunto ON cobro_asunto.id_cobro = cobro.id_cobro ";

							if (UtilesApp::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
									$query.=" LEFT JOIN asunto a2 ON cobro_asunto.codigo_asunto = a2.codigo_asunto ";
							} else if ($codigo_asunto_secundario){
								$query.=" JOIN asunto as a2 ON cobro_asunto.codigo_asunto = a2.codigo_asunto ";
							}
							$query.=" left join documento on documento.id_cobro=cobro.id_cobro and documento.tipo_doc='N'
                                                        $joinfactura
                                                        WHERE $where
							GROUP BY cobro.id_cobro, cobro.id_contrato";

		$x_pag = 20;
		$orden = ' cobro.id_cobro DESC, cliente.glosa_cliente, cliente.codigo_cliente, cobro.id_contrato';


                $b = new Buscador($sesion, $query, "Cobro", $desde, $x_pag, $orden);
		$b->mensaje_error_fecha = "N/A";
		$b->nombre = "busc_gastos";
		$b->titulo = __('Seguimiento de cobros');
		$b->AgregarEncabezado("glosa_cliente",__('Cliente'),"","","SplitDuracion");
		$b->AgregarEncabezado("asuntos",__('Asunto'),"align=left");
		$b->AgregarEncabezado("id_contrato",__('Acuerdo'),"align=left");
		$b->AgregarFuncion("Opci&oacute;n",'Opciones',"align=center nowrap width=8%");
		$b->funcionTR = "funcionTR";

		function funcionTR(& $cobro)
		{
			global $sesion;
			global $id_cobro;
			global $p_revisor;
			global $cobros;
			global $opc;
			global $fecha_fin;
			global $proceso;
			global $j;
			static $i = 0;
			global $codigo_cliente_ultimo, $id_contrato_ultimo;
			if($i % 2 == 0)
				$color = "#dddddd";
			else
				$color = "#ffffff";

			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $cobro->fields['codigo_idioma'] != '' )
				$idioma->Load($cobro->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));

			$cols = 4;
			if (  UtilesApp::GetConf($sesion,'FacturaSeguimientoCobros') )
				{
					$cols++;
				}

			if($cobro->fields['codigo_cliente'] != $codigo_cliente_ultimo || $id_contrato_ultimo != $cobro->fields['id_contrato'])
			{
				$j++;
				$html .= $codigo_cliente_ultimo != '' ? "<tr bgcolor=$color style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'><td colspan=4><hr size='1px'></td>" : "";

				$html .= "<tr id=foco".$j." bgcolor=$color style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'>";
				$html .= "<td style='font-size:10px' align=center valing=top><b>".$cobro->fields['glosa_cliente']."</b></td>";
				$html .= "<td style='font-size:10px' class='btpopover' id='tip_{$cobro->fields['id_contrato']}' align=left valing=top></td>";
				if($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL')
					$texto_acuerdo = $cobro->fields['forma_cobro']." de ".$cobro->fields['simbolo_moneda_contrato']." ".number_format($cobro->fields['monto'],$cobro->fields['cifras_decimales_moneda_contrato'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles'])." por ". number_format($cobro->fields['retainer_horas'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . " Hrs.";
				else if( $cobro->fields['forma_cobro'] == 'TASA' || $cobro->fields['forma_cobro'] == 'HITOS' || $cobro->fields['forma_cobro'] == 'ESCALONADA' )
					$texto_acuerdo = $cobro->fields['forma_cobro'];
                                else
                                        $texto_acuerdo = $cobro->fields['forma_cobro']." por ".$cobro->fields['simbolo_moneda_contrato']." ".number_format($cobro->fields['monto'],$cobro->fields['cifras_decimales_moneda_contrato'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
		    	$html .= "<td style='font-size:10px' align=left colspan=2 valign=top><b>".$texto_acuerdo.', Tarifa: '.$cobro->fields['glosa_tarifa']."</b>&nbsp;&nbsp;<a href='javascript:void(0)' style='font-size:10px' onclick=\"nuovaFinestra('Editar_Contrato',800,600,'agregar_contrato.php?popup=1&id_contrato=".$cobro->fields['id_contrato']."');\" title='".__('Editar Información Comercial')."'>Editar</a></td>";
		    	$html .= "</tr>";
		    	//$html .="<script> new Tip('tip_".$j."', '".$cobro->fields['asuntos']."', {title : '".__('Listado de asuntos')."', effect: '', offset: {x:-2, y:10}}); </script>";

		    	$ht = "<tr bgcolor='#F2F2F2'>
							<td align=center style='font-size:10px; width: 70px;'>
								<b>".__('N° Cobro')."</b>
							</td>";



				$ht .=	    "<td style='font-size:10px; ' align=left>
								<b>&nbsp;&nbsp;&nbsp;Descripción " . __('del cobro') . "</b>
							</td>";
				if( 	UtilesApp::GetConf($sesion,'FacturaSeguimientoCobros'))
				{
				$ht .=	"<td align=center style='font-size:10px; width: 70px;'>
								<b>N° Factura</b>
							</td>";
				}
				$ht .= "<td style='font-size:10px; width: 52px;' align=center>
								<b>Opción</b>
							</td></tr>";
	    		$ht .= "<tr bgcolor='#F2F2F2'><td align=center colspan=4><hr size=1px style='font-size:10px; border:1px dashed #CECECE'></td><tr>";
				$codigo_cliente_ultimo = $cobro->fields['codigo_cliente'];
	            $id_contrato_ultimo = $cobro->fields['id_contrato'];
		    }
			$total_horas = $cobros->TotalHorasCobro($cobro->fields['id_cobro']);
		    $html .= "<tr bgcolor='#F2F2F2' style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'>";
			$html .= "<td align=center colspan=".$cols."><div style='font-size:10px; border:1px dashed #CECECE'>";
			$html .= "<table width='100%' cellSpacing='0' cellPadding='0'>";
			$html .= $ht;
			$html .= "<tr onmouseover=\"this.bgColor='#bcff5c'\" onmouseout=\"this.bgColor='#F2F2F2'\">
				<td align=right style='font-size:10px; width: 70px;'>#".$cobro->fields['id_cobro']."</td>";



			if (empty($cobro->fields['incluye_honorarios'])) {
				$texto_tipo = '(sólo gastos)';
			} else if (empty($cobro->fields['incluye_gastos'])) {
				$texto_tipo = '(sólo honorarios)';
			} else {
				$texto_tipo = '';
			}

			$txt_iva = __('IVA');
			$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['cobro_monto'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			if(!empty($cobro->fields['impuesto'])){
				$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['monto_subtotal'] - $cobro->fields['descuento'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) .
					" + $txt_iva ($honorarios)";
			}

			$texto_honorarios = "$honorarios por <a href=\"horas.php?from=reporte&id_cobro={$cobro->fields['id_cobro']}\">" .
				number_format($total_horas, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' Hrs.</a> ';

			$gastos = $cobro->fields['simbolo_moneda_total'] . ' ' . number_format($cobro->fields['monto_gastos'], $cobro->fields['cifras_decimales_moneda_total'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

			if(!empty($cobro->fields['impuesto_gastos'])){
				$gastos = $cobro->fields['simbolo_moneda_total'] . ' ' . number_format($cobro->fields['subtotal_gastos'], $cobro->fields['cifras_decimales_moneda_total'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) .
					" + $txt_iva ($gastos)";
			}
			$texto_gastos = "$gastos en gastos ";

			if (!empty($cobro->fields['incluye_honorarios']) && !empty($cobro->fields['incluye_gastos']) && !empty($cobro->fields['monto_gastos'])) {
				$texto_monto = "$texto_honorarios y $texto_gastos";
			} else if (!empty($cobro->fields['incluye_honorarios'])) {
				$texto_monto = $texto_honorarios;
			} else {
				$texto_monto = $texto_gastos;
			}

			$html .= "<td align=left style='font-size:10px; ' >&nbsp;".$texto_tipo." de ".$texto_monto.$texto_horas.' ';
			if($cobro->fields['fecha_ini'] != '0000-00-00')
				$fecha_cobro = __('desde').' '.Utiles::sql2date($cobro->fields['fecha_ini']);
			if($cobro->fields['fecha_fin'] != '0000-00-00')
				$fecha_cobro .= ' '.__('hasta').' '.Utiles::sql2date($cobro->fields['fecha_fin']).' ';
			$html .= $fecha_cobro;
			#$html .= ' -  [Proc. '.$cobro->fields['id_proceso'].'] ';
			$html .= "<span style='font-size:8px'>- (".$cobro->fields['estado'].")</span>";

			if (UtilesApp::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
				$asuntos_separados = explode(', ',$cobro->fields['asuntos_cobro']);
				$cantidad_asuntos = count($asuntos_separados);
				$html .= " <strong id=\"tip_asuntos_cobro_" . $cobro->fields['id_cobro'] . "\">" . $cantidad_asuntos . "&nbsp;asunto" . ($cantidad_asuntos > 1 ? "s" : "") . "</strong>";
				$html .="<script> new Tip('tip_asuntos_cobro_" . $cobro->fields['id_cobro'] . "', '" . '<li>' . implode('</li><li>', $asuntos_separados) . '</li>' . "', {title : '".__('Listado de asuntos')."', effect: '', offset: {x:-2, y:10}}); </script>";
			}

			$html .= "</td>";
			if( UtilesApp::GetConf($sesion,'FacturaSeguimientoCobros'))
			{
					$html .= "<td align=center style='font-size:10px; width: 70px;'>&nbsp;";
					if($cobro->fields['documento'])
						$html.= "#".$cobro->fields['documento'];
					$html .= "</td>";
			}
			$html .= "<td align=center style=\"width: 52px;\"><img src='".Conf::ImgDir()."/editar_on.gif' title='".__('Continuar con el cobro')."' border=0 style='cursor:pointer' onclick=\"nuevaVentana('Editar_Contrato',1050,700,'cobros6.php?id_cobro=".$cobro->fields['id_cobro']."&popup=1&contitulo=true&id_foco=".$j."', '');\">&nbsp;";
			#if($cobro->fields['estado'] == 'EMITIDO' || $cobro->fields['estado'] == 'CREADO')
			if(  UtilesApp::GetConf($sesion,'UsaDisenoNuevo') ) {
				$html .=  "<img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' title='".__('Eliminar cobro')."' border=0 style='cursor:pointer' onclick=\"EliminarCobros('".$cobro->fields['id_cobro']."','".$cobro->fields['estado']."')\">";
			} else {
				$html .=  "<img src='".Conf::ImgDir()."/cruz_roja.gif' title='".__('Eliminar cobro')."' border=0 style='cursor:pointer' onclick=\"EliminarCobros('".$cobro->fields['id_cobro']."','".$cobro->fields['estado']."')\">";
			}
			$html .= "</td></table>";
		    $html .= "</div></tr>";
		    $ht = '';
		    return $html;
		}
	}#Buscar

	$pagina->titulo = __('Seguimiento de cobros');

	$pagina->PrintTop();

?>
 
<script type="text/javascript">
	var AsuntosContrato=new Array();
	<?php
	$prequery="select id_contrato, concat('<li>',GROUP_CONCAT(DISTINCT  ${mostrar_codigo_asuntos} asunto.glosa_asunto SEPARATOR '</li><li>'),'</li>') as asuntos from contrato left join asunto using (id_contrato) group by id_contrato;";
		$asuntosST=$sesion->pdodbh->query($prequery);
		$asuntosRS=$asuntosST->fetchAll(PDO::FETCH_ASSOC);
	foreach($asuntosRS as $contrato) {
		$asuntos=str_replace(array("\r","\n","'"),"",$contrato['asuntos']);
		echo "\nAsuntosContrato[{$contrato['id_contrato']}]='{$asuntos}';";
	}
	?>
		jQuery(document).ready(function() {

  jQuery.ajax({async: true,cache:true, type: "GET", url: "//static.thetimebilling.com/js/bootstrap.min.js"  ,
                dataType: "script",
                complete: function() {
                

			jQuery('.btpopover').each(function() {
				var idContrato=jQuery(this).attr('id').replace('tip_','');
				jQuery(this).popover({title:'Listado de <?php echo __('asuntos'); ?>', trigger:'hover',animation:true, content:AsuntosContrato[idContrato]});

				jQuery(this).append("<span class='asuntos_del_contrato' style='font-weight:bold;'>"+AsuntosContrato[idContrato]+"</span>");
			}  );
  
                }
            });


	});



//Genera o buisca los cobros.
function GeneraCobros(form, desde, opcion)
{
	if(!form)
		var form = $('form_busca');

	if(desde == 'genera')
	{
		if(confirm('<?php echo __("¿Ud. desea generar los cobros?")?>'))
		{
			form.action = 'genera_cobros_guarda.php';
			form.submit();
		}
		else
			return false;
	}
	else if(desde == 'print')
	{
		form.action = 'genera_cobros_guarda.php?print=true&opcion='+opcion;
		form.submit();
	}
	else if(desde == 'emitir')
	{
		if(confirm('<?php echo __("¿Ud. desea emitir los cobros?")?>'))
		{
			form.action = 'genera_cobros_guarda.php?emitir=true';
			form.submit();
		}
		else
			return false;
	}
	else
	{
		form.action = 'seguimiento_cobro.php';
		form.opc.value = 'buscar';
		form.submit();
	}
}

function SubirExcel()
{
	nuevaVentana("Subir_Excel",500,300,"subir_excel.php");
}

//Elimina Cobro
function EliminarCobros(id_cobro, estado)
{
	if(estado != 'CREADO' && estado != 'EN REVISION')
	{
		var text_window = "<img src='<?php echo Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA")?></u><br><br>";
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('El cobro seleccionado se encuentra en estado EMITIDO, Ud. debe cambiarlo a estado CREADO o EN REVISION para poder eliminarlo.')?>.</span><br>';
		text_window += '<br><table><tr>';
		text_window += '</table>';
		Dialog.confirm(text_window,
		{
			top:150, left:290, width:400, okLabel: "<?php echo __('Continuar')?>", cancelLabel: "<?php echo __('Cancelar')?>", buttonClass: "btn", className: "alphacube",
			id: "myDialogId",
			cancel:function(win){ return false; },
			ok:function(win){ nuevaVentana('Editar_Contrato',1050,700,'cobros6.php?id_cobro='+id_cobro+'&popup=1&contitulo=true'); return true; }
		});
	}
	else if(estado == 'CREADO' || estado == 'EN REVISION')
	{
		var text_window = "<img src='<?php echo Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA")?></u><br><br>";
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('¿Desea eliminar el cobro seleccionado?')?></span><br>';
		text_window += '<br><table><tr>';
		text_window += '</table>';
		Dialog.confirm(text_window,
		{
			top:150, left:290, width:400, okLabel: "<?php echo __('Aceptar')?>", cancelLabel: "<?php echo __('Cancelar')?>", buttonClass: "btn", className: "alphacube",
			id: "myDialogId",
			cancel:function(win){ return false; },
			ok:function(win){ DeleteCobro(id_cobro); return true; }
		});
	}
	else
		return false;
}

function DeleteCobro(id_cobro)
{
	var form = $('form_busca');
	form.id_cobro_hide.value = id_cobro;
	form.opc.value = 'eliminar';
	form.submit();
}

/*
	Despliega periodos o rango para filtros
*/
function Rangos(obj, form)
{
	var td_show = $('periodo_rango');
	var td_hide = $('periodo');

	if(obj.checked)
	{
		td_hide.style['display'] = 'none';
		td_show.style['display'] = 'inline';
	}
	else
	{
		td_hide.style['display'] = 'inline';
		td_show.style['display'] = 'none';
	}
}

function Refrescar(id_foco)
{
	//var form = $('form_busca');

	var factura = $('factura').value;
	var proceso = $('proceso').value;
	var codigo_cliente = $('codigo_cliente').value;
	var codigo_asunto = $('codigo_asunto').value;
	var forma_cobro = $('forma_cobro').value;
	var tipo_liquidacion = $('tipo_liquidacion') ?  $('tipo_liquidacion').value : '';
	var id_usuario = $('id_usuario').value;
	var id_usuario_secundario = $('id_usuario_secundario') ? $('id_usuario_secundario').value : '';
	var id_cobro = $('id_cobro').value;
	if ( $('usar_periodo').checked == true )
		var usar_periodo = $('usar_periodo').value;
	else
		var usar_periodo = '';
	if ( $('rango').checked == true )
		var rango = $('rango').value;
	else
		var rango = '';

	var fecha_mes = $('fecha_mes').value;
	var fecha_anio = $('fecha_anio').value;
	var fecha_ini = $('fecha_ini').value;
	var fecha_fin = $('fecha_fin').value;
	var estado = $('estado').value;
<?php
	if ($orden)
		echo "var orden = '&orden=" . $orden . "';";
	else
		echo "var orden = '';";
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
?>
	var url = "seguimiento_cobro.php?id_usuario="+id_usuario+"&tipo_liquidacion="+tipo_liquidacion+"&forma_cobro="+forma_cobro+"&id_usuario_secundario="+id_usuario_secundario+"&id_cobro="+id_cobro+"&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto+"&opc=buscar"+pagina_desde+"&usar_periodo="+usar_periodo+"&rango="+rango+"&proceso="+proceso+"&fecha_ini="+fecha_ini+"&fecha_mes="+fecha_mes+"&fecha_anio="+fecha_anio+"&fecha_fin="+fecha_fin+"&estado="+estado+orden+"&id_foco="+id_foco;

	self.location.href = url;
}




function ShowDiv(div, valor, dvimg)
{
	var div_id = document.getElementById(div);
	var img = document.getElementById(dvimg);
	var form = document.getElementById('form_editar_trabajo');
	var codigo = document.getElementById('campo_codigo_cliente').value;
	var tr = document.getElementById('tr_cliente');
	var tr2 = document.getElementById('tr_asunto');
	var al = document.getElementById('al');
	//var tbl_trabajo = document.getElementById('tbl_trabajo');

	DivClear(div, dvimg);

	if( div == 'tr_asunto' && codigo == '')
	{
		tr.style['display'] = 'none';
		alert("<?php echo __('Debe seleccionar un cliente')?>");
		form.codigo_cliente.focus();
		return false;
	}

	div_id.style['display'] = valor;
	/* FADE
	if(valor == 'inline')
		var fade = true;
	else
		var fade = false;
	setTimeout("MSG('"+div+"',"+fade+")",10);
	*/

	if( div == 'tr_cliente' )
	{
		WCH.Discard('tr_asunto');
		tr2.style['display'] = 'none';
		Lista('lista_clientes','left_data','','');
	}
	else if( div == 'tr_asunto' )
	{
		WCH.Discard('tr_cliente');
		tr.style['display'] = 'none';
		Lista('lista_asuntos','content_data2',codigo,'2');
	}

	/*Cambia IMG*/
	if(valor == 'inline')
	{
		WCH.Apply('tr_asunto');
		WCH.Apply('tr_cliente');
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\''+div+'\',\'none\',\''+dvimg+'\');">';
	}
	else
	{
		WCH.Discard(div);
		img.innerHTML = '<img src="<?php echo Conf::ImgDir()?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\''+div+'\',\'inline\',\''+dvimg+'\');">';
	}
}
<?php  if($id_foco)   { ?>
self.location.href = self.location.href + "#foco" + <?php echo $id_foco ?>;</script>
<?php  } ?>
</script>

<form name=form_busca id=form_busca action='' method=post>
<input type=hidden name='opc' id='opc' value=''>
<input type=hidden name='id_cobro_hide' value=''>




<fieldset class="tb_base" style="width:850px;">
<legend><?php echo 'Filtros'?></legend>
	<table  >
		<tr>
			<td align=right width='30%'><b><?php echo __('Cobro')?></b></td>
			<td colspan=2 align=left>
				<input onkeydown="if(event.keyCode==13)GeneraCobros(this.form, '',false)" type=text size=6 name=id_cobro id=id_cobro value="<?php echo $id_cobro ?>">
				<input onkeydown="if(event.keyCode==13)GeneraCobros(this.form, '',false)" type=hidden size=6 name=proceso id=proceso value="<?php echo $proceso ?>">
				<?php if( UtilesApp::GetConf($sesion,'FacturaSeguimientoCobros') && !UtilesApp::GetConf($sesion,'NuevoModuloFactura') ) { ?>
					&nbsp;&nbsp;<b><?php echo __('N° Factura')?></b>&nbsp;
					<input onkeydown="if(event.keyCode==13)GeneraCobros(this.form, '',false)" type=text size=6 name=numero_factura id=numero_factura value="<?php echo $numero_factura ?>">
				<?php } ?>
			</td>
		</tr>
		<?php
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
		{ ?>
		<tr>
			<td align=right width='30%'><b><?php echo __('Documento legal')?></b></td>
			<td colspan=2 align=left>
				<?php echo  Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal', $tipo_documento_legal, '', __('Cualquiera'), 100); ?>
				<?php echo Html::SelectQuery($sesion, $series_documento->SeriesQuery(), "serie", str_pad($serie, 3, '0', STR_PAD_LEFT), '', __('Serie'), 60); ?>
				<input onkeydown="if(event.keyCode==13)GeneraCobros(this.form, '',false)" type="text" size="6" name="factura" id="factura" value="<?php echo $factura ?>">
			</td>
		</tr>
		<?php
		}
		?>
	<tbody id="selectclienteasunto">
                        <tr >
                            <td align="right" width='30%'><?php echo '<b>'. __('Nombre Cliente').'</b>'; ?> </td>
                            <td nowrap colspan="3" align="left"><?php UtilesApp::CampoCliente($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>    </td>
                        </tr>
                        <tr>
                            <td align="right"> <?php echo '<b>'. __('Asunto').'</b>'; ?> </td>
			<td nowrap colspan="3" align="left"> <?php   UtilesApp::CampoAsunto($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?> </td>
                        </tr>
	</tbody>

		<tr>
			<td align=right><b><?php echo __('Encargado comercial')?>&nbsp;</b></td>
			<td colspan=2 align=left><?php echo Html::SelectQuery($sesion,$query_usuario,"id_usuario",$id_usuario,'',__('Cualquiera'),'210')?>
		</tr>
		<?php if(UtilesApp::GetConf($sesion, 'EncargadoSecundario')){ ?>
		<tr>
			<td align=right><b><?php echo __('Encargado Secundario')?>&nbsp;</b></td>
			<td colspan=2 align=left><?php echo Html::SelectQuery($sesion,$query_usuario_activo,"id_usuario_secundario",$id_usuario_secundario, '',__('Cualquiera'),'210')?>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type=hidden size=6 name=id_proceso id=id_proceso value='<?php echo $id_proceso?>' >
			</td>
		</tr>
		<?php }

		($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_filtros_seguimiento_cobro'):false; ?>

		<tr>
			<td align=right><b><?php echo __('Forma de Tarificación')?>&nbsp;</b></td>
			<td colspan=2 align=left>
				<?php echo Html::SelectQuery($sesion,$query_forma_cobro,"forma_cobro",$forma_cobro,'',__('Cualquiera'),'210')?>
			</td>
		</tr>
		<tr>
			<td align=right><b><?php echo __('Tipo de Liquidación')?>&nbsp;</b></td>
			<td colspan=2 align=left>
				<?php echo Html::SelectArray(array(
					array('1', __('Sólo Honorarios')),
					array('2', __('Sólo Gastos')),
					array('3', __('Sólo Mixtas (Honorarios y Gastos)'))), 'tipo_liquidacion', $tipo_liquidacion, '', __('Todas'))?>
			</td>
		</tr>
		<tr>
			<td align=right><input type=checkbox name=usar_periodo id=usar_periodo value=1 <?php echo $usar_periodo ? 'checked' : '' ?>><b><?php echo __('Periodo creación') ?></b></td>
			<td align=left colspan=2>
				<input type="checkbox" name="rango" id="rango" value="1" <?php echo $rango ? 'checked' : '' ?> onclick='Rangos(this, this.form);' title='Otro rango' />&nbsp;<span style='font-size:9px'><?php echo __('Otro rango') ?></span>
<?php
				$fecha_mes = $fecha_mes != '' ? $fecha_mes : date('m');
?>
				<div id=periodo style='display:<?php echo !$rango ? 'inline' : 'none' ?>;'>

	<?php

	  echo Html::SelectArray(array(
					array('1', __('Enero')),
					array('2', __('Febrero')),
					array('3', __('Marzo')),
					array('4', __('Abril')),
					array('5', __('Mayo')),
					array('6', __('Junio')),
					array('7', __('Julio')),
					array('8', __('Agosto')),
					array('9', __('Septiembre')),
					array('10', __('Octubre')),
					array('11', __('Noviembre')),
					array('12', __('Diciembre')),
					  )
					, 'fecha_mes', $fecha_mes, '', __('Mes'),'80px') ;

		    if(!$fecha_anio)
		    	$fecha_anio = date('Y');
	?>
		    <select name="fecha_anio" id='fecha_anio' style='width:55px'>
		    	<?php  for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
		    	<option value='<?php echo $i?>' <?php echo $fecha_anio == $i ? 'selected' : '' ?>><?php echo $i ?></option>
		    	<?php  } ?>
		    </select>
			</div>
			<br>
			<div id=periodo_rango style='display:<?php echo $rango ? 'inline' : 'none' ?>;'>
					<?php echo __('Fecha desde')?>:
		  	    <input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
 		  	    <br />
					<?php echo __('Fecha hasta')?>:&nbsp;
	    	    <input type="text" name="fecha_fin" class="fechadiff"  value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
 			</div>
			</td>
		</tr>
		<script> new Tip('usar_periodo', '<?php echo __('Seleccione esta opción para utilizar el filtro periodo')?>', {title : '', effect: '', offset: {x:-2, y:19}}); </script>
		<tr>
			<td align=right><b><?php echo __('Estado') ?></b></td>
			<td align=left colspan=2>
				<?php echo Html::SelectQuery($sesion,"SELECT codigo_estado_cobro FROM prm_estado_cobro ORDER BY orden","estado[]",$estado,'multiple="multiple" size="7"',__('Vacio'),'150')?>
			</td>
		</tr>
<tr>

			<div style="text-align: left;position: absolute;left: 600px;top: 300px;">
			<br/><input type="checkbox" name="tienehonorario"  value="1" id="tienehonorario" <?php if (isset($_POST['tienehonorario'])) echo 'checked="checked"'; ?> /> Tiene <?php echo __('Honorarios');?>
			<br/><input type="checkbox" name="tienegastociva"   value="1" id="tienegastociva"  <?php if (isset($_POST['tienegastociva'])) echo 'checked="checked"'; ?>/> Tiene <?php echo __('Gastos c/ IVA');?>
			<br/><input type="checkbox" name="tienegastosiva"   value="1" id="tienegastosiva"  <?php if (isset($_POST['tienegastosiva'])) echo 'checked="checked"' ; ?>/> Tiene <?php echo __('Gastos s/ IVA');?>
			<br/><input type="checkbox"  name="tienetramites"  value="1"   id="tienetramites" <?php if (isset($_POST['tienetramites'])) echo 'checked="checked"'; ?> /> Tiene <?php echo __('Trámites');?>
			</div>
		</tr>


		<!--<tr>
			<td align=right><b><?php echo __('Concepto') ?></b></td>
			<td align=left colspan=2>
				<?php echo  Html::SelectQuery($sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden","id_concepto", $id_concepto, '',__('Cualquiera'),'160'); ?>
			</td>
		</tr>-->
		<tr>
			<td></td>
			<td align=left>
			<a class="btn botonizame"  href="javascript:void(0);" icon="find" name='boton_buscar' id='boton_buscar' onclick="GeneraCobros(jQuery('#form_busca').get(0), '',false)"><?php echo __('Buscar')?></a>
			<a style="float:right;margin-right:20px;" class="btn botonizame" href="javascript:void(0);"  icon="upload" onclick="SubirExcel();">Subir excel</a></td>
		</tr>
	</table>

</fieldset>

</form>



<?php
	if($opc == 'buscar')
		$b->Imprimir('');


$pagina->PrintBottom($popup);

