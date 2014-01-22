<?php
$tini = time();
$fechactual = date('Ymd');
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

set_time_limit(3600);

if ($xls) {

	$where = 1;

	if (!empty($fecha1)){
		$fecha1 = date('Y-m-d', strtotime($fecha1));	
	}

	if (!empty($fecha2)){
		$fecha2 = date('Y-m-d', strtotime($fecha2));	
	}

	if (!empty($codigo_cliente)) {
		$where .= " AND cli.codigo_cliente = '$codigo_cliente' ";
	}

	if (!empty($fecha1) && !empty($fecha2)){
		$where .=" AND fecha_cobro BETWEEN '".$fecha1."' AND '".$fecha2."' ";
	}

	$query_excel = "SELECT
						cli.glosa_cliente AS glosa_cliente,
						asu.glosa_asunto AS glosa_asunto,
						cp.monto_estimado AS monto_estimado,
						cp.fecha_cobro AS fecha_cobro,
						cli.codigo_cliente AS codigo_cliente,
						asu.codigo_asunto AS codigo_asunto,
						cp.descripcion AS descripcion,
						cp.observaciones AS observaciones,
						IF(cp.id_cobro IS NULL, 'SIN COBRO', 'COBRADO') AS cobrado,
						cp.id_cobro AS numero_cobro

					FROM cobro_pendiente as cp

					LEFT JOIN contrato AS con ON cp.id_contrato = con.id_contrato
					LEFT JOIN asunto AS asu ON con.id_contrato = asu.id_contrato
					LEFT JOIN cliente AS cli ON con.codigo_cliente = cli.codigo_cliente

					WHERE $where
					AND monto_estimado != '0'";

	$resultado = mysql_query($query_excel, $sesion->dbh) or Utiles::errorSQL($query_excel, __FILE__, __LINE__, $sesion->dbh);

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send("Reporte_Hitos.xls");

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Listado Actividades');

	//Styles
	$titulo = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'12','Align' => 'center')
	);

	$glosa_detalle_documento = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center')
	);

	$glosa_detalle_documento_right = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'right')
	);

	$encabezados = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center','Border' => '0')
	);

	$encabezados_borde = &$workbook->addFormat(
		array ( 'bold' => '1', 'size' =>'10','Align' => 'center','Border' => '1', 'FgColor' => 'green')
	);

	$general = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'center')
	);

	$general_codigo_cliente = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'center')
	);

	$general_codigo_cliente->setNumFormat("0");

	$general_izquierda = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'left')
	);

	$general_derecha = &$workbook->addFormat(
		array ( 'size' =>'10','Align' => 'right')
	);

	//Worksheet::setColumn ( integer $firstcol , integer $lastcol , float $width , mixed $format=0 , integer $hidden=0 )
	$worksheet->setColumn(0,0,10);
	$worksheet->setColumn(1,1,60);
	$worksheet->setColumn(2,2,60);
	$worksheet->setColumn(3,3,30);
	$worksheet->setColumn(4,4,20);
	$worksheet->setColumn(5,5,20);
	$worksheet->setColumn(6,6,20);
	$worksheet->setColumn(7,7,20);
	$worksheet->setColumn(8,8,50);
	$worksheet->setColumn(9,9,10);
	$worksheet->setColumn(10,10,15);

	$worksheet->writeString(1,1,'Reporte Hitos',$titulo);

	$fila_datos_documento = 3;

	$fila_encabezado = 5;

	$columna_cero = 0;
	$columna_glosa_cliente = 1;
	$columna_glosa_asunto = 2;
	$columna_monto_estimado = 3;
	$columna_fecha_cobro = 4;
	$columna_codigo_cliente = 5;
	$columna_codigo_asunto = 6;
	$columna_descripcion = 7;
	$columna_observaciones = 8;
	$columna_cobrado = 9;
	$columna_id_cobro = 10;

	//Worksheet::write ( integer $row , integer $col , mixed $token , mixed $format=0 )
	$worksheet->write($fila_datos_documento,1, 'Fecha Creacion : '.date('Y-m-d h:i:s'),$glosa_detalle_documento);
	$worksheet->write($fila_encabezado, $columna_cero, '', $encabezados);

	$worksheet->write($fila_encabezado, $columna_glosa_cliente, __('Glosa Cliente'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_glosa_asunto, __('Glosa Asunto'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_monto_estimado, __('Monto Estimado'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_fecha_cobro, __('Fecha Cobro'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_codigo_cliente, __('Codigo Cliente'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_codigo_asunto, __('Codigo Asunto'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_descripcion, __('Descripcion'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_observaciones, __('Observaciones'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_cobrado, __('Cobrado'), $encabezados_borde);
	$worksheet->write($fila_encabezado, $columna_id_cobro, __('Numero Cobro'), $encabezados_borde);

	// Filas del documento
	while ( list ($glosa_cliente, $glosa_asunto, $monto_estimado, $fecha_cobro, $codigo_cliente, $codigo_asunto, $descripcion, $observaciones, $cobrado, $id_cobro ) = mysql_fetch_array($resultado)) {

		++$fila_encabezado;
		$worksheet->write($fila_encabezado, $columna_glosa_cliente, $glosa_cliente, $general_izquierda);
		$worksheet->write($fila_encabezado, $columna_glosa_asunto, $glosa_asunto, $general_izquierda);
		$worksheet->write($fila_encabezado, $columna_monto_estimado, $monto_estimado, $general);
		$worksheet->write($fila_encabezado, $columna_fecha_cobro, $fecha_cobro, $general);
		$worksheet->write($fila_encabezado, $columna_codigo_cliente, $codigo_cliente, $general);
		$worksheet->write($fila_encabezado, $columna_codigo_asunto, $codigo_asunto, $general);
		$worksheet->write($fila_encabezado, $columna_descripcion, $descripcion, $general);
		$worksheet->write($fila_encabezado, $columna_observaciones, $observaciones, $general);
		$worksheet->write($fila_encabezado, $columna_cobrado, $cobrado, $general);
		$worksheet->write($fila_encabezado, $columna_id_cobro, $id_cobro, $general);

	}

	$workbook->close();

	exit;

	}

$pagina->titulo = __('Reporte Horas por Facturar');
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
				<?php $query = 'SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC';
					echo Html::SelectQuery($sesion, $query, "codigo_cliente", $codigo_cliente, '', "Todos", "200");
				?>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td align=center colspan="4">
				<input type="hidden" name="debug" value="<?php echo $debug ?>" />
				<input type="submit" class=btn value="<?php echo __('Generar reporte') ?>" name="btn_reporte">
			</td><td>&nbsp;</td>
		</tr>
	</table>
</form>

<?php
echo InputId::Javascript($sesion);
$pagina->PrintBottom();
