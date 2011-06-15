<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('LEE','EDI', 'ADM'));
	$pagina = new Pagina($sesion);

	$wb = new Spreadsheet_Excel_Writer();

	$wb->setVersion(8);
	$wb->send('Carpetas.xls');
	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);


	$encabezado =& $wb->addFormat(array('Size' => 12,
											'VAlign' => 'top',
											'Align' => 'justify',
											'Bold' => '1',
											'Color' => 'black'));
	$tit =& $wb->addFormat(array('Size' => 12,
								'VAlign' => 'top',
								'Align' => 'center',
								'Bold' => '1',
								'Locked' => 1,
								'Border' => 1,
								'FgColor' => '35',
								'Color' => 'black'));
	$tex =& $wb->addFormat(array('Size' => 11,
								'valign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'TextWrap' => 1));

	$ws =& $wb->addWorksheet(__('Carpetas'));
	$ws->setZoom(75);

	$ws->write(0, 0, 'LISTADO DE CARPETAS', $encabezado);
	$ws->mergeCells (0, 0, 0, 8);

	// Definición de columnas
	$col_codigo_carpeta = 0;
	$col_codigo_asunto = 1;
	$col_asunto = 2;
	$col_nombre = 3;
	$col_contenido = 4;
	$col_tipo = 5;
	$col_ubicacion = 6;
	$col_estado = 7;
	$col_usuario_mantenedor = 8;
	$col_usuario_modificacion = 9;
	$col_fecha_modificacion = 10;
	$col_fecha_creacion = 11;
	$col_usuario_creacion = 12;

	$col = 3;
	// Setear el ancho de las columnas
	$ws->setColumn($col_codigo_carpeta, $col_codigo_carpeta, 8);
	$ws->setColumn($col_codigo_asunto, $col_codigo_asunto, 15);
	$ws->setColumn($col_asunto, $col_asunto, 30);
	$ws->setColumn($col_nombre, $col_nombre, 30);
	$ws->setColumn($col_contenido, $col_contenido, 30);
	$ws->setColumn($col_tipo, $col_tipo, 17);
	$ws->setColumn($col_ubicacion, $col_ubicacion, 18);
	$ws->setColumn($col_estado, $col_estado, 17);
	$ws->setColumn($col_usuario_mantenedor, $col_usuario_mantenedor, 17);
	$ws->setColumn($col_usuario_modificacion, $col_usuario_modificacion, 17);
	$ws->setColumn($col_fecha_modificacion, $col_fecha_modificacion, 17);
	$ws->setColumn($col_fecha_creacion, $col_fecha_creacion, 17);
	$ws->setColumn($col_usuario_creacion, $col_usuario_creacion, 17);

	$fila_inicial = 4;

	$ws->write($fila_inicial, $col_codigo_carpeta, __('No.'), $tit);
	$ws->write($fila_inicial, $col_codigo_asunto, __('Código'), $tit);
	$ws->write($fila_inicial, $col_asunto, __('Asunto'), $tit);
	$ws->write($fila_inicial, $col_nombre, __('Nombre'), $tit);
	$ws->write($fila_inicial, $col_contenido, __('Contenido'), $tit);
	$ws->write($fila_inicial, $col_tipo, __('Tipo'), $tit);
	$ws->write($fila_inicial, $col_ubicacion, __('Ubicación'), $tit);
	$ws->write($fila_inicial, $col_estado, __('Estado'), $tit);
	$ws->write($fila_inicial, $col_usuario_mantenedor, __('Usuario'), $tit);
	$ws->write($fila_inicial, $col_usuario_modificacion, __('Secretaria'), $tit);
	$ws->write($fila_inicial, $col_fecha_modificacion, __('Fecha Cambio'), $tit);
	$ws->write($fila_inicial, $col_fecha_creacion, __('Fecha Creación'), $tit);
	$ws->write($fila_inicial, $col_usuario_creacion, __('Creador'), $tit);
	$fila_inicial++;

	#La lista viene de la pagina en la cual se incluye esta.
	for($i = 0; $i < $lista->num; $i++)
	{
		$carpeta = $lista->Get($i);

		$ws->write($fila_inicial + $i, $col_codigo_carpeta, $carpeta->fields[codigo_carpeta], $tex);
		$ws->write($fila_inicial + $i, $col_codigo_asunto, $carpeta->fields[codigo_asunto_secundario], $tex);
		$ws->write($fila_inicial + $i, $col_asunto, $carpeta->fields[glosa_asunto], $tex);
		$ws->write($fila_inicial + $i, $col_nombre, $carpeta->fields[nombre_carpeta], $tex);
		$ws->write($fila_inicial + $i, $col_contenido, $carpeta->fields[glosa_carpeta], $tex);
		$ws->write($fila_inicial + $i, $col_tipo, $carpeta->fields[glosa_tipo_carpeta], $tex);
		$ws->write($fila_inicial + $i, $col_ubicacion, $carpeta->fields[glosa_bodega], $tex);
		$ws->write($fila_inicial + $i, $col_estado, $carpeta->fields[glosa_tipo_movimiento_carpeta], $tex);
		$ws->write($fila_inicial + $i, $col_usuario_mantenedor, $carpeta->fields[username], $tex);
		$ws->write($fila_inicial + $i, $col_usuario_modificacion, $carpeta->fields[usuario_modificacion], $tex);
		$ws->write($fila_inicial + $i, $col_fecha_modificacion, Utiles::sql2date($carpeta->fields[fecha_modificacion]), $tex);
		$ws->write($fila_inicial + $i, $col_fecha_creacion, Utiles::sql2date($carpeta->fields[fecha_creacion]), $tex);
		$ws->write($fila_inicial + $i, $col_usuario_creacion, $carpeta->fields[usuario_creacion], $tex);
	}
	$wb->close();
	exit;
?>
