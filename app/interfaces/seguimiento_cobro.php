<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB', 'DAT'));
$pagina = new Pagina($sesion);
$contrato = new Contrato($sesion);
$cobros = new Cobro($sesion);
$Form = new Form;
global $contratofields;
$series_documento = new DocumentoLegalNumero($sesion);

$query_usuario = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre";

$query_usuario_activo = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			WHERE activo = 1 ORDER BY nombre";

$query_cliente = "SELECT codigo_cliente, glosa_cliente FROM cliente WHERE activo = 1 ORDER BY glosa_cliente ASC";

$query_proceso = "SELECT id_proceso FROM cobro_proceso ORDER BY id_proceso ASC";

$query_forma_cobro = "SELECT forma_cobro, descripcion FROM prm_forma_cobro";

if ($opc == 'eliminar') {
	try {
		$cobros->Eliminar($id_cobro_hide);
	} catch (Exception $e) {
		$pagina->AddError($e->getMessage() . '.');
	}
	$pagina->AddInfo(__('Cobro eliminado con �xito') . '.');
}

if ($opc == 'buscar') {

	if ($codigo_cliente_secundario) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
	} else if ($codigo_cliente) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		$codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
	}

	$where = 1;

	if ($id_cobro) {
		$where .= " AND cobro.id_cobro = '$id_cobro' ";
	} else if (!empty($numero_factura)) {
		$where .= " AND TRIM(cobro.documento) = TRIM('$numero_factura') ";
	} else if ($factura || $tipo_documento_legal || $serie) {
		$factura_obj = new Factura($sesion);
		$lista_cobros_x_factura = $factura_obj->GetlistaCobroSoyDatoFactura('', $tipo_documento_legal, $factura, $serie);
		if ($lista_cobros_x_factura == '') {
			$where .= " AND cobro.id_cobro = 0";
		} else {
			$where .= " AND cobro.id_cobro IN ($lista_cobros_x_factura)";
		}
	} else {

		if ($proceso) {
			$where .= " AND cobro.id_proceso in ($proceso) ";
		}

		if ($id_usuario) {
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
		}
		if ($id_usuario_secundario) {
			$where .= " AND contrato.id_usuario_secundario = '$id_usuario_secundario' ";
		}
		if (!empty($forma_cobro)) {
			$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
		}

		if (empty($rango) && !empty($usar_periodo)) {
			$fecha_ini = "{$fecha_anio}-{$fecha_mes}-01";
			$fecha_fin = "{$fecha_anio}-{$fecha_mes}-31";
			$where .= " AND cobro.fecha_creacion >= '{$fecha_ini} 00:00:00' AND cobro.fecha_creacion <= '{$fecha_fin} 23:59:59' ";
		} elseif (!empty($rango) && !empty($usar_periodo) && !empty($fecha_ini) && !empty($fecha_fin)) {
			$where .= " AND cobro.fecha_creacion >= '" . Utiles::fecha2sql($fecha_ini) . " 00:00:00' AND cobro.fecha_creacion <= '" . Utiles::fecha2sql($fecha_fin) . " 23:59:59' ";
		}

		if ($codigo_cliente) {
			$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
		}
		if (!empty($estado) && $estado[0] != '-1') {
			$where .= " AND cobro.estado in ('" . implode("','", $estado) . "') ";
		}

		if ($id_grupo_cliente) {
			$where .= " AND (cliente.id_grupo_cliente = '{$id_grupo_cliente}' OR grupo_cliente.id_grupo_cliente = '{$id_grupo_cliente}') ";
		}
	}

	if ($codigo_asunto) {
		$where.=" AND contrato.id_contrato in (select id_contrato from asunto where asunto.codigo_asunto='$codigo_asunto') ";
	}
	if ($codigo_asunto_secundario) {
		$where .= " AND contrato.id_contrato in (select id_contrato from asunto WHERE asunto.codigo_asunto_secundario ='" . $codigo_asunto_secundario . "') ";
	}
	if (!empty($glosa_asunto) && empty($codigo_asunto) && empty($codigo_asunto_secundario)) {
		$where .= " AND contrato.id_contrato in (select id_contrato from asunto WHERE asunto.glosa_asunto  LIKE '%{$glosa_asunto}%') ";
	}
	if (!empty($tipo_liquidacion) && $tipo_liquidacion != '') {
		$where .= " AND cobro.incluye_honorarios = '" . ($tipo_liquidacion & 1) . "' " . " AND cobro.incluye_gastos = '" . ($tipo_liquidacion & 2 ? 1 : 0) . "' ";
	}

	if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
		$joinfactura = "left join factura f1 on cobro.id_cobro=f1.id_cobro
                             left join prm_documento_legal prm on f1.id_documento_legal=prm.id_documento_legal
                             left join prm_estado_factura pef on f1.id_estado=pef.id_estado ";
		if (Conf::GetConf($sesion, 'NumeroFacturaConSerie')) {
			$documentof = " GROUP_CONCAT(DISTINCT CONCAT(' ', prm.codigo, ' ', IFNULL(serie_documento_legal, '001'), '-', numero, IF(pef.glosa='Anulado', ' (Anulado)', '')))    ";
		} else {
			$documentof = " GROUP_CONCAT(DISTINCT CONCAT(' ', prm.codigo, ' ', numero, IF(pef.glosa='Anulado', ' (Anulado)', ''))) ";
		}
	} else if (Conf::GetConf($sesion, 'PermitirFactura')) {
		$joinfactura = "left join factura f1 on cobro.id_cobro=f1.id_cobro
                             left join prm_documento_legal prm on f1.id_documento_legal=prm.id_documento_legal ";
		$documentof = " group_concat(DISTINCT concat(' ',prm.codigo,' ', ifnull(serie_documento_legal,'001'),'-', numero,if(f1.anulado=1, ' (Anulado)','')))   ";
	} else {
		$joinfactura = "";
		$documentof = " cobro.documento ";
	}

	if (isset($_POST['tienehonorario'])) {
		$where.= " AND ( SELECT count(id_tramite) FROM  `trabajo` AS t1 WHERE t1.id_cobro = cobro.id_cobro AND t1.id_tramite = 0 ) > 0 ";
	}
	if (isset($_POST['tienegastos'])) {
		$where.= " AND ( SELECT count(id_movimiento) FROM cta_corriente c WHERE c.id_cobro = cobro.id_cobro AND c.id_cobro is not null group by c.id_cobro ) IS NOT NULL ";
	}
	if (isset($_POST['tienetramites'])) {
		$where.=" AND ( SELECT count(id_tramite) FROM tramite AS t1 WHERE t1.id_cobro = cobro.id_cobro ) > 0 ";
	}

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
				cobro.fecha_creacion,
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
				usuario.apellido1,
				contrato.forma_cobro,
				contrato.monto,
				contrato.retainer_horas,
				moneda.simbolo,
				moneda.cifras_decimales,
				cobro.incluye_honorarios as incluye_honorarios,
				cobro.incluye_gastos as incluye_gastos,
				CONCAT(moneda_monto.simbolo, ' ', contrato.monto) AS monto_total,
				tarifa.glosa_tarifa,

				(
			        SELECT count(id_tramite)
			        FROM  `trabajo` AS t1
			        WHERE t1.id_cobro = cobro.id_cobro
			        AND t1.id_tramite != 0
			    ) as tramites_count,
			    (
			        SELECT count(id_tramite)
			        FROM  `trabajo` AS t1
			        WHERE t1.id_cobro = cobro.id_cobro
			        AND t1.id_tramite = 0
			    ) as trabajos_count,
			    (
			    	SELECT count(id_movimiento)
			        FROM cta_corriente c
			        WHERE c.id_cobro = cobro.id_cobro AND c.id_cobro is not null
			        group by c.id_cobro
				) as gastos_SiNo
				";

	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_query_seguimiento_cobro') : false;

	$query.="FROM contrato
				JOIN cobro ON cobro.id_contrato = contrato.id_contrato
			 	LEFT JOIN prm_moneda as moneda ON cobro.id_moneda = moneda.id_moneda
			 	LEFT JOIN cliente ON cobro.codigo_cliente = cliente.codigo_cliente
			 	LEFT JOIN usuario ON cobro.id_usuario_responsable = usuario.id_usuario
				LEFT JOIN grupo_cliente ON grupo_cliente.codigo_cliente = contrato.codigo_cliente
				LEFT JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
				LEFT JOIN prm_moneda as moneda_total ON cobro.opc_moneda_total = moneda_total.id_moneda
				LEFT JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa";

	if (isset($_POST['tieneadelantos'])) {
		$query.=" left join documento as adelanto on adelanto.es_adelanto=1 AND adelanto.codigo_cliente=contrato.codigo_cliente and (adelanto.id_contrato is null or adelanto.id_contrato=contrato.id_contrato)";
		$where.=" AND adelanto.saldo_pago<0";
		$where.=" AND (	(adelanto.pago_honorarios=1 AND cobro.monto>0) OR (adelanto.pago_gastos=1 AND cobro.subtotal_gastos>0) )";
	}

	$query.=" left join documento on documento.id_cobro=cobro.id_cobro and documento.tipo_doc='N'
            	$joinfactura
                    WHERE $where
						GROUP BY cobro.id_cobro, cobro.id_contrato";
	$x_pag = 20;

	if(empty($orden)) {
		$orden = $cobros->OrdenResultados();
	}

	if ($print) {
		$query .= " ORDER BY $orden";
		$cobros_stmt = $sesion->pdodbh->query($query);
		$cobros_result = $cobros_stmt->fetchAll(PDO::FETCH_ASSOC);

		$opcion = explode(',', $opcion);
		$imprimir_cartas = $opcion[0] == 'cartas';
		$agrupar_cartas = $opcion[1] == 'agrupar';

		$NotaCobro = new NotaCobro($sesion);
		$NotaCobro->GeneraCobrosMasivos($cobros_result, $imprimir_cartas, $agrupar_cartas);
		die();
	}

	$b = new Buscador($sesion, $query, "Cobro", $desde, $x_pag, $orden);
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Seguimiento de cobros');
	$b->AgregarEncabezado("glosa_cliente", __('Cliente'), "", "", "SplitDuracion");
	$b->AgregarEncabezado("asuntos", __('Asunto'), "align=left");
	$b->AgregarEncabezado("id_contrato", __('Acuerdo'), "align=left");
	$b->AgregarFuncion("Opci&oacute;n", 'Opciones', "align=center nowrap width=8%");
	$b->funcionTR = "funcionTR";

	function funcionTR(&$cobro) {
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
		global $html, $contratofields;

		if ($i % 2 == 0) {
			$color = "#dddddd";
		} else {
			$color = "#ffffff";
		}

		$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');

		if ($cobro->fields['codigo_idioma'] != '') {
			$idioma->Load($cobro->fields['codigo_idioma']);
		} else {
			$idioma->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));
		}

		$cols = 4;
		if (Conf::GetConf($sesion, 'FacturaSeguimientoCobros')) {
			$cols++;
		}
		$contratofields = $cobro->fields;

		$html = "";

		if ($cobro->fields['codigo_cliente'] != $codigo_cliente_ultimo || $id_contrato_ultimo != $cobro->fields['id_contrato']) {
			$j++;
			$html .= $codigo_cliente_ultimo != '' ? "<tr bgcolor=$color style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'><td colspan=4><hr size='1px'></td>" : "";

			$html .= "<tr id=foco" . $j . " bgcolor=$color style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'>";
			$html .= "<td style='font-size:10px' align=center valing=top><b>" . $cobro->fields['glosa_cliente'];
			($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_imprimir_buscador') : false;

			$html .= "</b></td>";
			$html .= "<td style='font-size:10px' class='btpopover' title='Listado de " . __('Asuntos') . "' id='tip_{$cobro->fields['id_contrato']}' align=left valing=top></td>";

			if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
				$texto_acuerdo = $cobro->fields['forma_cobro'] . " de " . $cobro->fields['simbolo_moneda_contrato'] . " " . number_format($cobro->fields['monto'], $cobro->fields['cifras_decimales_moneda_contrato'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . " por " . number_format($cobro->fields['retainer_horas'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . " Hrs.";
			} else if ($cobro->fields['forma_cobro'] == 'TASA' || $cobro->fields['forma_cobro'] == 'HITOS' || $cobro->fields['forma_cobro'] == 'ESCALONADA') {
				$texto_acuerdo = $cobro->fields['forma_cobro'];
			} else {
				$texto_acuerdo = $cobro->fields['forma_cobro'] . " por " . $cobro->fields['simbolo_moneda_contrato'] . " " . number_format($cobro->fields['monto'], $cobro->fields['cifras_decimales_moneda_contrato'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			}

			$html .= "<td style='font-size:10px' align=left colspan=2 valign=top><b>" . $texto_acuerdo . ', Tarifa: ' . $cobro->fields['glosa_tarifa'] . "</b>&nbsp;&nbsp;<a href='javascript:void(0)' style='font-size:10px' onclick=\"nuovaFinestra('Editar_Contrato',800,600,'agregar_contrato.php?popup=1&id_contrato=" . $cobro->fields['id_contrato'] . "');\" title='" . __('Editar Informaci�n Comercial') . "'>Editar</a>";
			$html .="</td></tr>";

			$ht = "<tr bgcolor='#F2F2F2'>
							<td align=center style='font-size:10px; width: 70px;'>
								<b>" . __('N� Cobro') . "</b>
							</td>";

			$ht .= "<td style='font-size:10px; ' align=left>
								<b>&nbsp;&nbsp;&nbsp;Descripci�n " . __('del cobro') . "</b>
							</td>";
			if (Conf::GetConf($sesion, 'FacturaSeguimientoCobros')) {
				$ht .= "<td align=center style='font-size:10px; width: 70px;'>
								<b>N� Factura</b>
							</td>";
			}
			$ht .= "<td style='font-size:10px; width: 52px;' align=center>
								<b>Opci�n</b>
							</td></tr>";
			$ht .= "<tr bgcolor='#F2F2F2'><td align=center colspan=4><hr size=1px style='font-size:10px; border:1px dashed #CECECE'></td><tr>";

			$codigo_cliente_ultimo = $cobro->fields['codigo_cliente'];
			$id_contrato_ultimo = $cobro->fields['id_contrato'];

		}

		$total_horas = $cobros->TotalHorasCobro($cobro->fields['id_cobro']);

		$html .= "<tr bgcolor='#F2F2F2' style='border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;'>";
		$html .= "<td align=center colspan=" . $cols . "><div style='font-size:10px; border:1px dashed #CECECE'>";
		$html .= "<table width='100%' cellSpacing='0' cellPadding='0'>";
		$html .= $ht;
		$html .= "<tr onmouseover=\"this.bgColor='#bcff5c'\" onmouseout=\"this.bgColor='#F2F2F2'\">
				<td align=right style='font-size:10px; width: 70px;'>#" . $cobro->fields['id_cobro'] . "</td>";

		if (empty($cobro->fields['incluye_honorarios'])) {
			$texto_tipo = '(s�lo gastos)';
		} else if (empty($cobro->fields['incluye_gastos'])) {
			$texto_tipo = '(s�lo honorarios)';
		} else {
			$texto_tipo = '';
		}

		$txt_iva = __('IVA');
		$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['cobro_monto'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		if (!empty($cobro->fields['impuesto'])) {
			$honorarios = $cobro->fields['simbolo'] . ' ' . number_format($cobro->fields['monto_subtotal'] - $cobro->fields['descuento'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) .
				" + $txt_iva ($honorarios)";
		}

		$texto_honorarios = "$honorarios por <a href=\"horas.php?from=reporte&id_cobro={$cobro->fields['id_cobro']}\">" .
			number_format($total_horas, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' Hrs.</a> ';

		$gastos = $cobro->fields['simbolo_moneda_total'] . ' ' . number_format($cobro->fields['monto_gastos'], $cobro->fields['cifras_decimales_moneda_total'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		if (!empty($cobro->fields['impuesto_gastos'])) {
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

		$html .= "<td align=left style='font-size:10px; ' >&nbsp;" . $texto_tipo . " de " . $texto_monto . $texto_horas . ' ';

		if ($cobro->fields['fecha_ini'] != '0000-00-00') {
			$fecha_cobro = __('desde') . ' ' . Utiles::sql2date($cobro->fields['fecha_ini']);
		}
		if ($cobro->fields['fecha_fin'] != '0000-00-00') {
			$fecha_cobro .= ' ' . __('hasta') . ' ' . Utiles::sql2date($cobro->fields['fecha_fin']) . ' ';
		}

		$html .= $fecha_cobro;
		$html .= "<span style='font-size:8px'>- (" . $cobro->fields['estado'] . ")</span>";

		if (Conf::GetConf($sesion, 'MostrarCodigoAsuntoEnListados')) {
			$asuntos_separados = explode(', ', $cobro->fields['asuntos_cobro']);
			$cantidad_asuntos = count($asuntos_separados);
			$html .= " <div class=\"tip_asuntos_cobro inlinehelp\" title=\"Listado de '. __('Asuntos'); .'\" id=\"tip_asuntos_cobro_" . $cobro->fields['id_cobro'] . "\">" . $cantidad_asuntos . "&nbsp;asunto" . ($cantidad_asuntos > 1 ? "s" : "") . "</div>";
		}

		$html .= "</td>";

		if (Conf::GetConf($sesion, 'FacturaSeguimientoCobros')) {
			$html .= "<td align=center style='font-size:10px; width: 70px;'>&nbsp;";
			if ($cobro->fields['documento'])
				$html.= "#" . $cobro->fields['documento'];
			$html .= "</td>";
		}

		$html .= "<td align=center style=\"white-space:nowrap; width: 52px;\">";
		$html .= "<a class=\"fl ui-button editar\" style=\"margin: 3px 1px;width: 18px;height: 18px;\"   title='" . __('Continuar con el cobro') . "' href=\"javascript:void(0)\" onclick=\"nuevaVentana('Editar_Cobro',1050,700,'cobros6.php?id_cobro=" . $cobro->fields['id_cobro'] . "&popup=1&contitulo=true&id_foco=" . $j . "', '');\">&nbsp;</a>";
		$html .= "<a class=\"fl ui-button cruz_roja\" style=\"margin: 3px 1px;width: 18px;height: 18px;\" title='" . __('Eliminar cobro') . "'  onclick=\"EliminarCobros('" . $cobro->fields['id_cobro'] . "','" . $cobro->fields['estado'] . "')\">&nbsp;</a>";
		$html .= UtilesApp::LogDialog($sesion, 'cobro', $cobro->fields['id_cobro']);
		$html .= "</td></table>";
		$html .= "</div></tr>";
		$ht = '';
		return $html;
	}

}

#Buscar

$pagina->titulo = __('Seguimiento de cobros');

$pagina->PrintTop();
?>

<script type="text/javascript">

	jQuery(document).ready(function() {
		<?php if ($_GET['buscar'] == 1)
			echo "jQuery('#boton_buscar').click();";
		?>

		jQuery('#usar_periodo').click(function(e) {
			jQuery('#rango').prop('checked', false);
			Rangos(jQuery('#rango'), this.form);
			if (jQuery(this).is(':checked')) {
				jQuery('#div_rango').css('display', 'inline');
			} else {
				jQuery('#div_rango').hide();
			}
		});

		jQueryUI.done(function() {

			jQuery('.btpopover').each(function() {

				var self = jQuery(this);
				var idContrato = jQuery(this).attr('id').replace('tip_', '');
				jQuery.ajax({url: 'ajax/ajax_asuntos.php?id_contrato=' + idContrato, dataType: 'json'}).done(function(data) {

				if (data == '' || data == null) {
					jQuery('#tip_' + idContrato).html("<span class='asuntos_del_contrato' style='font-weight:bold;'>No hay informaci&oacute;n sobre <?php echo __('Asuntos'); ?></span>");
				} else {

					var popover = data[idContrato];

					if (popover.length > 10) {
						var popover2 = popover.slice(0, 10);
						var sobra = popover.length - 10;
						popover2.push('<small>(hay otros ' + sobra + ' <?php echo __('asuntos'); ?> ocultos por falta de espacio en pantalla)</small>');
					} else {
						var popover2 = popover;
					}
					var contenido = popover2.join('<li>');
					var contenidofull = popover.join('<li>');
					jQuery('#tip_' + idContrato).data('content', '<li>' + contenido);
					jQuery('#tip_' + idContrato).html("<span class='asuntos_del_contrato' style='font-weight:bold;'><li>" + contenidofull + "</span>");
					}
				});
			});
		});
	});

	//Genera o buisca los cobros.
	function GeneraCobros(form, desde, opcion) {
		if (!form) {
			var form = $('form_busca');
		}

		if (desde == 'genera') {
			if (confirm('<?php echo __("�Ud. desea generar los cobros?") ?>')) {
				form.action = 'genera_cobros_guarda.php';
				form.submit();
			} else {
				return false;
			}
		} else if (desde == 'print') {
			form.action = 'genera_cobros_guarda.php?print=true&opcion=' + opcion;
			form.submit();
		} else if (desde == 'emitir') {
			if (confirm('<?php echo __("�Ud. desea emitir los cobros?") ?>')) {
				form.action = 'genera_cobros_guarda.php?emitir=true';
				form.submit();
			} else {
				return false;
			}
		} else {
			form.action = 'seguimiento_cobro.php';
			form.opc.value = 'buscar';
			form.orden.value = '';
			form.desde.value = '';
			form.submit();
		}
	}

	function SubirExcel() {
		nuevaVentana("Subir_Excel", 500, 300, "subir_excel.php");
	}

	//Elimina Cobro
	function EliminarCobros(id_cobro, estado) {
		var okButton = {
			caption: 'ok',
			onClick: function(){}
		};
		var text_window = '';
		if (estado == 'CREADO') {
			text_window = '<span><?php echo __('�Desea eliminar el cobro seleccionado?') ?></span>';
			okButton.caption = '<?php echo __('Aceptar') ?>';
			okButton.onClick = function() {
				DeleteCobro(id_cobro);
				jQuery(this).dialog('close');
			};
		} else {
			text_window = '<span><?php echo __('El cobro seleccionado debe estar en estado CREADO para poder eliminarlo.') ?>.</span>';
			okButton.caption = '<?php echo __('Continuar') ?>';
			okButton.onClick = function() {
				nuevaVentana('Editar_Contrato', 1050, 700, 'cobros6.php?id_cobro=' + id_cobro + '&popup=1&contitulo=true');
				jQuery(this).dialog('close');
			};

		}

		jQuery('<p/>')
			.css('font-size', '12px')
			.css('font-weight', 'bold')
			.attr('title', '<?php echo __('ALERTA') ?>')
			.html(text_window)
			.dialog({
				resizable: true, autoOpen: true, height: 200, width: 350, modal: true,
				close: function(ev, ui) {
					jQuery(this).remove();
				},
				open: function() {
					jQuery('.ui-dialog-title').addClass('ui-icon-warning');
					jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
				},
				buttons: [
					{
						text: okButton.caption,
						click: okButton.onClick
					},
					{
						text: '<?php echo __('Cancelar') ?>',
						click: function() {
							jQuery(this).dialog('close');
							return false;
						}
					}
				]
			});

	}

	function DeleteCobro(id_cobro) {

		var form = $('form_busca');
		form.id_cobro_hide.value = id_cobro;
		form.opc.value = 'eliminar';
		form.submit();

	}

	/*
	 Despliega periodos o rango para filtros
	 */
	function Rangos(obj, form) {

		var td_show = $('periodo_rango');
		var td_hide = $('periodo');

		if (obj.checked) {
			td_hide.style['display'] = 'none';
			td_show.style['display'] = 'inline';
		} else {
			td_hide.style['display'] = 'inline';
			td_show.style['display'] = 'none';
		}
	}

	function Refrescar() {
		$('opc').value = 'buscar';
		self.location.href = 'seguimiento_cobro.php?' + jQuery('#form_busca').serialize();
	}

	function ShowDiv(div, valor, dvimg) {

		var div_id = document.getElementById(div);
		var img = document.getElementById(dvimg);
		var form = document.getElementById('form_editar_trabajo');
		var codigo = document.getElementById('campo_codigo_cliente').value;
		var tr = document.getElementById('tr_cliente');
		var tr2 = document.getElementById('tr_asunto');
		var al = document.getElementById('al');

		DivClear(div, dvimg);

		if (div == 'tr_asunto' && codigo == '') {
			tr.style['display'] = 'none';
			alert("<?php echo __('Debe seleccionar un cliente') ?>");
			form.codigo_cliente.focus();
			return false;
		}

		div_id.style['display'] = valor;

		if (div == 'tr_cliente') {
			WCH.Discard('tr_asunto');
			tr2.style['display'] = 'none';
			Lista('lista_clientes', 'left_data', '', '');
		} else if (div == 'tr_asunto') {
			WCH.Discard('tr_cliente');
			tr.style['display'] = 'none';
			Lista('lista_asuntos', 'content_data2', codigo, '2');
		}

		/*Cambia IMG*/
		if (valor == 'inline') {
			WCH.Apply('tr_asunto');
			WCH.Apply('tr_cliente');
			img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/menos.gif" border="0" title="Ocultar" class="mano_on" onClick="ShowDiv(\'' + div + '\',\'none\',\'' + dvimg + '\');">';
		} else {
			WCH.Discard(div);
			img.innerHTML = '<img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" onMouseover="ddrivetip(\'Historial de trabajos ingresados\')" onMouseout="hideddrivetip()" class="mano_on" onClick="ShowDiv(\'' + div + '\',\'inline\',\'' + dvimg + '\');">';
		}
	}

	function DescargarLiquidaciones() {
		var text_window = '<strong><center><?php echo __('�Desea descargar los cobros del periodo?') ?><center></strong><br><br>';
		text_window += '<br><label for="cartas" style="padding-bottom: 4px;display:inline-block;width:160px;">Incluir cartas:</label><input type="checkbox" name="cartas" id="cartas"  />';
		text_window += '<br><label for="agrupar" style="padding-bottom: 4px;display:inline-block;width:160px;">Agrupar por cliente:</label><input type="checkbox" name="agrupar" id="agrupar" /></div>';

		jQuery('<p/>')
			.attr('title', 'Advertencia')
			.html(text_window)
			.dialog({
				resizable: true,
				height: 260,
				width: 500,
				modal: true,
				close: function(ev, ui) {
					interrumpeproceso = 1;
				},
				open: function() {
					jQuery('.ui-dialog-title').addClass('ui-icon-warning');
					jQuery('.ui-dialog-buttonpane').find('button').addClass('btn').removeClass('ui-button ui-state-hover');
				},
				buttons: {
					"<?php echo __('Descargar') ?>": function() {
						var opciones = '';
						if (jQuery('#cartas').is(':checked')) {
							opciones += 'cartas';
						}
						if (jQuery('#agrupar').is(':checked')) {
							opciones += ',agrupar';
						}

						jQuery("#opc").val('buscar');
						jQuery('#form_busca').attr('action', 'seguimiento_cobro.php?print=true&opcion=' + opciones);
						jQuery('#form_busca').submit();

						jQuery(this).dialog("close");
						return true;
					},
					"<?php echo __('Cancelar') ?>": function() {
						jQuery(this).dialog('close');
						return false;
					}
				}
			});
	}

	<?php if ($id_foco) { ?>
		self.location.href = self.location.href + "#foco" + <?php echo $id_foco ?>;</script>
	<?php } ?>

</script>

<form name="form_busca" id="form_busca" action="" method="post">
	<input type="hidden" name='opc' id='opc' value=''>
	<input type="hidden" name='id_cobro_hide' value=''>
 	<input type='hidden' name='desde' id='desde' value='<?php echo $desde ?>'>
 	<input type='hidden' name='orden' id='orden' value='<?php echo $orden ?>'>

	<fieldset class="tb_base" style="width:850px;">
	<legend><?php echo 'Filtros' ?></legend>

		<table>
			<tr>
				<td align="right" width='30%'><b><?php echo __('Cobro') ?></b></td>
				<td colspan="2" align="left">
					<input onkeydown="if (event.keyCode == 13) GeneraCobros(this.form, '', false)" type=text size=6 name=id_cobro id=id_cobro value="<?php echo $id_cobro ?>">
					<input onkeydown="if (event.keyCode == 13) GeneraCobros(this.form, '', false)" type=hidden size=6 name=proceso id=proceso value="<?php echo $proceso ?>">
					 <?php if (Conf::GetConf($sesion, 'FacturaSeguimientoCobros') && !Conf::GetConf($sesion, 'NuevoModuloFactura')) { ?>
						&nbsp;&nbsp;<b><?php echo __('N� Factura') ?></b>&nbsp;
						<input onkeydown="if (event.keyCode == 13) GeneraCobros(this.form, '', false)" type=text size=6 name=numero_factura id=numero_factura value="<?php echo $numero_factura ?>">
					 <?php } ?>
				</td>
			</tr>

			<?php if (Conf::GetConf($sesion, 'FacturaSeguimientoCobros') && Conf::GetConf($sesion, 'NuevoModuloFactura')) { ?>
				<tr>
					<td align="right" width='30%'>
						<b><?php echo __('Documento legal') ?></b>
					</td>
					<td colspan="2" align="left">
						<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal', $tipo_documento_legal, '', __('Cualquiera'), 100); ?>
						<?php echo Html::SelectQuery($sesion, $series_documento->SeriesQuery(), "serie", $serie ? str_pad($serie, 3, '0', STR_PAD_LEFT) : __('Serie'), '', __('Serie'), 60); ?>
						<input onkeydown="if (event.keyCode == 13) GeneraCobros(this.form, '', false)" type="text" size="6" name="factura" id="factura" value="<?php echo $factura ?>">
					</td>
				</tr>
			<?php } ?>

			<tr>
				<td align="right" width="30%">
					<b><?php echo __('Grupo') ?></b>&nbsp;
				</td>
				<td align="left" colspan="2">
					<?php $GrupoCliente = new GrupoCliente($sesion); ?>
					<?php echo Html::SelectArrayDecente($GrupoCliente->Listar(), "id_grupo_cliente", $id_grupo_cliente, "", "Ninguno", '280px') ?>
				</td>
			</tr>

			<tbody id="selectclienteasunto">
				<tr >
					<td align="right" width='30%'><?php echo '<b>' . __('Nombre Cliente') . '</b>'; ?> </td>
					<td nowrap colspan="3" align="left"><?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>    </td>
				</tr>
				<tr>
					<td align="right"> <?php echo '<b>' . __('Asunto') . '</b>'; ?> </td>
					<td nowrap colspan="3" align="left"> <?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320, '', '', false); ?> </td>
				</tr>
			</tbody>

			<tr>
				<td align=right><b><?php echo __('Encargado comercial') ?>&nbsp;</b></td>
				<td colspan=2 align=left><?php echo Html::SelectQuery($sesion, $query_usuario, "id_usuario", $id_usuario, '', __('Cualquiera'), '210') ?>
			</tr>

			<?php if (Conf::GetConf($sesion, 'EncargadoSecundario')) { ?>
				<tr>
					<td align=right><b><?php echo __('Encargado Secundario') ?>&nbsp;</b></td>
					<td colspan=2 align=left><?php echo Html::SelectQuery($sesion, $query_usuario_activo, "id_usuario_secundario", $id_usuario_secundario, '', __('Cualquiera'), '210') ?>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type=hidden size=6 name=id_proceso id=id_proceso value='<?php echo $id_proceso ?>' >
					</td>
				</tr>
			<?php } ?>

			<?php ($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_filtros_seguimiento_cobro') : false; ?>

			<tr>
				<td align=right><b><?php echo __('Forma de Tarificaci�n') ?>&nbsp;</b></td>
				<td colspan=2 align=left>
					<?php echo Html::SelectQuery($sesion, $query_forma_cobro, "forma_cobro", $forma_cobro, '', __('Cualquiera'), '210') ?>
				</td>
			</tr>
			<tr>
				<td align=right><b><?php echo __('Tipo de Liquidaci�n') ?>&nbsp;</b></td>
				<td colspan=2 align=left>
					<?php
					echo Html::SelectArray(array(
						array('1', __('S�lo Honorarios')),
						array('2', __('S�lo Gastos')),
						array('3', __('S�lo Mixtas (Honorarios y Gastos)'))), 'tipo_liquidacion', $tipo_liquidacion, '', __('Todas'))
					?>
				</td>
			</tr>

			<tr>
				<td align="right">
					<label>
						<input type="checkbox" name="usar_periodo" id="usar_periodo" value="1" title="Seleccione esta opci�n para utilizar el filtro periodo" <?php echo $usar_periodo ? 'checked' : ''; ?>>
						<b><?php echo __('Periodo creaci�n') ?></b>
					</label>
				</td>
				<td align="left" colspan="2">
					<div id="div_rango" style="display:<?php echo $usar_periodo ? 'inline' : 'none'; ?>">
						<label>
							<input type="checkbox" name="rango" id="rango" value="1" <?php echo $rango ? 'checked' : ''; ?> onclick="Rangos(this, this.form);" title="Otro rango" />
							<span style='font-size:9px;'><?php echo __('Otro rango'); ?></span>
						</label>
					</div>
					<div id="periodo" style="display:<?php echo !$rango ? 'inline' : 'none' ?>;">
						<?php
							$fecha_mes = $fecha_mes != '' ? $fecha_mes : date('m');
							echo Html::SelectArray(
								array(
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
								),
								'fecha_mes',
								$fecha_mes,
								'id="fecha_mes"',
								__('Mes'),
								'80px'
							);

							if (!$fecha_anio) {
								$fecha_anio = date('Y');
							}
						?>
						<select name="fecha_anio" id="fecha_anio" style="width:55px">
							<?php for ($i = (date('Y') - 5); $i < (date('Y') + 5); $i++) { ?>
								<option value='<?php echo $i; ?>' <?php echo $fecha_anio == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
							<?php } ?>
						</select>
					</div>
					<br>
					<div id="periodo_rango" style="display:<?php echo $rango ? 'inline' : 'none' ?>;">
						<?php echo __('Fecha desde'); ?>:
						<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
						<br />
						<?php echo __('Fecha hasta'); ?>:&nbsp;
						<input type="text" name="fecha_fin" class="fechadiff" value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
					</div>
				</td>
			</tr>

			<tr>
				<td align="right">
					<b><?php echo __('Estado') . ' de ' . __('Cobro'); ?></b>
				</td>
				<td align="left" colspan="2">
					<?php echo Html::SelectQuery($sesion, "SELECT codigo_estado_cobro FROM prm_estado_cobro ORDER BY orden", "estado[]", $estado, 'multiple="multiple" size="7"', __('Vacio'), '150') ?>
				</td>
			</tr>

			<tr>
				<div style="text-align: left;position: absolute;left: 600px;top: 300px;">
					<br/><input type="checkbox" name="tienehonorario"  value="1" id="tienehonorario" <?php if (isset($_POST['tienehonorario'])) echo 'checked="checked"'; ?> /> Tiene <?php echo __('Honorarios'); ?>
					<br/><input type="checkbox" name="tienegastos"   value="1" id="tienegastos"  <?php if (isset($_POST['tienegastos'])) echo 'checked="checked"'; ?>/> Tiene <?php echo __('Gastos'); ?>
					<br/><input type="checkbox"  name="tienetramites"  value="1"   id="tienetramites" <?php if (isset($_POST['tienetramites'])) echo 'checked="checked"'; ?> /> Tiene <?php echo __('Tr�mites'); ?>
					<br/><input type="checkbox"  name="tieneadelantos"  value="1"   id="tieneadelantos" <?php if (isset($_POST['tieneadelantos'])) echo 'checked="checked"'; ?> /> Hay <?php echo __('Adelantos'); ?>  disponibles
				</div>
			</tr>

			<tr>
				<td></td>
				<td align="left">
					<?php
					echo $Form->icon_button(__('Buscar'), 'find', array('id' => 'boton_buscar', 'onclick' => "GeneraCobros(jQuery('#form_busca').get(0), '', false);"));
					echo "&nbsp;&nbsp;&nbsp;&nbsp;";
					echo $Form->icon_button(__('Descargar Liquidaciones'), 'download', array('href' => "javascript:void(0);", 'onclick' => "DescargarLiquidaciones()"));
					echo $Form->icon_button(__('Subir excel'), 'upload', array('id' => 'boton_buscar', 'onclick' => "SubirExcel();", 'style' => 'float:right;margin-right:20px;'));
					?>
				</td>
			</tr>
		</table>

	</fieldset>

</form>
<?php
echo $Form->script();
if ($opc == 'buscar') {
	$b->Imprimir('');
}

$pagina->PrintBottom($popup);
