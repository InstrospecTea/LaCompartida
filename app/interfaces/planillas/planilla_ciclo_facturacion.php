<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Reporte.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Factura.php';
require_once Conf::ServerDir() . '/classes/Cobro.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

set_time_limit(300);

if ($xls) {
	$moneda = new Moneda($sesion);
	$id_moneda_referencia = $moneda->GetMonedaTipoCambioReferencia($sesion);
	$id_moneda_base = $moneda->GetMonedaBase($sesion);

	$arreglo_monedas = ArregloMonedas($sesion);

	$moneda_base = Utiles::MonedaBase($sesion);
	#ARMANDO XLS
	$wb = new Spreadsheet_Excel_Writer();

	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);

	$formato_encabezado = & $wb->addFormat(array('Size' => 12,
				'VAlign' => 'top',
				'Align' => 'left',
				'Bold' => '1',
				'underline' => 1,
				'Color' => 'black'));

	$formato_encabezado2 = & $wb->addFormat(array('Size' => 12,
				'VAlign' => 'top',
				'Align' => 'left',
				'Bold' => '1',
				'Color' => 'black'));

	$formato_texto = & $wb->addFormat(array('Size' => 11,
				'Valign' => 'top',
				'Align' => 'left',
				'Border' => 1,
				'Color' => 'black',
				'TextWrap' => 1));

	$formato_tiempo = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => '[h]:mm'));
	$formato_numero = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => 0));
	$formato_titulo = & $wb->addFormat(array('Size' => 12,
				'Align' => 'center',
				'Bold' => '1',
				'FgColor' => '35',
				'Border' => 1,
				'Locked' => 1,
				'Color' => 'black',
				'TextWrap' => 1));
	$formato_subtitulo = & $wb->addFormat(array('Size' => 12,
				'VAlign' => 'top',
				'Align' => 'right',
				'Bold' => '1',
				'Color' => 'black'));

	$mostrar_encargado_secundario = UtilesApp::GetConf($sesion, 'EncargadoSecundario');

	$ws1 = & $wb->addWorksheet(__('Ciclo de Facturacion'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1, 0);
	$ws1->setZoom(75);

	$filas += 1;
	$ws1->mergeCells($filas, 1, $filas, 3);
	$ws1->write($filas, 1, __('Reporte ciclo de cobranza').' '.UtilesApp::GetConf($sesion,'PdfLinea1'), $formato_encabezado);
	$ws1->write($filas, 2, '', $formato_encabezado);
        $ws1->write($filas, 3, '', $formato_encabezado);

	$filas +=2;
	$ws1->mergeCells($filas, 1, $filas, 4);

	if (isset($_POST['fecha1']) && isset($_POST['fecha2'])) {
		$ft = explode("-", $_POST['fecha1']);
		$fecha_desde = $ft[2] . "/" . $ft[1] . "/" . $ft[0];

		$ft = explode("-", $_POST['fecha2']);
		$fecha_hasta = $ft[2] . "/" . $ft[1] . "/" . $ft[0];

		$ws1->write($filas, 1, __('Periodo desde:'), $formato_encabezado2);
		$ws1->write($filas, 2, $fecha_desde . " hasta " . $fecha_hasta, $formato_encabezado2);
                $ws1->write($filas, 3, '', $formato_encabezado2);
                $ws1->write($filas, 4, '', $formato_encabezado2);
	} elseif (isset($_POST['fecha1'])) {
		$where .= " AND f.fecha >= '{$_POST['fecha1']}' ";

		$ft = explode("-", $_POST['fecha1']);
		$fecha_desde = $ft[2] . "/" . $ft[1] . "/" . $ft[0];

		$ws1->write($filas, 1, __('Periodo desde:'), $formato_encabezado2);
		$ws1->write($filas, 2, $fecha_desde, $formato_encabezado2);
                $ws1->write($filas, 3, '', $formato_encabezado2);
                $ws1->write($filas, 4, '', $formato_encabezado2);
	} elseif (isset($_POST['fecha2'])) {

		$where .= " AND f.fecha <= '{$_POST['fecha2']}' ";

		$ft = explode("-", $_POST['fecha2']);
		$fecha_hasta = $ft[2] . "/" . $ft[1] . "/" . $ft[0];

		$ws1->write($filas, 1, __('Periodo hasta:'), $formato_encabezado2);
		$ws1->write($filas, 2, $fecha_hasta, $formato_encabezado2);
                $ws1->write($filas, 3, '', $formato_encabezado2);
                $ws1->write($filas, 4, '', $formato_encabezado2);
	}


	$ws1->write($filas, 2, date("d-m-Y H:i:s"), $formato_texto);

	$filas +=4;
	$col = 0;

	$col_cliente = ++$col;
	$col_factura = ++$col;
	$col_fecha_factura = ++$col;
	$col_fecha_primer_pago = ++$col;
	$col_fecha_pago_total = ++$col;
	$col_dias_primer_pago = ++$col;
	$col_dias_pago_final = ++$col;

	unset($col);

	$ws1->setColumn($col_cliente, $col_cliente, 50);
	$ws1->setColumn($col_factura, $col_factura, 20);
	$ws1->setColumn($col_fecha_factura, $col_fecha_factura, 30);
	$ws1->setColumn($col_fecha_primer_pago, $col_fecha_primer_pago, 30);
	$ws1->setColumn($col_fecha_pago_total, $col_fecha_pago_total, 30);
	$ws1->setColumn($col_dias_primer_pago, $col_dias_primer_pago, 20);
	$ws1->setColumn($col_dias_pago_final, $col_dias_pago_final, 20);



	$ws1->write($filas, $col_cliente, __('Cliente'), $formato_titulo);
	$ws1->write($filas, $col_factura, __('Documento Legal'), $formato_titulo);
	$ws1->write($filas, $col_fecha_factura, __('Fecha Documento Legal'), $formato_titulo);
	$ws1->write($filas, $col_fecha_primer_pago, __('Fecha Primer Pago'), $formato_titulo);
	$ws1->write($filas, $col_fecha_pago_total, __('Fecha Pago al 100%'), $formato_titulo);
	$ws1->write($filas, $col_dias_primer_pago, __('Tiempo Primer Pago de la factura (días)'), $formato_titulo);
	$ws1->write($filas, $col_dias_pago_final, __('Tiempo Pago al 100% de la factura (días)'), $formato_titulo);


	if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {

		$where = " pef.codigo != 'A' ";
		if (isset($_POST['fecha1']) && isset($_POST['fecha2'])) {
			$where .= " AND f.fecha >= '{$_POST['fecha1']}' AND f.fecha <= '{$_POST['fecha2']}' ";
		} elseif (isset($_POST['fecha1'])) {
			$where .= " AND f.fecha >= '{$_POST['fecha1']}' ";
		} elseif (isset($_POST['fecha2'])) {
			$where .= " AND f.fecha <= '{$_POST['fecha2']}' ";
		}

		if (isset($_POST['id_documento_legal']) && $_POST['id_documento_legal'] > 0) {
			$where .= " AND f.id_documento_legal = {$_POST['id_documento_legal']} ";
		} else {
			$where .= " AND f.id_documento_legal !=  2 ";
		}

		$query = "SELECT 
						f.numero, 
						f.fecha, 
						f.cliente, 
						dl.codigo,
						pef.glosa as estado_factura,
						(SELECT fp.fecha
							FROM factura_pago fp
								JOIN cta_cte_fact_mvto ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
								JOIN cta_cte_fact_mvto_neteo ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
								LEFT JOIN cta_cte_fact_mvto ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
							WHERE ccfm2.id_factura =  f.id_factura
							ORDER BY fp.fecha ASC,fp.id_factura_pago ASC LIMIT 1) as fecha_primer_pago,
						(IF ( ccfm3.saldo = 0, (SELECT fp.fecha
										FROM factura_pago fp
											JOIN cta_cte_fact_mvto ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
											JOIN cta_cte_fact_mvto_neteo ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
											LEFT JOIN cta_cte_fact_mvto ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
										WHERE ccfm2.id_factura =  f.id_factura
										ORDER BY fp.fecha DESC,fp.id_factura_pago DESC LIMIT 1) , NULL )) as fecha_pago_total
					FROM factura f 
						JOIN prm_documento_legal dl USING ( id_documento_legal ) 
						JOIN cta_cte_fact_mvto ccfm3 ON ( f.id_factura = ccfm3.id_factura )
						LEFT JOIN prm_estado_factura pef ON ( f.id_estado = pef.id_estado ) 
					WHERE $where ";
		# echo $query; exit;

		$fila_inicial = $filas + 2;
		$num_facturas_primer = 0;
		$num_facturas_total = 0;
		$sum_dias_primer_pago = 0;
		$sum_dias_pago_total = 0;

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		while ($factura = mysql_fetch_array($resp)) {

			++$filas;

			$ws1->write($filas, $col_cliente, $factura['cliente'], $formato_texto);
			$ws1->write($filas, $col_factura, $factura['codigo'] . " " . $factura['numero'], $formato_texto);
			$ws1->write($filas, $col_fecha_factura, Utiles::sql2fecha($factura['fecha'], $formato_fecha, " "), $formato_texto);
			$ws1->write($filas, $col_fecha_primer_pago, !empty($factura['fecha_primer_pago']) ? Utiles::sql2fecha($factura['fecha_primer_pago'], $formato_fecha, " ") : " ", $formato_texto);
			$ws1->write($filas, $col_fecha_pago_total, !empty($factura['fecha_pago_total']) ? Utiles::sql2fecha($factura['fecha_pago_total'], $formato_fecha, " ") : " ", $formato_texto);

			$fecha_original = strtotime($factura['fecha']);
			$fecha_primer_pago = !empty($factura['fecha_primer_pago']) ? strtotime($factura['fecha_primer_pago']) : 0;
			$fecha_pago_total = !empty($factura['fecha_pago_total']) ? strtotime($factura['fecha_pago_total']) : 0;

			$dias_primer_pago = " ";
			if ($fecha_primer_pago > 0) {
				$dias_primer_pago = (int) ceil(($fecha_primer_pago - $fecha_original) / 86400);
				$sum_dias_primer_pago += $dias_primer_pago;
				$num_facturas_primer++;
			}

			$dias_pago_total = " ";
			if ($fecha_pago_total > 0) {
				$dias_pago_total = (int) ceil(($fecha_pago_total - $fecha_original) / 86400);
				$sum_dias_pago_total += $dias_pago_total;
				$num_facturas_total++;
			}

			$ws1->write($filas, $col_dias_primer_pago, $dias_primer_pago, $formato_numero);
			$ws1->write($filas, $col_dias_pago_final, $dias_pago_total, $formato_numero);

			$num_facturas++;
		}
		++$filas;
		$ws1->write($filas, $col_fecha_pago_total, __('Promedio facturas con pago'), $formato_subtitulo);
		$ws1->write($filas, $col_dias_primer_pago, number_format(($sum_dias_primer_pago / $num_facturas_primer), 2), $formato_numero);
		$ws1->write($filas, $col_dias_pago_final, number_format(($sum_dias_pago_total / $num_facturas_total), 2), $formato_numero);
		++$filas;
		$ws1->write($filas, $col_fecha_pago_total, __('Promedio todas'), $formato_subtitulo);
		$ws1->write($filas, $col_dias_primer_pago, number_format(($sum_dias_primer_pago / $num_facturas), 2), $formato_numero);
		$ws1->write($filas, $col_dias_pago_final, number_format(($sum_dias_pago_total / $num_facturas), 2), $formato_numero);
	} else {
		$where = " d.tipo_doc = 'N'
				AND ( cob.estado != 'CREADO' AND cob.estado!='EN REVISION' AND cob.estado != 'INCOBRABLE') ";
		if (isset($_POST['fecha1']) && isset($_POST['fecha2'])) {
			$where .= " AND d.fecha >= '{$_POST['fecha1']}' AND d.fecha <= '{$_POST['fecha2']}' ";
		} elseif (isset($_POST['fecha1'])) {
			$where .= " AND d.fecha >= '{$_POST['fecha1']}' ";
		} elseif (isset($_POST['fecha2'])) {
			$where .= " AND d.fecha <= '{$_POST['fecha2']}' ";
		}

		
		$query = "SELECT 
						d.glosa_documento, 
						d.fecha, 
						c.glosa_cliente, 
						cob.estado as estado_cobro,
						(SELECT dp.fecha
							FROM documento dp
								JOIN neteo_documento nd ON ( dp.id_documento = nd.id_documento_pago)
								JOIN documento dc ON (nd.id_documento_cobro = dc.id_documento) 
							WHERE nd.id_documento_cobro = d.id_documento
								AND dp.tipo_doc != 'N'				
							ORDER BY dp.fecha ASC, dp.id_documento ASC LIMIT 1) as fecha_primer_pago,
						( IF( (d.saldo_honorarios + d.saldo_gastos) = 0 , (SELECT dp.fecha
								FROM documento dp
									JOIN neteo_documento nd ON ( dp.id_documento = nd.id_documento_pago)
									JOIN documento dc ON (nd.id_documento_cobro = dc.id_documento) 
								WHERE nd.id_documento_cobro = d.id_documento
									AND dp.tipo_doc != 'N'				
								ORDER BY dp.fecha DESC, dp.id_documento DESC LIMIT 1) , NULL ) ) as fecha_pago_total
					FROM documento d 
						JOIN cliente c ON ( d.codigo_cliente = c.codigo_cliente )
						JOIN cobro cob ON ( d.id_cobro = cob.id_cobro )
						WHERE $where ";
		
		$fila_inicial = $filas + 2;
		$num_facturas_primer = 0;
		$num_facturas_total = 0;
		$sum_dias_primer_pago = 0;
		$sum_dias_pago_total = 0;

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		while ($factura = mysql_fetch_array($resp)) {
			++$filas;

			$ws1->write($filas, $col_cliente, $factura['glosa_cliente'], $formato_texto);
			$ws1->write($filas, $col_factura, $factura['glosa_documento'], $formato_texto);
			$ws1->write($filas, $col_fecha_factura, Utiles::sql2fecha($factura['fecha'], $formato_fecha, " "), $formato_texto);
			$ws1->write($filas, $col_fecha_primer_pago, !empty($factura['fecha_primer_pago']) ? Utiles::sql2fecha($factura['fecha_primer_pago'], $formato_fecha, " ") : " ", $formato_texto);
			$ws1->write($filas, $col_fecha_pago_total, !empty($factura['fecha_pago_total']) ? Utiles::sql2fecha($factura['fecha_pago_total'], $formato_fecha, " ") : " ", $formato_texto);

			$fecha_original = strtotime($factura['fecha']);
			$fecha_primer_pago = !empty($factura['fecha_primer_pago']) ? strtotime($factura['fecha_primer_pago']) : 0;
			$fecha_pago_total = !empty($factura['fecha_pago_total']) ? strtotime($factura['fecha_pago_total']) : 0;

			$dias_primer_pago = " ";
			if ($fecha_primer_pago > 0) {
				$dias_primer_pago = (int) ceil(($fecha_primer_pago - $fecha_original) / 86400);
				$sum_dias_primer_pago += $dias_primer_pago;
				$num_facturas_primer++;
			}

			$dias_pago_total = " ";
			if ($fecha_pago_total > 0) {
				$dias_pago_total = (int) ceil(($fecha_pago_total - $fecha_original) / 86400);
				$sum_dias_pago_total += $dias_pago_total;
				$num_facturas_total++;
			}

			$ws1->write($filas, $col_dias_primer_pago, $dias_primer_pago, $formato_numero);
			$ws1->write($filas, $col_dias_pago_final, $dias_pago_total, $formato_numero);

			$num_facturas++;
		}
		++$filas;
		$ws1->write($filas, $col_fecha_pago_total, __('Promedio facturas con pago'), $formato_subtitulo);
		$ws1->write($filas, $col_dias_primer_pago, number_format(($sum_dias_primer_pago / $num_facturas_primer), 2), $formato_numero);
		$ws1->write($filas, $col_dias_pago_final, number_format(($sum_dias_pago_total / $num_facturas_total), 2), $formato_numero);
		++$filas;
		$ws1->write($filas, $col_fecha_pago_total, __('Promedio todas'), $formato_subtitulo);
		$ws1->write($filas, $col_dias_primer_pago, number_format(($sum_dias_primer_pago / $num_facturas), 2), $formato_numero);
		$ws1->write($filas, $col_dias_pago_final, number_format(($sum_dias_pago_total / $num_facturas), 2), $formato_numero);
	}
	$wb->send("Reporte ciclo facturacion.xls");
	$wb->close();
	exit;
}

$pagina->titulo = __('Reporte Facturación pendiente');
$pagina->PrintTop();
?>
<form method="post" name="formulario" action="planilla_ciclo_facturacion.php?xls=1">
	<table class="border_plomo tb_base">
		<tr>
			<td align=right>
				<?php echo __('Fecha desde'); ?>
			</td>
			<td align=left>
				<?php echo Html::PrintCalendar("fecha1", "$fecha1"); ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('Fecha hasta'); ?>
			</td>
			<td align=left>
				<?php echo Html::PrintCalendar("fecha2", "$fecha2"); ?>
			</td>
		</tr>
		<!--<tr>
			<td align=center colspan="2">
		<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
					FROM usuario JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
			</td>
		</tr>-->
		<tr>
			<td align=right>
				<?php echo __('Tipo Documento legal'); ?>
			</td>
			<td align=left>
				<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa 
					FROM prm_documento_legal WHERE codigo != 'NC' ORDER BY id_documento_legal ASC", "id_documento_legal", $id_documento_legal, "", "Todos", "150"); ?>
			</td>
		</tr>
		<!--<tr>
			<td align=center colspan="2">
				<input type="checkbox" value=1 name="separar_asuntos" <?php echo $separar_asuntos ? 'checked' : ''; ?>><?php echo __('Separar Asuntos'); ?>
			</td>
		</tr>-->
		<tr>
			<td align=right colspan=2>
				<input type="hidden" name="debug" value="<?php echo $debug; ?>" />
				<input type="submit" class=btn value="<?php echo __('Generar reporte'); ?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<?php
echo(InputId::Javascript($sesion));
$pagina->PrintBottom();
?>