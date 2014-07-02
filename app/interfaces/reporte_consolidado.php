<?php 
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/PrmExcelCobro.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';


	$sesion = new Sesion();
	$pagina = new Pagina($sesion);

	$nombre_mes = array(__('Enero'), __('Febrero'), __('Marzo'), __('Abril'), __('Mayo'), __('Junio'), __('Julio'), __('Agosto'), __('Septiembre'), __('Octubre'), __('Noviembre'), __('Diciembre'));

	if($opc=='generar')
	{
		set_time_limit(150);
		require_once Conf::ServerDir().'/fpdf/PDF_ReporteConsolidado.php';
		$glosa_cliente = UtilesApp::GetConf($sesion,'CodigoSecundario')? 'codigo_cliente_secundario':'glosa_cliente';

		$max_por_grafico = 10; // Posible parámetro

		$largo_mes = array(31, Utiles::es_bisiesto($fecha_anio)?29:28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$fecha_desde = "01-$fecha_mes-$fecha_anio";
		$fecha_hasta = $largo_mes[$fecha_mes-1]."-$fecha_mes-$fecha_anio";

		// P: hoja vertical
		// mm: todo se mide en milímetros
		// Letter: formato de hoja

		$pdf = new PDF_ReporteConsolidado($sesion, $id_moneda, $fecha_anio, $fecha_mes, $id_area_proyecto_excluido, $categorias);
		$decimales_moneda = Utiles::glosa($pdf->sesion, $pdf->id_moneda, 'cifras_decimales', 'prm_moneda', 'id_moneda');

		$pdf->SetTitle("Reporte consolidado");
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 12);


		// Dimensiones de una hoja tamaño carta, en milímetros, por si se necesitan.
		// $ancho = 216;
		// $alto = 279;

		##### Escribir resumen inicial #####
		// Gráfico de horas
		$datos = array($pdf->getDatos('horas_trabajadas', $fecha_desde, $fecha_hasta),
						$pdf->getDatos('horas_cobrables', $fecha_desde, $fecha_hasta),
						$pdf->getDatos('horas_visibles', $fecha_desde, $fecha_hasta),
						$pdf->getDatos('horas_cobradas', $fecha_desde, $fecha_hasta),
						$pdf->getDatos('horas_pagadas', $fecha_desde, $fecha_hasta));
		$nombres = array(__('Trabajadas'),
						__('Cobrables'),
						__('Cobrables corregidas'),
						__('Cobradas'),
						__('Pagadas'));

		$grafico = new Grafico(__('Resumen de horas mes actual'));
		$grafico->Ejes('', __('Horas'));
		$grafico->Labels($nombres, true);
		$grafico->layer->addDataSet($datos);
		$pdf->MemImage($grafico->makeChart2(PNG), 24, 40, 150);

		$completo = true;

		// Gráficos comparando con año actual y últimos 12 meses.
		# Ventas
		$pdf->addGraficoComparacionValorCobrado('valor_cobrado');
		# Rentabilidad
		if($completo)
			$pdf->addGraficoComparacion('rentabilidad');
		# Valor hora
		if($completo)
			$pdf->addGraficoComparacion('valor_hora');
		# Diferencia valor estándar
		if($completo)
			$pdf->addGraficoComparacion('diferencia_valor_estandar');

		##### Escribir desglose por cliente (solo para el mes actual) #####
		$pdf->addPage('', '', __('Desglose por cliente'));

		// Stack de horas
		$pdf->addGraficoStackHoras($glosa_cliente);
		// Valor cobrado
		$pdf->addGraficoStackValores($glosa_cliente);
		//$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, $glosa_cliente);
		// Horas por cobrar
		if($completo)
			$pdf->addGraficoDesglose('horas_por_cobrar', $decimales_moneda, $glosa_cliente);
		// Rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('rentabilidad', 2, $glosa_cliente);

		##### Escribir desglose por profesional (solo para el mes actual) #####
		$pdf->addPage('', '', __('Desglose por profesional'));
		// Horas trabajadas
		$pdf->addGraficoStackHoras('profesional');

		#$pdf->addGraficoDesglose('horas_trabajadas', 2, 'profesional');
		// Valor cobrado
		$pdf->addGraficoStackValores('profesional');
		//$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, 'profesional');
		// Horas por cobrar
		if($completo)
			$pdf->addGraficoDesglose('horas_por_cobrar', $decimales_moneda, 'profesional');
		// Rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('rentabilidad', 2, 'profesional');

		##### Separar por forma de cobro #####
		$pdf->addPage('', '', __('Desglose por forma de cobro'));
		// Horas trabajadas
		if($completo)
			$pdf->addGraficoDesglose('horas_trabajadas', 2, 'forma_cobro');
		// Valor cobrado
		if($completo)
			$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, 'forma_cobro');
		// Horas por cobrar
		if($completo)
			$pdf->addGraficoDesglose('horas_por_cobrar', $decimales_moneda, 'forma_cobro');
		// Rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('rentabilidad', 2, 'forma_cobro');

		##### Separar por área de asunto #####
		$pdf->addPage('', '', __('Desglose por área de asunto'));
		// Horas trabajadas
		if($completo)
			$pdf->addGraficoDesglose('horas_trabajadas', 2, 'prm_area_proyecto.glosa');
		// Valor cobrado
		if($completo)
			$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, 'prm_area_proyecto.glosa');
		// Horas por cobrar
		if($completo)
			$pdf->addGraficoDesglose('horas_por_cobrar', $decimales_moneda, 'prm_area_proyecto.glosa');
		// Rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('rentabilidad', 2, 'prm_area_proyecto.glosa');

		##### Separar por encargado comercial #####
		$pdf->addPage('', '', __('Desglose por encargado comercial'));
		// Horas trabajadas
		if($completo)
			$pdf->addGraficoDesglose('horas_trabajadas', 2, 'id_usuario_responsable');
		// Valor cobrado
		if($completo)
			$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, 'id_usuario_responsable');
		// Horas por cobrar
		if($completo)
			$pdf->addGraficoDesglose('horas_por_cobrar', $decimales_moneda, 'id_usuario_responsable');
		// Rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('rentabilidad', 2, 'id_usuario_responsable');

		##### Detalle por cobros emitidos #####
		$pdf->addPage('', '', __('Cobros con fecha de corte').' '.$nombre_mes[$fecha_mes-1]." $fecha_anio");
		// Valor cobrado y rentabilidad
		if($completo)
			$pdf->addGraficoDesglose('valor_cobrado', $decimales_moneda, 'id_cobro', false, 'cobro', 'rentabilidad');
		// Agregar una tabla con cliente y asunto de cada cobro
		$datos = $pdf->getDatos('valor_cobrado', $fecha_desde, $fecha_hasta, true, 'id_cobro', 'cobro');
		foreach($datos as $v)
			if(is_array($v))
				$ids_cobro[] = $v['label'];

		// Tabla con los datos de cada cobro, para que sea entendible.
		if(count($ids_cobro))
		{
			$query = 'SELECT cobro.id_cobro AS cobro,
						cliente.glosa_cliente AS cliente,
						asunto.glosa_asunto AS asunto
					FROM cobro
					LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
					LEFT JOIN cobro_asunto ON cobro.id_cobro=cobro_asunto.id_cobro
					LEFT JOIN asunto ON asunto.codigo_asunto=cobro_asunto.codigo_asunto
					WHERE cobro.id_cobro IN ('.implode(', ', $ids_cobro).')';
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			# Encabezado
			if(++$pdf->num_grafico==count($pdf->pos_grafico_y))
				$pdf->addPage();
			$pdf->nueva_pagina = false;
			$pdf->setXY(24, $pdf->pos_grafico_y[$pdf->num_grafico]);
			$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(20, 5, __('Cobro'), 1);
			$pdf->Cell(50, 5, __('Cliente'), 1);
			$pdf->Cell(100, 5, __('Asunto'), 1, 1);
			# Contenido
			$pdf->SetFont('Arial', '', 11);
			while($row = mysql_fetch_array($resp))
			{
				$pdf->Cell(0, 5); // Esta celda sin texto se usa para poder detectar si hay un salto de página.
				if($pdf->nueva_pagina)
				{
					$pdf->nueva_pagina = false;
					$pdf->SetFont('Arial', 'B', 12);
					$pdf->Cell(0, 8, '', 0, 1); // Esta celda sin texto se usa para dejar espacio desde el header.
					$pdf->setX(24);
					$pdf->Cell(20, 5, __('Cobro'), 1);
					$pdf->Cell(50, 5, __('Cliente'), 1);
					$pdf->Cell(100, 5, __('Asunto'), 1, 1);
					$pdf->SetFont('Arial', '', 11);
				}
				$pdf->setX(24);
				$pdf->Cell(20, 5, $row['cobro'], 1);
				$pdf->Cell(50, 5, strlen($row['cliente'])>25?substr($row['cliente'], 0, 25).'...':$row['cliente'], 1);
				$pdf->Cell(100, 5, strlen($row['asunto'])>50?substr($row['asunto'], 0, 50).'...':$row['asunto'], 1, 1);

			}
		}

		$content = $pdf->Output('', 'S');
		$fecha_sql = "$fecha_anio-$fecha_mes-01";

		if($pdf->glosa_reporte)
			$query = "REPLACE INTO reporte_consolidado(periodo, contenido, id_moneda, glosa_reporte) VALUES('$fecha_sql', '".mysql_real_escape_string($content)."', $id_moneda,'$pdf->glosa_reporte')";
		else
			$query = "REPLACE INTO reporte_consolidado(periodo, contenido, id_moneda) VALUES('$fecha_sql', '".mysql_real_escape_string($content)."', $id_moneda)";

		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	}
	elseif($opc=='descargar')
	{
		$query = "SELECT contenido FROM reporte_consolidado WHERE id_reporte_consolidado='$id'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($contenido) = mysql_fetch_array($resp);
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen($contenido));
		header('Content-Disposition: attachment; filename=reporte_consolidado.pdf');
		print $contenido;
	}

	$pagina->titulo = __('Reporte consolidado');
	$pagina->PrintTop();

	if(!$anio_tabla)
		$anio_tabla = date('Y');

	$query = "SELECT id_reporte_consolidado,
				fecha_generacion,
				EXTRACT(MONTH FROM periodo) AS mes,
				glosa_moneda,
				glosa_reporte
			FROM reporte_consolidado
				LEFT JOIN prm_moneda USING(id_moneda)
			WHERE EXTRACT(YEAR FROM periodo)='$anio_tabla'
			ORDER BY periodo ASC";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_reporte_consolidado, $fecha_generacion, $mes, $moneda) = mysql_fetch_array($resp);

?>

	<script type="text/javascript">
		function CambiaFecha(fecha_nueva){
			self.location.href = "reporte_consolidado.php?anio_tabla=" + fecha_nueva;
		}
	</script>
	<style type="text/css">
	#tbl_tarifa
	{
		font-size: 10px;
		padding: 1px;
		margin: 0px;
		vertical-align: middle;
		border:1px solid #CCCCCC;
	}
	.text_box
	{
		font-size: 10px;
		text-align:right;
	}
	</style>


	<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545; border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
		<tr bgcolor="#6CA522">
			<td colspan="3" align="center">
				<img src='<?php echo Conf::ImgDir()."/izquierda.gif"?>' <?php echo $tip_anterior?> class='mano_on' onclick="CambiaFecha('<?php echo $anio_tabla-1?>')" alt="Ir al año anterior.">
				<b><?php echo __('Reportes año ').$anio_tabla?></b>
				<img src='<?php echo Conf::ImgDir()."/derecha.gif"?>' <?php echo $tip_siguiente?> class='mano_on' onclick="CambiaFecha('<?php echo $anio_tabla+1?>')" alt="Ir al año siguiente.">
			</td>
		</tr>
		<tr bgcolor="#6CA522">
			<td><b><?php echo __('Mes')?></b></td>
			<td><b><?php echo __('Fecha generación')?></b></td>
			<td></td>
		</tr>
<?php 
	for($m=1; $m<13; ++$m)
	{
		echo '<tr>';
		echo '<td>'.$nombre_mes[$m-1].'</td>';
		if($mes==$m)
		{
?>
			<td>
				<?php echo "$fecha_generacion ($moneda)"?>
				<?php echo $glosa_reporte? $glosa_reporte:''?>
				<form method="post" name="formulario<?php echo $id_reporte_consolidado?>" action="reporte_consolidado.php">
					<input type="hidden" id="opc" name="opc" value="descargar" />
					<input type="hidden" id="id" name="id" value="<?php echo $id_reporte_consolidado?>" />
					<input type="submit" class="btn" value="<?php echo __('Descargar')?>" />
				</form>
			</td>
<?php 
			list($id_reporte_consolidado, $fecha_generacion, $mes, $moneda, $glosa_reporte) = mysql_fetch_array($resp);
		}
		else
			echo '<td>--</td>';
?>
			<td>
				<form method="post" name="formulario<?php echo $id_reporte_consolidado?>" action="reporte_consolidado.php<?php echo $anio_tabla?"?anio_tabla=$anio_tabla":''?>">
					<input type="hidden" id="opc" name="opc" value="generar" />
					<input type="hidden" id="fecha_anio" name="fecha_anio" value="<?php echo $anio_tabla?>" />
					<input type="hidden" id="fecha_mes" name="fecha_mes" value="<?php echo $m?>" />
					<?php echo Html::SelectQuery($sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda', 'id_moneda', $id_moneda?$id_moneda:'3', '', '', 60);?>
					&nbsp;<?php echo __('Excluir').' '.__('prm_area_proyecto.glosa')?>:&nbsp;
					<?php echo Html::SelectQuery($sesion, 'SELECT prm_area_proyecto.id_area_proyecto, glosa FROM prm_area_proyecto ORDER BY glosa', 'id_area_proyecto_excluido[]','','class="selectMultiple" multiple="multiple" size="2" ', '', 130);?>
					<input type="submit" class="btn" value="<?php echo __('Generar')?>" />
				</form>
			</td>
<?php 
		echo '</tr>';
	}
?>
	</table>
<?php 
	$pagina->PrintBottom();
