<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

set_time_limit(3600);

if ($xls) {

	$criteria = new Criteria($sesion);

	if ($codigo_cliente_secundario) {
		$cliente = new Cliente($sesion);
		$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
	}

	if (!empty($fecha1)){
		$fecha1 = date('Y-m-d', strtotime($fecha1));
	}

	if (!empty($fecha2)){
		$fecha2 = date('Y-m-d', strtotime($fecha2));
	}


	if (!empty($codigo_cliente)) {
		$criteria->add_restriction(CriteriaRestriction::equals('cli.codigo_cliente', "'{$codigo_cliente}'"));
	}

	if (!empty($fecha1)) {
		$criteria->add_restriction(CriteriaRestriction::greater_or_equals_than('cp.fecha_cobro', $fecha1));
	}

	if (!empty($fecha2)) {
		$criteria->add_restriction(CriteriaRestriction::lower_or_equals_than('cp.fecha_cobro', $fecha2));
	}

	if ($mostrar_con_cobro){
		$criteria->add_restriction(CriteriaRestriction::is_not_null('cp.id_cobro'));
	}

	$criteria
		->add_select('cli.glosa_cliente', 'glosa_cliente')
		->add_select("GROUP_CONCAT(DISTINCT asu.glosa_asunto ORDER BY asu.glosa_asunto SEPARATOR ', ')", 'glosa_asunto')
		->add_select('cp.monto_estimado', 'monto_estimado')
		->add_select('pmcp.simbolo', 'moneda_estimada')
		->add_select("IF(cp.fecha_cobro IS NULL, DATE_FORMAT(cob.fecha_emision,'%d-%m-%Y'), DATE_FORMAT(cp.fecha_cobro,'%d-%m-%Y'))", 'fecha_cobro')
		->add_select('asu.codigo_asunto', 'codigo_asunto')
		->add_select('cp.descripcion', 'descripcion')
		->add_select('cp.observaciones', 'observaciones')
		->add_select("IF(cp.id_cobro IS NULL, 'SIN COBRO', cob.estado)", 'estado_cobro')
		->add_select('cp.id_cobro', 'numero_cobro')
		->add_select('cob.monto', 'monto_cobrado')
		->add_select("IF(cob.id_cobro IS NULL,'', pmcob.simbolo)", 'moneda_cobrada')
		->add_select('cob.documento', 'numero_factura')
		->add_select('con.id_moneda')
		->add_from('cobro_pendiente', 'cp')
		->add_left_join_with('contrato as con', CriteriaRestriction::equals('cp.id_contrato', 'con.id_contrato'))
		->add_left_join_with('prm_moneda as pmcp', CriteriaRestriction::equals('con.id_moneda', 'pmcp.id_moneda'))
		->add_left_join_with('cobro AS cob', CriteriaRestriction::equals('cp.id_cobro', 'cob.id_cobro'))
		->add_left_join_with('prm_moneda AS pmcob', CriteriaRestriction::equals('cob.id_moneda', 'pmcob.id_moneda'))
		->add_left_join_with('asunto AS asu', CriteriaRestriction::equals('con.id_contrato', 'asu.id_contrato'))
		->add_left_join_with('cliente AS cli', CriteriaRestriction::equals('con.codigo_cliente', 'cli.codigo_cliente'));


	$resultado = $criteria->run();

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send("planilla_reporte_hitos.xls");

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Listado Actividades');

	//Styles

	$workbook->setCustomColor(35, 220, 255, 220);
	
	$titulo = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'12','Align' => 'left')
	);

	$glosa_detalle_documento = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center')
	);

	$glosa_detalle_documento_left = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'left')
	);

	$glosa_detalle_documento_right = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'right')
	);

	$encabezados = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center','Border' => '0')
	);

	$encabezados_borde = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center','Border' => '1', 'FgColor' => 35)
	);

	$general = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'center')
	);

	$fcodigo_cliente = &$workbook->addFormat(
		array ('Size' => 10, 'Align' => 'center')
	);
	$fcodigo_cliente->setNumFormat("0");

	$general_izquierda = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'left')
	);

	$general_derecha = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'right')
	);

	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($cobro->fields['codigo_idioma']);

	$ff = str_replace('%m', 'MM', $ff);
	$ff = str_replace('%y', 'YYYY', $ff);
	$ff = str_replace('%Y', 'YYYY', $ff);

	//Worksheet::setColumn ( integer $firstcol , integer $lastcol , float $width , mixed $format=0 , integer $hidden=0 )
	$worksheet->setColumn(0,0,40);
	$worksheet->setColumn(1,1,40);
	$worksheet->setColumn(2,2,20);
	$worksheet->setColumn(3,3,10);
	$worksheet->setColumn(4,4,20);
	$worksheet->setColumn(5,5,20);
	$worksheet->setColumn(6,6,50);
	$worksheet->setColumn(7,7,50);
	$worksheet->setColumn(8,8,20);
	$worksheet->setColumn(9,9,15);
	$worksheet->setColumn(10,10,15);
	$worksheet->setColumn(11,11,15);
	$worksheet->setColumn(12,12,20);

	$worksheet->writeString(1,0,'Reporte Hitos',$titulo);

	$celda_fecha_creacion = 2;
	$celda_periodo_reporte = 3;

	$fila_encabezado = 5;

	$columna_glosa_cliente = 0;
	$columna_glosa_asunto = 1;
	$columna_monto_estimado = 2;
	$columna_moneda_estimada = 3;
	$columna_fecha_cobro = 4;
	$columna_codigo_asunto = 5;
	$columna_descripcion = 6;
	$columna_observaciones = 7;
	$columna_estado_cobro = 8;
	$columna_numero_cobro = 9;
	$columna_monto_cobrado = 10;
	$columna_moneda_cobrada = 11;
	$columna_numero_factura = 12;

	//Worksheet::write ( integer $row , integer $col , mixed $token , mixed $format=0 )
	$worksheet->write($celda_fecha_creacion,0, 'Fecha Creación : '.date('d-m-Y'),$glosa_detalle_documento_left);

	$periodo = "Periodo : Desde ".$fecha1." Hasta ".$fecha2;

	$worksheet->write($celda_periodo_reporte,0, $periodo,$glosa_detalle_documento_left);

	$worksheet->write($fila_encabezado, $columna_glosa_cliente, __('Glosa Cliente'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_glosa_asunto, __('Asuntos'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_monto_estimado, __('Monto Estimado'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_moneda_estimada, __('Moneda'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_fecha_cobro, __('Fecha Cobro'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_codigo_asunto, __('Cliente').'-'.__('Asunto'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_descripcion, __('Descripcion'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_observaciones, __('Observaciones'), $encabezados_borde);
	$worksheet->Write($fila_encabezado, $columna_estado_cobro, __('Estado Cobro'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_numero_cobro, __('Numero Cobro'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_monto_cobrado, __('Monto Cobrado'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_moneda_cobrada, __('Moneda'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_numero_factura, __('Numero Factura'), $encabezados_borde);

	// Filas del documento
	while ( list (	$glosa_cliente,
					$glosa_asunto,
					$monto_estimado,
					$moneda_estimada,
					$fecha_cobro,
					$codigo_asunto,
					$descripcion,
					$observaciones,
					$estado_cobro,
					$numero_cobro,
					$monto_cobrado,
					$moneda_cobrada,
					$numero_factura,
					$id_moneda_contrato ) = mysql_fetch_array($resultado)) {

		$cifras_decimales = Utiles::glosa($sesion, $id_moneda_contrato, 'cifras_decimales', 'prm_moneda', 'id_moneda');

		if ($cifras_decimales) {
			$decimales = '.';
			while ($cifras_decimales-- > 0) {
				$decimales .= '0';
			}
		} else {
			$decimales = '';
		}

		$simbolo_moneda = Utiles::glosa($sesion, $id_moneda_contrato, 'simbolo', 'prm_moneda', 'id_moneda');

		$formato_moneda_monto = &$workbook->addFormat(array('Size' => 10,
					'VAlign' => 'top',
					'Align' => 'right',
					'Color' => 'black',
					'NumFormat' => "#,##,0$decimales"));

		++$fila_encabezado;
		$worksheet->write($fila_encabezado, $columna_glosa_cliente, $glosa_cliente, $general_izquierda);
		$worksheet->write($fila_encabezado, $columna_glosa_asunto, $glosa_asunto, $general_izquierda);
		$worksheet->write($fila_encabezado, $columna_monto_estimado, $monto_estimado, $formato_moneda_monto);
		$worksheet->write($fila_encabezado, $columna_moneda_estimada, $moneda_estimada, $general);
		$worksheet->write($fila_encabezado, $columna_fecha_cobro, $fecha_cobro, $general);
		$worksheet->write($fila_encabezado, $columna_codigo_asunto, $codigo_asunto, $general);
		$worksheet->write($fila_encabezado, $columna_descripcion, $descripcion, $general_izquierda);
		$worksheet->write($fila_encabezado, $columna_observaciones, $observaciones, $general_izquierda);
		$worksheet->Write($fila_encabezado, $columna_estado_cobro, $estado_cobro, $general);
		$worksheet->write($fila_encabezado, $columna_numero_cobro, $numero_cobro, $general);
		$worksheet->write($fila_encabezado, $columna_monto_cobrado, $monto_cobrado, $formato_moneda_monto);
		$worksheet->write($fila_encabezado, $columna_moneda_cobrada, $moneda_cobrada, $general);
		$worksheet->write($fila_encabezado, $columna_numero_factura, $numero_factura, $general);

	}

	$workbook->close();

	exit;

}

$pagina->titulo = __('Reporte Hitos');
$pagina->PrintTop();

?>

<form method="post" name="formulario" action="planilla_reporte_hitos.php?xls=1">

	<input type="hidden" name="reporte" value="generar" />
	
	<table  class="border_plomo tb_base" width="40%" align="center">

		<tr>
			<td align="right"><b><?php echo __('Fecha desde') ?></b></td>
			<td align="left"><input type="text" class="fechadiff" name="fecha1" id="fecha1" value="<?php echo ($fecha1 ? $fecha1 : date('d-m-Y', strtotime('-1 year'))); ?>"/></td>
		</tr>
		<tr>
			<td align="right"><b><?php echo __('Fecha hasta') ?></b></td> 
			<td align="left"><input type="text" class="fechadiff" name="fecha2" id="fecha2" value="<?php echo ($fecha2 ? $fecha2 : date('d-m-Y')); ?>"/></td>
		</tr>

		<tr>
			<td align="right">
				<b><?php echo __('Clientes')?>:</b>
			</td>
			<td align="left">
				<?php
					$criteria = new Criteria($sesion);
					$criteria->add_from('cliente')->add_ordering('nombre')->add_ordering_criteria('ASC');
					$criteria->add_select('glosa_cliente', 'nombre');
					if (Conf::GetConf($sesion, 'CodigoSecundario')) {
						$criteria->add_select('codigo_cliente_secundario');
					} else {
						$criteria->add_select('codigo_cliente');
					}
					echo Html::SelectQuery($sesion, $criteria->get_plain_query(), "codigo_cliente", $codigo_cliente, '', "Todos", "200");
				?>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

		<tr>
			<td align="right">
				<input type="checkbox" name="mostrar_con_cobro" id="mostrar_con_cobro" value="1" <?php echo $mostrar_con_cobro ? 'checked="checked"' : '' ?> />
			</td>
			<td align="left">
				<label for="mostrar_con_cobro"><?php echo __('Considerar sólo hitos cobrados'); ?></label>
			</td>
		</tr>
		
		<tr>
			<td align=center colspan="4">
				<input type="submit" class=btn value="<?php echo __('Generar reporte') ?>" name="btn_reporte">
			</td><td>&nbsp;</td>
		</tr>
	</table>
</form>

<?php
echo InputId::Javascript($sesion);
$pagina->PrintBottom();
