<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Gasto.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';

$sesion = new Sesion( array('OFI','COB') );
$pagina = new Pagina( $sesion );

require_once 'Spreadsheet/Excel/Writer.php';

$where = 1;

if ($codigo_actividad != '') {
	$where .= " AND codigo_actividad = " . $codigo_actividad ;
}

if ($codigo_cliente_secundario != '' && $codigo_cliente == '') {
	$cliente = new Cliente($sesion);
	$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
}

if ($codigo_cliente) {
	$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
}

if ($codigo_asunto) {
	$where .= " AND actividad.codigo_asunto = '$codigo_asunto' ";
}

$query_excel = "SELECT SQL_CALC_FOUND_ROWS
					actividad.glosa_actividad,
					asunto.glosa_asunto,
					cliente.glosa_cliente,
					actividad.codigo_actividad
				
				FROM actividad
				LEFT JOIN asunto ON actividad.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
				
				WHERE $where";

$resp_actividad = mysql_query($query_excel, $sesion->dbh) or Utiles::errorSQL($query_excel, __FILE__, __LINE__, $sesion->dbh);

// Creating a workbook
$workbook = new Spreadsheet_Excel_Writer();

// sending HTTP headers
$workbook->send("Listado actividades.xls");

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
	array ( 'bold' => '1', 'size' =>'10','Align' => 'center','Border' => '1', 'FgColor' => 'cyan')
);

$general = &$workbook->addFormat(
	array ( 'size' =>'10','Align' => 'center')
);

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
$worksheet->setColumn(4,5,20);

$worksheet->writeString(1,1,'Listado de Actividades',$titulo);

$fila_datos_documento = 3;

$fila_encabezado = 5;
$columna_cero = 0;
$columna_glosa_actividad = 1;
$columna_glosa_asunto = 2;
$columna_glosa_cliente = 3;
$columna_codigo_actividad = 4;

//Worksheet::write ( integer $row , integer $col , mixed $token , mixed $format=0 )
$worksheet->write($fila_datos_documento,1, 'Fecha Creacion : '.date('Y-m-d h:i:s'),$glosa_detalle_documento);
$worksheet->write($fila_encabezado, $columna_cero, '', $encabezados);
$worksheet->write($fila_encabezado, $columna_glosa_actividad, __('Nombre Actividad'), $encabezados_borde);
$worksheet->write($fila_encabezado, $columna_glosa_asunto, __('Nombre Asunto'), $encabezados_borde);
$worksheet->write($fila_encabezado, $columna_glosa_cliente, __('Nombre Cliente'), $encabezados_borde);
$worksheet->write($fila_encabezado, $columna_codigo_actividad, __('Codigo Actividad'), $encabezados_borde);

// Filas del documento
while(list($glosa_actividad, $glosa_asunto, $glosa_cliente, $codigo_actividad) = mysql_fetch_array($resp_actividad)) {
	++$fila_encabezado;
	$worksheet->write($fila_encabezado, $columna_glosa_actividad, $glosa_actividad, $general_izquierda);
	$worksheet->write($fila_encabezado, $columna_glosa_asunto, $glosa_asunto, $general_izquierda);
	$worksheet->write($fila_encabezado, $columna_glosa_cliente, $glosa_cliente, $general);
	$worksheet->write($fila_encabezado, $columna_codigo_actividad, $codigo_actividad, $general);
}

$workbook->close();

exit;

?>
