<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM', 'COB'));
ini_set("memory_limit", "256M");
$where_cobro = ' 1 ';

if ($id_cobro) {
	$where_cobro .= " AND cobro.id_cobro=$id_cobro ";
}

// Procesar los filtros
if ($codigo_cliente_secundario) {
	$cliente = new Cliente($sesion);
	$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
}
if ($codigo_cliente) {
	$cliente = new Cliente($sesion);
	$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
}
if ($activo) {
	$where_cobro .= " AND contrato.activo = 'SI' ";
} elseif ($no_activo) {
	$where_cobro .= " AND contrato.activo = 'NO' ";
}
if ($forma_cobro) {
	$where_cobro .= " AND contrato.forma_cobro = '$forma_cobro' ";
}
if ($id_usuario) {
	$where_cobro .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
}
if ($codigo_cliente) {
	$where_cobro .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
}
if ($id_grupo_cliente) {
	$where_cobro .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";
}

if ($forma_cobro) {
	$where_cobro .= " AND contrato.forma_cobro = '$forma_cobro' ";
}
if ($tipo_liquidacion) { //1:honorarios, 2:gastos, 3:mixtas
	$incluye_honorarios = $tipo_liquidacion & 1 ? true : false;
	$incluye_gastos = $tipo_liquidacion & 2 ? true : false;
	$where_cobro .= " AND cobro.incluye_gastos = '$incluye_gastos' AND cobro.incluye_honorarios = '$incluye_honorarios' ";
}
if ($codigo_asunto) {
	$where_cobro .= " AND asunto.codigo_asunto = '$codigo_asunto' ";
}


if (!$id_cobro) {
	$borradores = true;
	$opc_ver_gastos = 1;
}
$mostrar_resumen_de_profesionales = 1;

if ($guardar_respaldo) {

$wb = new WorkbookMiddleware(Conf::ServerDir().'/respaldos/ResumenCobros'.date('ymdHis'));
} else {
	$wb = new WorkbookMiddleware();
}

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);
$wb->setCustomColor(37, 255, 255, 0);


//--------------- Definamos los formatos generales, los que quedan constante por todo el documento -------------
$CellFormat = new CellFormat($wb);
$CellFormat->setDefault([
	'Size' => 7,
	'VAlign' => 'top',
	'Align' => 'left',
	'Bold' => 0,
	'Color' => 'black'
]);

$CellFormat->add('encabezado', [
	'Size' => 10,
	'VAlign' => 'middle',
	'Bold' => 1,
]);
$CellFormat->add('encabezado_datos_cliente', [
	'Size' => 10,
	'VAlign' => 'middle',
	'Bold' => 1,
]);
$CellFormat->add('encabezado_underline', [
	'Size' => 10,
	'VAlign' => 'middle',
	'Bold' => 1,
	'Underline' => 1,
]);
$CellFormat->add('encabezado_derecha', [
	'Size' => 10,
	'VAlign' => 'top',
	'Align' => 'right',
	'Bold' => 1,
]);
$CellFormat->add('titulo', [
	'Size' => 10,
	'VAlign' => 'top',
	'TextWrap' => 1,
	'Bold' => 1,
	'Locked' => 1,
	'Bottom' => 1,
	'FgColor' => 35,
]);
$CellFormat->add('titulo_centrado', [
	'Size' => 10,
	'VAlign' => 'top',
	'Align' => 'center',
	'TextWrap' => 1,
	'Bold' => 1,
	'Locked' => 1,
	'Bottom' => 1,
	'FgColor' => 35,
]);
$CellFormat->add('normal_centrado', [
	'Align' => 'center',
]);
$CellFormat->add('normal', []);

$CellFormat->add('descripcion', [
	'TextWrap' => 1
]);
$CellFormat->add('tiempo', [
	'NumFormat' => '[h]:mm'
]);
$CellFormat->add('total', [
	'Size' => 10,
	'Bold' => 1,
	'Top' => 1,
]);
$CellFormat->add('instrucciones12', [
	'Size' => 12,
	'VAlign' => 'top',
	'Bold' => 1,
]);
$CellFormat->add('instrucciones10', [
	'Size' => 10,
	'VAlign' => 'top',
	'Bold' => 1,
]);
$CellFormat->add('resumen_rentabilidad', [
	'Size' => 10,
	'VAlign' => 'top',
	'Top' => 2,
	'Left' => 2,
	'Bottom' => 2,
	'Bold' => 1,
]);
$CellFormat->add('porcentaje_rentabilidad', [
	'Size' => 10,
	'VAlign' => 'top',
	'Top' => 2,
	'Right' => 2,
	'Bottom' => 2,
	'Bold' => 1,
	'NumFormat' => "0.00[$%]"
]);
$CellFormat->add('tiempo_total', [
	'Size' => 10,
	'VAlign' => 'top',
	'Bold' => 1,
	'Top' => 1,
	'NumFormat' => '[h]:mm'
]);
$CellFormat->add('resumen_text', [
	'Border' => 1,
	'TextWrap' => 1
]);
$CellFormat->add('resumen_text_derecha', [
	'Align' => 'right',
	'Border' => 1,
]);
$CellFormat->add('resumen_text_izquierda', [
	'Align' => 'left',
	'Border' => 1,
]);
$CellFormat->add('resumen_text_titulo', [
	'Size' => 9,
	'Valign' => 'top',
	'Align' => 'left',
	'Bold' => 1,
	'Border' => 1,
]);
$CellFormat->add('resumen_text_amarillo', [
	'Size' => 7,
	'Valign' => 'top',
	'Align' => 'left',
	'Border' => 1,
	'FgColor' => '37',
	'Color' => 'black',
	'TextWrap' => 1
]);
$CellFormat->add('numeros', [
	'Size' => 7,
	'VAlign' => 'top',
	'Align' => 'right',
	'Border' => 1,
	'Color' => 'black',
	'NumFormat' => '0'
]);
$CellFormat->add('numeros_amarillo', [
	'Size' => 7,
	'Valign' => 'top',
	'Align' => 'right',
	'Border' => 1,
	'FgColor' => '37',
	'Color' => 'black',
	'TextWrap' => '0'
]);


// Definimos las columnas, mantenerlas así permite agregar nuevas columnas sin tener que rehacer todo.
/*
  IMPORTANTE:
    Se asume que las columnas $col_id_trabajo, $col_fecha y $col_abogado son las primeras tres y que
    $col_tarifa_hh, $col_valor_trabajo y $col_id_abogado son las últimas tres (aunque $col_id_abogado es
    una columan oculta), en ese orden.
    Estos valores se usan para definir dónde se escribe el encabezado, resumen y otros, conviene no modificarlas.
    Se puede modificar el orden de las otras columnas.
*/
$col = 0;
$col_fecha = $col++;

$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma->Load($cobro->fields['codigo_idioma']);

$col_abogado = $col++;
if ($opc_ver_cobrable) {
	$col_es_cobrable = $col++;
}
if (!$opc_ver_asuntos_separados) {
	$col_asunto = $col++;
}
$col_descripcion = $col++;
if ($opc_ver_horas_trabajadas) {
	$col_duracion_trabajada = $col++;
}
$col_duracion_cobrable = $col++;
if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
	$col_duracion_retainer = $col++;
	$col_tarificable_hh = $col++;
}

$col_tarifa_hh = $col++;
$col_valor_trabajo = $col++;
$col_valor_trabajo_flat_fee = $col++;
$col_id_abogado = $col++;
unset($col);

// Valores para usar en las fórmulas de la hoja
$col_formula_descripcion = Utiles::NumToColumnaExcel($col_descripcion);
if ($opc_ver_horas_trabajadas) {
	$col_formula_duracion_trabajada = Utiles::NumToColumnaExcel($col_duracion_trabajada);
}
$col_formula_duracion_cobrable = Utiles::NumToColumnaExcel($col_duracion_cobrable);
if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
	$col_formula_tarificable_hh = Utiles::NumToColumnaExcel($col_tarificable_hh);
	$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
}
$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
$col_formula_valor_trabajo_flat_fee = Utiles::NumToColumnaExcel($col_valor_trabajo_flat_fee);

$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
$col_formula_abogado = Utiles::NumToColumnaExcel($col_abogado);
if ($col_asunto)
	$col_formula_asunto = Utiles::NumToColumnaExcel($col_asunto);


// Esta variable se usa para que cada página tenga un nombre único.
$numero_pagina = 0;

// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir
$query = "SELECT DISTINCT cobro.id_cobro
							FROM cobro
							JOIN contrato ON cobro.id_contrato = contrato.id_contrato
							LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato
							LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
						 WHERE $where_cobro AND cobro.estado " . ($borradores ? ' IN (\'CREADO\',\'EN REVISION\')' : '=\'' . $cobro->fields['estado'] . '\'') . "
						 ORDER BY cliente.glosa_cliente,cobro.codigo_cliente";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

while (list($id_cobro) = mysql_fetch_array($resp)) {
	// ---------------- Cargar los datos necesarios dentro del cobro -----------------
	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);
	$cobro->LoadAsuntos();
	$cobro->GuardarCobro();

	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($cobro->fields['codigo_idioma']);


	// Estas variables son necesario para poder decidir si se imprima una tabla o no,
	// generalmente si no tiene data no se escribe
	$query_cont_trabajos_cobro = "SELECT COUNT(*) FROM trabajo WHERE id_cobro=" . $cobro->fields['id_cobro'];
	$resp_cont_trabajos_cobro = mysql_query($query_cont_trabajos_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_trabajos_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_trabajos_cobro) = mysql_fetch_array($resp_cont_trabajos_cobro);

	$query_cont_tramites_cobro = "SELECT COUNT(*) FROM tramite WHERE id_cobro=" . $cobro->fields['id_cobro'];
	$resp_cont_tramites_cobro = mysql_query($query_cont_tramites_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_tramites_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_tramites_cobro) = mysql_fetch_array($resp_cont_tramites_cobro);

	$query_cont_gastos_cobro = "SELECT COUNT(*) FROM cta_corriente WHERE id_cobro=" . $cobro->fields['id_cobro'];
	$resp_cont_gastos_cobro = mysql_query($query_cont_gastos_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_gastos_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_gastos_cobro) = mysql_fetch_array($resp_cont_gastos_cobro);


	$cobro_moneda = new CobroMoneda($sesion);
	$cobro_moneda->Load($cobro->fields['id_cobro']);

	$contrato = new Contrato($sesion);
	$contrato->Load($cobro->fields['id_contrato']);

	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);

	$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'simbolo', 'prm_moneda', 'id_moneda');
	$glosa_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'glosa_moneda', 'prm_moneda', 'id_moneda');
	if ($glosa_moneda == "Euro") {
		$simbolo_moneda = "EUR";
	}

	$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'cifras_decimales', 'prm_moneda', 'id_moneda');

	$cifras_decimales_opc_moneda_total = $cifras_decimales;
	$simbolo_moneda_opc_moneda_total = $simbolo_moneda;
	if ($cifras_decimales > 0) {
		$decimales = '.';
		while ($cifras_decimales-- > 0) {
			$decimales .= '0';
		}
	} else {
		$decimales = '';
	}

	$CellFormat->add('moneda_resumen', [
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => '1',
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('moneda_gastos', [
		'Size' => 7,
		'VAlign' => 'top',
		'Align' => 'right',
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('moneda_gastos_total', [
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => 1,
		'Top' => 1,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('moneda_resumen_cobro', [
		'Size' => 7,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => '1',
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);

	$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'simbolo', 'prm_moneda', 'id_moneda');
	$glosa_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'glosa_moneda', 'prm_moneda', 'id_moneda');
	if ($glosa_moneda == "Euro") {
		$simbolo_moneda = "EUR";
	}
	$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
	$cifras_decimales_moneda_monto = $cifras_decimales;
	$simbolo_moneda_opc_moneda_monto = $simbolo_moneda;
	if ($cifras_decimales) {
		$decimales = '.';
		while ($cifras_decimales-- > 0)
			$decimales .= '0';
	} else
		$decimales = '';
	$CellFormat->add('moneda_monto_resumen', [
		'Size' => 7,
		'Valign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'simbolo', 'prm_moneda', 'id_moneda');
	$glosa_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'glosa_moneda', 'prm_moneda', 'id_moneda');
	if ($glosa_moneda == "Euro") {
		$simbolo_moneda = "EUR";
	}
	$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
	if ($cifras_decimales) {
		$decimales = '.';
		while ($cifras_decimales-- > 0)
			$decimales .= '0';
	} else
		$decimales = '';
	$CellFormat->add('moneda_monto',[
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => 1,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda');
	$glosa_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'glosa_moneda', 'prm_moneda', 'id_moneda');
	if ($glosa_moneda == "Euro") {
		$simbolo_moneda = "EUR";
	}
	$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['id_moneda'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
	if ($cifras_decimales) {
		$decimales = '.';
		while ($cifras_decimales-- > 0)
			$decimales .= '0';
	} else
		$decimales = '';
	$CellFormat->add('moneda', [
		'Size' => 7,
		'VAlign' => 'top',
		'Align' => 'right',
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('moneda_total', [
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => 1,
		'Top' => 1,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('rentabilidad_moneda_total', [
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => 1,
		'Top' => 2,
		'Right' => 2,
		'Bottom' => 2,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);
	$CellFormat->add('moneda_encabezado', [
		'Size' => 10,
		'VAlign' => 'top',
		'Align' => 'right',
		'Bold' => 1,
		'Color' => 'black',
		'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"
	], [], [], true);


	// **************** Imprime el encabezado de la hoja **********************
	// El largo máximo para el nombre de una hoja son 31 caracteres, reservamos 4 para el número de página y un espacio.
	// Es importante notar que los nombres se truncan automáticamente con el primer caracter con tilde o ñ.
	$nombre_pagina = ++$numero_pagina . ' ';
	if (strlen($cliente->fields['glosa_cliente']) > 27) {
		$nombre_pagina .= substr($cliente->fields['glosa_cliente'], 0, 24) . '...';
	}	else {
		$nombre_pagina .= $cliente->fields['glosa_cliente'];
	}

	$PrmExcelCobro = new PrmExcelCobro($sesion);

	$ws =& $wb->addWorksheet($nombre_pagina);
	$ws->setPaper(1);
	$ws->hideScreenGridlines();
	$ws->setMargins(0.01);
	if (UtilesApp::GetConf($sesion, 'ImprimirExcelCobrosUnaPagina')) {
		$ws->fitToPages(1, 1);
	}

	// Seteamos el ancho de las columnas >>>>>>>>>>>>>>>>>
	if (Conf::read('UsarResumenExcel')) {
		$ws->setColumn($col_fecha, $col_fecha, 20);
		$ws->setColumn($col_abogado, $col_abogado, 15);
	} else {
		$ws->setColumn($col_fecha, $col_fecha, $PrmExcelCobro->getTamano('fecha', 'Listado de trabajos'));
		$ws->setColumn($col_abogado, $col_abogado, $PrmExcelCobro->getTamano('abogado', 'Listado de trabajos'));
	}
	if (!$opc_ver_asuntos_separados) {
		$ws->setColumn($col_asunto, $col_asunto, $PrmExcelCobro->getTamano('asunto', 'Listado de trabajos'));
	}

	$ws->setColumn($col_descripcion, $col_descripcion, $PrmExcelCobro->getTamano('descripcion', 'Listado de trabajos'));
	if ($opc_ver_horas_trabajadas)
		$ws->setColumn($col_duracion_trabajada, $col_duracion_trabajada, $PrmExcelCobro->getTamano('duracion_trabajada', 'Listado de trabajos'));
	if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
		$ws->setColumn($col_tarificable_hh, $col_tarificable_hh, 1.2 * $PrmExcelCobro->getTamano('duracion_cobrable', 'Listado de trabajos'));
		$ws->setColumn($col_duracion_retainer, $col_duracion_retainer, 1.2 * $PrmExcelCobro->getTamano('duracion_cobrable', 'Listado de trabajos'));
	}
	$ws->setColumn($col_duracion_cobrable, $col_duracion_cobrable, 1.2 * $PrmExcelCobro->getTamano('duracion_cobrable', 'Listado de trabajos'));
	$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, 1.5 * $PrmExcelCobro->getTamano('tarifa_hh', 'Listado de trabajos'));

	$ancho_columna_valor_trabajado = $PrmExcelCobro->getTamano('valor_trabajado', 'Listado de trabajos');
	$ws->setColumn(
		$col_valor_trabajo,
		$col_valor_trabajo,
		$ancho_columna_valor_trabajado ? $ancho_columna_valor_trabajado : 14
	);
	$ws->setColumn(
		$col_valor_trabajo_flat_fee,
		$col_valor_trabajo_flat_fee,
		$ancho_columna_valor_trabajado ? $ancho_columna_valor_trabajado : 14
	);

	$ws->setColumn($col_id_abogado, $col_id_abogado, 0, 0 ,1);

	// Agregar la imagen del logo
	$altura_logo = UtilesApp::AlturaLogoExcel();
	if ($altura_logo) {
		$ws->setRow(0, .8 * $altura_logo);
		$ws->insertBitmap(0, 0, UtilesApp::GetConf($sesion, 'LogoExcel'), 0, 0, .8, .8);
	}

	// Es necesario setear estos valores para que la emisión masiva funcione.
	$primer_asunto = true;
	$creado = false;
	$filas = 0;
	$filas_totales_asuntos = array();

	$cliente = new Cliente($sesion);
	if (method_exists('Conf', 'GetConf') && Conf::read('CodigoSecundario')) {
		$codigo_cliente = $cliente->CodigoACodigoSecundario($cobro->fields['codigo_cliente']);
	} else {
		$codigo_cliente = $cobro->fields['codigo_cliente'];
	}

	if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
		$sumante = 5;
	} else {
		$sumante = 3;
	}
	$ws->write($filas, $col_descripcion + $sumante, $PrmExcelCobro->getGlosa('minuta', 'Encabezado', $lang) . ' ' . $cobro->fields['id_cobro'], $CellFormat->get('encabezado'));
	$filas++;
	$ws->write($filas, $col_descripcion + $sumante, 'Código cliente: ' . $codigo_cliente, $CellFormat->get('encabezado'));
	$filas++;

	// Escribir el encabezado con los datos del cliente
	$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('cliente', 'Encabezado', $lang), $CellFormat->get('encabezado_datos_cliente'));
	$ws->write($filas, $col_descripcion, $contrato->fields['codigo_cliente'] . ' - ' . str_replace('\\', '', $contrato->fields['factura_razon_social']), $CellFormat->get('encabezado_datos_cliente'));
	$ws->mergeCells($filas, $col_descripcion, $filas, $col_valor_trabajo_flat_fee);
	++$filas;

	$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('rut', 'Encabezado', $lang), $CellFormat->get('encabezado_datos_cliente'));
	$ws->write($filas, $col_descripcion, $contrato->fields['rut'], $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_descripcion, $filas, $col_valor_trabajo_flat_fee);
	++$filas;


	$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('direccion', 'Encabezado', $lang), $CellFormat->get('encabezado_datos_cliente'));
	$direccion = str_replace("\r", " ", $contrato->fields['direccion_contacto']);
	$direccion = str_replace("\n", " ", $direccion);
	$ws->write($filas, $col_descripcion, $direccion, $CellFormat->get('encabezado_datos_cliente'));
	$ws->mergeCells($filas, $col_descripcion, $filas, $col_valor_trabajo_flat_fee);
	++$filas;

	$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('contacto', 'Encabezado', $lang), $CellFormat->get('encabezado_datos_cliente'));
	$ws->write($filas, $col_descripcion, $contacto->fields['contacto'], $CellFormat->get('encabezado_datos_cliente'));
	$ws->mergeCells($filas, $col_descripcion, $filas, $col_valor_trabajo_flat_fee);
	++$filas;

	$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('telefono', 'Encabezado', $lang), $CellFormat->get('encabezado_datos_cliente'));
	$ws->write($filas, $col_descripcion, $contacto->fields['fono_contacto'], $CellFormat->get('encabezado_datos_cliente'));
	$ws->mergeCells($filas, $col_descripcion, $filas, $col_valor_trabajo_flat_fee);
	$filas += 2;

	if ($opc_ver_resumen_cobro || $borradores) {
		$ws->write($filas++, $col_fecha, $PrmExcelCobro->getGlosa('titulo', 'Resumen', $lang), $CellFormat->get('encabezado_underline'));

		// Esto es para poder escribir la segunda columna más fácilmente.
		$filas2 = $filas;

		$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('fecha', 'Resumen', $lang), $CellFormat->get('encabezado'));

		$ws->write($filas++, $col_descripcion, ($trabajo->fields['fecha_emision'] == '0000-00-00' or $trabajo->fields['fecha_emision'] == '') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($trabajo->fields['fecha_emision'], $idioma->fields['formato_fecha']), $CellFormat->get('encabezado'));

		$fecha_primer_trabajo = $cobro->fields['fecha_ini'];
		$fecha_ultimo_trabajo = $cobro->fields['fecha_fin'];
		$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
		$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

		if ($fecha_primer_trabajo && $fecha_primer_trabajo != '0000-00-00') {
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('fecha_desde', 'Resumen', $lang), $CellFormat->get('encabezado'));
			$ws->write($filas++, $col_descripcion, Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $CellFormat->get('encabezado'));
		}

		$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('fecha_hasta', 'Resumen', $lang), $CellFormat->get('encabezado'));
		$ws->write($filas++, $col_descripcion, Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $CellFormat->get('encabezado'));

		// Si hay una factura asociada mostramos su número.
		if ($cobro->fields['documento']) {
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('factura', 'Resumen', $lang), $CellFormat->get('encabezado'));
			$ws->write($filas++, $col_descripcion, $cobro->fields['documento'], $CellFormat->get('encabezado'));
		}

		$ws->write($filas, $col_fecha, __('Tarifario Base:'), $CellFormat->get('encabezado'));

		// Consulta glosa de tarifa
		$query = "SELECT glosa_tarifa FROM tarifa WHERE tarifa_defecto = 1 ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($glosa_tarifa) = mysql_fetch_array($resp);

		$ws->write($filas++, $col_descripcion, $glosa_tarifa, $CellFormat->get('encabezado'));
		$ws->write($filas, $col_fecha, __('Pacto:'), $CellFormat->get('encabezado'));
		//$ws->write($filas++, $col_abogado, __($cobro->fields['forma_cobro']), $CellFormat->get('encabezado'));
		if (($cobro->fields['forma_cobro'] == 'PROPORCIONAL') || ($cobro->fields['forma_cobro'] == 'RETAINER')) {
			$mje_detalle_forma_cobro = $simbolo_moneda_opc_moneda_monto . " " . number_format($cobro->fields['monto_contrato'], $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales'], ',', '.') . " por " . $cobro->fields['retainer_horas'] . " horas exceso " . $glosa_tarifa;
		} else if ($cobro->fields['forma_cobro'] == 'FLAT FEE') {
			$mje_detalle_forma_cobro = $simbolo_moneda_opc_moneda_monto . " " . number_format($cobro->fields['monto_contrato'], $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales'], ',', '.');
		}
		$ws->write($filas++, $col_descripcion, __($cobro->fields['forma_cobro']) . " " . $mje_detalle_forma_cobro, $CellFormat->get('encabezado'));

		if ($trabajo->fields['forma_cobro'] == 'PROPORCIONAL' || $trabajo->fields['forma_cobro'] == 'RETAINER') {
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('horas_retainer', 'Resumen', $lang), $CellFormat->get('encabezado'));
			$ws->write($filas++, $col_descripcion, $cobro->fields['retainer_horas'], $CellFormat->get('encabezado_derecha'));
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('monto_retainer', 'Resumen', $lang), $CellFormat->get('encabezado'));
			$ws->writeNumber($filas++, $col_descripcion, $cobro->fields['monto_contrato'], $CellFormat->get('moneda_monto'));
		}

		// Segunda columna
		$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_horas', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
		$horas_cobrables = floor($cobro->fields['total_minutos'] / 60);
		$minutos_cobrables = sprintf("%02d", $cobro->fields['total_minutos'] % 60);
		$ws->write($filas2++, $col_valor_trabajo_flat_fee, "$horas_cobrables:$minutos_cobrables", $CellFormat->get('encabezado_derecha'));

		$ws->write($filas2, $col_valor_trabajo, __('Honorarios por horas:'), $CellFormat->get('encabezado_derecha'));
		$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['monto_thh_estandar'], $CellFormat->get('moneda_encabezado'));

		$ws->write($filas2, $col_valor_trabajo, __('Honorarios por pacto:'), $CellFormat->get('encabezado_derecha'));
		$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['monto_subtotal'], $CellFormat->get('moneda_encabezado'));

		if ($cobro->fields['id_moneda'] != $cobro->fields['opc_moneda_total']) {
			$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('equivalente', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
			$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1) . "*" . $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] . "/" . $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $CellFormat->get('moneda_resumen'));
		}
		if ($cobro->fields['descuento'] > 0) {
			$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('descuento', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
			$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['descuento'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $CellFormat->get('moneda_resumen'));
			$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('subtotal', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
			$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=$col_formula_valor_trabajo_flat_fee" . ($filas2 - 2) . "-$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
		}
		if ($cobro->fields['porcentaje_impuesto'] > 0 && ((method_exists('Conf', 'GetConf') && Conf::read('ValorImpuesto') > 0) || (method_exists('Conf', 'ValorImpuesto') && Conf::ValorImpuesto() > 0))) {
			if ($cobro->fields['porcentaje_impuesto_gastos'] > 0 && ((method_exists('Conf', 'GetConf') && Conf::read('ValorImpuestoGastos') > 0) || (method_exists('Conf', 'ValorImpuestoGastos') && Conf::ValorImpuestoGastos() > 0))) {
				if ($opc_ver_gastos && $cobro->fields['subtotal_gastos'] > 0) {
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('gastos', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['subtotal_gastos'], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('impuesto', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));

					/*$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2).'*0.'.$cobro->fields['porcentaje_impuesto']."+$col_formula_valor_trabajo".($filas2-1).'*0.'.$cobro->fields['porcentaje_impuesto_gastos'], $CellFormat->get('moneda_resumen'));*/
					$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, false);
					//ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2).'*0.'.$cobro->fields['porcentaje_impuesto']."+$col_formula_valor_trabajo".($filas2-1).'*0.'.$cobro->fields['porcentaje_impuesto_gastos'], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2++, $col_valor_trabajo_flat_fee, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=SUM($col_formula_valor_trabajo_flat_fee" . ($filas2 - 3) . ":$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
				} else {
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('impuesto', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));

					$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, false);
					//$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1).'*0.'.(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ValorImpuesto'):Conf::ValorImpuesto()), $CellFormat->get('moneda_resumen'));;
					$ws->write($filas2++, $col_valor_trabajo_flat_fee, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=$col_formula_valor_trabajo_flat_fee" . ($filas2 - 2) . " + $col_formula_valor_trabajo_flat_fee" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
				}
			} else {
				$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('impuesto', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
				$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, false);
				//$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1).'*0.'.(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ValorImpuesto'):Conf::ValorImpuesto()), $CellFormat->get('moneda_resumen'));
				$ws->write($filas2++, $col_valor_trabajo_flat_fee, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
				if ($opc_ver_gastos && $cobro->fields['monto_gastos'] > 0) {
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('gastos', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=SUM($col_formula_valor_trabajo_flat_fee" . ($filas2 - 3) . ":$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
				} else {
					$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=$col_formula_valor_trabajo_flat_fee" . ($filas2 - 2) . " + $col_formula_valor_trabajo_flat_fee" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
				}
			}
		} else {
			if ($opc_ver_gastos) {
				$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('gastos', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
				$ws->writeNumber($filas2++, $col_valor_trabajo_flat_fee, $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen'));
				$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
				$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=SUM($col_formula_valor_trabajo_flat_fee" . ($filas2 - 2) . ":$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
			} else {
				$ws->write($filas2, $col_valor_trabajo, $PrmExcelCobro->getGlosa('total_cobro', 'Resumen', $lang), $CellFormat->get('encabezado_derecha'));
				$ws->writeFormula($filas2++, $col_valor_trabajo_flat_fee, "=$col_formula_valor_trabajo_flat_fee" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
			}
		}

		// Para seguir imprimiendo datos hay definir en que linea será
		$filas = max($filas, $filas2);
		++$filas;
	}

	$query_num_usuarios = "SELECT DISTINCT id_usuario FROM trabajo WHERE id_cobro=" . $cobro->fields['id_cobro'];
	$resp_num_usuarios = mysql_query($query_num_usuarios, $sesion->dbh) or Utiles::errorSQL($query_num_usuarios, __FILE__, __LINE__, $sesion->dbh);
	$num_usuarios = mysql_num_rows($resp_num_usuarios);

	// Dejar espacio para el resumen profesional si es necesario.
	if (($opc_ver_profesional && $mostrar_resumen_de_profesionales) || $cobro->fields['opc_ver_profesional']) {
		$fila_inicio_resumen_profesional = $filas - 1;
		if ($num_usuarios > 0)
			$filas += $num_usuarios + 7;
		else
			$filas += 3;
	}

	$cont_asuntos = 0;

	// Bucle sobre todos los asuntos de este cobro
	$cobro_tiene_trabajos = false;
	$lineas_total_asunto = [];
	while ($cobro->asuntos[$cont_asuntos]) {

		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($cobro->asuntos[$cont_asuntos]);
		$codigo_asunto_secundario = $asunto->CodigoACodigoSecundario($cobro->asuntos[$cont_asuntos]);

		$where_trabajos = " 1 ";
		if ($opc_ver_asuntos_separados) {
			$where_trabajos .= " AND trabajo.codigo_asunto ='" . $asunto->fields['codigo_asunto'] . "' ";
		}
		if (!$opc_ver_cobrable) {
			$where_trabajos .= " AND trabajo.visible = 1 ";
		}
		if (!$opc_ver_horas_trabajadas) {
			$where_trabajos .= " AND trabajo.duracion_cobrada != '00:00:00' ";
		}

		$query_cont_trabajos = "SELECT COUNT(*) FROM trabajo WHERE $where_trabajos AND id_cobro='" . $cobro->fields['id_cobro'] . "' AND id_tramite = 0";
		$resp_cont_trabajos = mysql_query($query_cont_trabajos, $sesion->dbh) or Utiles::errorSQL($query_cont_trabajos, __FILE__, __LINE__, $sesion->dbh);
		list($cont_trabajos) = mysql_fetch_array($resp_cont_trabajos);

		$where_tramites = " 1 ";
		if ($opc_ver_asuntos_separados) {
			$where_tramites .= " AND tramite.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "' ";
		}

		$query_cont_tramites = "SELECT COUNT(*) FROM tramite WHERE $where_tramites AND id_cobro=" . $cobro->fields['id_cobro'];
		$resp_cont_tramites = mysql_query($query_cont_tramites, $sesion->dbh) or Utiles::errorSQL($query_cont_tramites, __FILE__, __LINE__, $sesion->dbh);
		list($cont_tramites) = mysql_fetch_array($resp_cont_tramites);

		// Si el asunto tiene trabajos y/o trámites imprime su resumen
		if (($cont_trabajos + $cont_tramites) > 0) {
			$cobro_tiene_trabajos = true;
			if ($opc_ver_asuntos_separados) {
				// Indicar en una linea que los asuntos se muestran por separado y lluego
				// esconder la columna para que no ensucia la vista.
				$ws->write($filas, $col_fecha, 'asuntos_separado', $CellFormat->get('encabezado'));
				$ws->write(++$filas, $col_abogado, $asunto->fields['codigo_asunto'], $CellFormat->get('encabezado'));
				$ws->setRow($filas - 1, 0, 0, 1);
				$ws->write($filas, $col_fecha, __('Asunto') . ': ', $CellFormat->get('encabezado'));
				if (Conf::read('CodigoSecundario')) {
					$ws->write($filas, $col_descripcion, $asunto->fields['codigo_asunto_secundario'] . ' - ' . $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
				} else {
					$ws->write($filas, $col_descripcion, $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
				}
				$filas += 2;
			}

			// Si existen trabajos imprime la tabla
			if ($cont_trabajos > 0) {
				if ($cobro->fields['forma_cobro'] == "TASA") {
					$dato_tarifa_hh = "tarifa_hh";
				} else {
					$dato_tarifa_hh = "tarifa_hh_estandar";
				}
				// Buscar todos los trabajos de este asunto/cobro
				$query_trabajos = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
														trabajo.id_cobro,
														trabajo.id_trabajo,
														trabajo.codigo_asunto,
														trabajo.id_usuario,
														trabajo.cobrable,
														trabajo.monto_cobrado,
														trabajo.fecha,
														trabajo.descripcion,
														trabajo.solicitante,
														prm_moneda.simbolo AS simbolo,
														asunto.codigo_cliente AS codigo_cliente,
														asunto.id_asunto AS id,
														trabajo.fecha_cobro AS fecha_cobro_orden,
														IF( trabajo.cobrable = 1, 'SI', 'NO') AS glosa_cobrable,
														trabajo.visible,
														username AS usr_nombre,
														username AS username,
														DATE_FORMAT(duracion, '%H:%i') AS duracion,
														DATE_FORMAT(duracion_cobrada, '%H:%i') AS duracion_cobrada,
														TIME_TO_SEC(duracion_cobrada) AS duracion_cobrada_decimal,
														DATE_FORMAT(duracion_retainer, '%H:%i') AS duracion_retainer,
														TIME_TO_SEC(duracion)/3600 AS duracion_horas,
														IF( trabajo.cobrable = 1, trabajo.$dato_tarifa_hh, '0') AS tarifa_hh,
														tarifa_hh as tarifa_hh_cliente,
														tarifa_hh_estandar,
														DATE_FORMAT(trabajo.fecha_cobro, '%e-%c-%x') AS fecha_cobro,
														asunto.codigo_asunto_secundario as codigo_asunto_secundario
													FROM trabajo
														JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
														LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
														JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
														LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
														LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
														LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
													WHERE $where_trabajos AND trabajo.id_tramite=0 AND trabajo.id_cobro=" . $cobro->fields['id_cobro'];

				$orden = "trabajo.fecha, trabajo.descripcion";
				$b1 = new Buscador($sesion, $query_trabajos, "Trabajo", $desde, '', $orden);
				$lista_trabajos = $b1->lista;

				// Encabezado de la tabla de trabajos

				$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('fecha', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));

				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_asunto, $PrmExcelCobro->getGlosa('asunto', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_abogado, $PrmExcelCobro->getGlosa('abogado', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));

				if ($opc_ver_horas_trabajadas) {
					$ws->write($filas, $col_duracion_trabajada, $PrmExcelCobro->getGlosa('duracion_trabajada', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));
				}
				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
					$ws->write($filas, $col_duracion_cobrable, __('Duración'), $CellFormat->get('titulo'));
					$ws->write($filas, $col_duracion_retainer, __('Duración Retainer'), $CellFormat->get('titulo'));
					$ws->write($filas, $col_tarificable_hh, __('Hr. Exceso'), $CellFormat->get('titulo'));
				} else {
					$ws->write($filas, $col_duracion_cobrable, $PrmExcelCobro->getGlosa('duracion_cobrable', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));
				}
				if ($opc_ver_cobrable) {
					$ws->write($filas, $col_es_cobrable, $PrmExcelCobro->getGlosa('cobrable', 'Listado de trabajos', $lang), $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, $PrmExcelCobro->getGlosa('tarifa_hh_rentabilidad', 'Listado de trabajos', $lang)), $CellFormat->get('titulo_centrado'));
				$ws->write($filas, $col_valor_trabajo, __('Valor según Tarifa'), $CellFormat->get('titulo_centrado'));
				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
					$ws->write($filas, $col_valor_trabajo_flat_fee, __('Valor Retainer'), $CellFormat->get('titulo_centrado'));
				} else if ($cobro->fields['forma_cobro'] == 'FLAT FEE') {
					$ws->write($filas, $col_valor_trabajo_flat_fee, __('Valor Flat'), $CellFormat->get('titulo_centrado'));
				} else {
					$ws->write($filas, $col_valor_trabajo_flat_fee, __('Valor Estándar'), $CellFormat->get('titulo_centrado'));
				}

				$ws->write($filas, $col_id_abogado, __('NO MODIFICAR ESTA COLUMNA'));
				if (!$primera_fila_primer_asunto) {
					$primera_fila_primer_asunto = $filas;
				}
				++$filas;
				$primera_fila_asunto = $filas + 1;
				$diferencia_proporcional = 0;
				$suma_total_inexacto = 0;
				$suma_total_exacto = 0;


				// Contenido de la tabla de trabajos
				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);

					$ws->write($filas, $col_fecha, Utiles::sql2date($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					if (!$opc_ver_asuntos_separados) {
						if (UtilesApp::GetConf($sesion, 'TipoCodigoAsunto') == 2) {
							$ws->write($filas, $col_asunto, substr($trabajo->fields['codigo_asunto_secundario'], -3), $CellFormat->get('descripcion'));
						} else {
							$ws->write($filas, $col_asunto, substr($trabajo->fields['codigo_asunto_secundario'], -4), $CellFormat->get('descripcion'));
						}
					}
					$ws->write($filas, $col_descripcion, str_replace("\r", '', stripslashes($trabajo->fields['descripcion'])), $CellFormat->get('descripcion'));
					// Se guarda el nombre en una variable porque se usa en el detalle profesional.
					$nombre = $trabajo->fields['username'];
					$ws->write($filas, $col_abogado, $nombre, $CellFormat->get('normal'));

					$duracion = $trabajo->fields['duracion'];
					list($h, $m) = explode(':', $duracion);
					$duracion = $h / 24 + $m / (24 * 60);
					if ($opc_ver_horas_trabajadas)
						$ws->writeNumber($filas, $col_duracion_trabajada, $duracion, $CellFormat->get('tiempo'));
					$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
					list($h, $m) = explode(':', $duracion_cobrada);
					if ($trabajo->fields['glosa_cobrable'] == 'SI')
						$duracion_cobrada = $h / 24 + $m / (24 * 60);
					else
						$duracion_cobrada = 0;

					$query_total_cobrable = "select if(sum(TIME_TO_SEC(duracion_cobrada)/3600)<=0,1,sum(TIME_TO_SEC(duracion_cobrada)/3600)) as duracion_total from trabajo where id_cobro = '" . $cobro->fields['id_cobro'] . "' and cobrable = 1";
					$resp_total_cobrable = mysql_query($query_total_cobrable, $sesion->dbh) or Utiles::errorSQL($query_total_cobrable, __FILE__, __LINE__, $sesion->dbh);
					list($xtotal_hh_cobrable) = mysql_fetch_array($resp_total_cobrable);
					if ($xtotal_hh_cobrable > 0) {
						$factor = $cobro->fields['retainer_horas'] / $xtotal_hh_cobrable;
					} else {
						$factor = 1;
					}
					if ($cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
						$duracion_retainer = $duracion_cobrada * $factor;
					} else {
						$duracion_retainer = $trabajo->fields['duracion_retainer'];
						list($h, $m, $s) = explode(':', $duracion_retainer);
						$duracion_retainer = $h / 24 + $m / (24 * 60) + $s / (24 * 60 * 60);
					}

					if ($duracion_retainer > $duracion_cobrada || $cobro->fields['forma_cobro'] == 'FLAT FEE')
						$duracion_retainer = $duracion_cobrada;
					$duracion_tarificable = max(($duracion_cobrada - $duracion_retainer), 0);

					$ws->writeNumber($filas, $col_duracion_cobrable, $duracion_cobrada, $CellFormat->get('tiempo'));
					if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
						$ws->writeNumber($filas, $col_duracion_retainer, $duracion_retainer, $CellFormat->get('tiempo'));
						$ws->writeNumber($filas, $col_tarificable_hh, $duracion_tarificable, $CellFormat->get('tiempo'));
					}

					if ($opc_ver_cobrable) {
						$ws->write($filas, $col_es_cobrable, $trabajo->fields['cobrable'] == 1 ? __("Sí") : __("No"), $CellFormat->get('normal'));
					}

					$tarifa_hh = $trabajo->fields['tarifa_hh'];
					$tarifahhcliente = $trabajo->fields['tarifa_hh_cliente'];
					$tarifahhestandar = $trabajo->fields['tarifa_hh_estandar'];


					if ($cobro->fields['monto_thh_estandar'] > 0) {
						$factor_valor_flat = number_format($cobro->fields['monto_trabajos'] / $cobro->fields['monto_thh_estandar'], 6, '.', '');
					} else if ($cobro->fields['monto_thh'] > 0) {
						$factor_valor_flat = number_format($cobro->fields['monto_trabajos'] / $cobro->fields['monto_thh'], 6, '.', '');
					} else {
						$factor_valor_flat = number_format(60 * $cobro->fields['monto_trabajos'] / $cobro->fields['total_minutos'], 6, '.', '');
					}
					if ($cobro->fields['retainer_horas'] > 0) {
						$factor_valor_retainer = number_format(($cobro->fields['monto_contrato'] * ($cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'])) / (min($cobro->fields['total_minutos'] % 60, $cobro->fields['retainer_horas'])), 6, '.', '');
					} else {
						$factor_valor_retainer = 1;
					}
					if ($cobro->fields['monto_thh_estandar']) {
						$factor_valor_hh = number_format($cobro->fields['monto_thh_estandar'] / $cobro->fields['monto_thh'], 6, '.', '');
					} else {
						$factor_valor_hh = 1;
					}


					if ($cobro->fields['retainer_horas'] > 0) {
						$factor_valor_retainer = number_format(($cobro->fields['monto_contrato'] * ($cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'])) / (min($cobro->fields['total_minutos'] / 60, $cobro->fields['retainer_horas'])), 6, '.', '');
					} else {
						$factor_valor_retainer = 1;
					}

					$ws->writeNumber($filas, $col_tarifa_hh, $tarifa_hh, $CellFormat->get('moneda'));
					$ws->writeFormula($filas, $col_valor_trabajo, "=24*$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda'));
					if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
						$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=24*$factor_valor_retainer*$col_formula_duracion_retainer" . ($filas + 1) . "+24*$col_formula_tarificable_hh" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda'));
					} else if ($cobro->fields['forma_cobro'] == 'FLAT FEE') {
						if ($cobro->fields['monto_thh_estandar'] > 0 || $cobro->fields['monto_thh'] > 0) {

							$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=24*$factor_valor_flat*$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda'));
						} elseif ($cobro->fields['monto_thh'] > 0) {
							$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=24*$factor_valor_flat*$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda'));
						} else {
							$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=24*$factor_valor_flat*$col_formula_duracion_cobrable" . ($filas + 1), $CellFormat->get('moneda'));
						}
					} else {
						$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=24*$factor_valor_hh*$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda'));
					}
					$ws->write($filas, $col_id_abogado, $trabajo->fields['id_usuario'], $CellFormat->get('normal'));

					// Si hay que mostrar el detalle profesional guardamos una lista con los profesionales que trabajaron en este asunto.
					if ($opc_ver_profesional || $cobro->fields['opc_ver_profesional']) {
						if ($trabajo->fields['cobrable'] > 0 && !isset($detalle_profesional[$trabajo->fields['id_usuario']])) {
							$detalle_profesional[$trabajo->fields['id_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
							$detalle_profesional[$trabajo->fields['id_usuario']]['nombre'] = $nombre;
						}
					}
					++$filas;
				}

				// Hay que eliminar la variable $diferencia_proporcional para que la proxima vez parte con zero
				$diferencia_proporcional = 0;

				// Guardar ultima linea para tener esta información por el resumen profesional
				$ultima_fila_ultimo_asunto = $filas - 1;

				//$lineas_total_asunto["'".($filas+1)."'"]  = $filas+1;
				// Totales de la tabla de trámites
				$ws->write($filas, $col_fecha, __('Total'), $CellFormat->get('total'));
				$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
				$ws->write($filas, $col_abogado, '', $CellFormat->get('total'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_asunto, '', $CellFormat->get('total'));
				}
				if ($opc_ver_horas_trabajadas) {
					$ws->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$primera_fila_asunto:$col_formula_duracion_trabajada$filas)", $CellFormat->get('tiempo_total'));
				}
				$ws->writeFormula($filas, $col_duracion_cobrable, "=SUM($col_formula_duracion_cobrable$primera_fila_asunto:$col_formula_duracion_cobrable$filas)", $CellFormat->get('tiempo_total'));
				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
					$ws->writeFormula($filas, $col_duracion_retainer, "=SUM($col_formula_duracion_retainer$primera_fila_asunto:$col_formula_duracion_retainer$filas)", $CellFormat->get('tiempo_total'));
					$ws->writeFormula($filas, $col_tarificable_hh, "=SUM($col_formula_tarificable_hh$primera_fila_asunto:$col_formula_tarificable_hh$filas)", $CellFormat->get('tiempo_total'));
				}
				if ($opc_ver_cobrable) {
					$ws->write($filas, $col_es_cobrable, '', $CellFormat->get('total'));
				}
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
				$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo$primera_fila_asunto:$col_formula_valor_trabajo$filas)", $CellFormat->get('moneda_total'));
				$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=SUM($col_formula_valor_trabajo_flat_fee$primera_fila_asunto:$col_formula_valor_trabajo_flat_fee$filas)", $CellFormat->get('moneda_total'));
				$filas += 2;
			}


			if ($cont_tramites > 0) {
				// Buscar todos los trámites de este cobro/asunto
				$where_tramites = " 1 ";
				if ($opc_ver_asuntos_separados) {
					$where_tramites .= " AND tramite.codigo_asunto ='" . $asunto->fields['codigo_asunto'] . "' ";
				}

				$query_tramites = "SELECT SQL_CALC_FOUND_ROWS *,
																		tramite.id_tramite,
																		tramite.fecha,
																		glosa_tramite,
																		tramite.id_moneda_tramite,
																		username as usr_nombre,
																		tramite.descripcion,
																		tramite.tarifa_tramite as tarifa,
																		tramite.duracion,
																		tramite.id_cobro,
																		tramite.codigo_asunto,
																		cliente.glosa_cliente,
																		prm_moneda.simbolo AS simbolo,
																		cobro.id_moneda AS id_moneda_asunto,
																		asunto.id_asunto AS id,
																		asunto.codigo_asunto_secundario as codigo_asunto_secundario,
																		cobro.estado AS estado_cobro,
																		DATE_FORMAT(duracion, '%H:%i') AS duracion,
																		TIME_TO_SEC(duracion)/3600 AS duracion_horas,
																		DATE_FORMAT(cobro.fecha_cobro, '%e-%c-%x') AS fecha_cobro
																		FROM tramite
																		JOIN asunto ON tramite.codigo_asunto=asunto.codigo_asunto
																		JOIN contrato ON asunto.id_contrato=contrato.id_contrato
																		JOIN prm_moneda ON contrato.id_moneda_tramite=prm_moneda.id_moneda
																		JOIN usuario ON tramite.id_usuario=usuario.id_usuario
																		JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
																		LEFT JOIN cliente ON asunto.codigo_cliente=cliente.codigo_cliente
																		LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
																		WHERE $where_tramites AND tramite.id_cobro='" . $cobro->fields['id_cobro'] . "'
																		ORDER BY fecha ASC";

				$lista_tramites = new ListaTramites($sesion, '', $query_tramites);

				// Encabezado de la tabla de trámites
				$filas++;
				$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('fecha', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_abogado, $PrmExcelCobro->getGlosa('abogado', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_abogado + 1, $PrmExcelCobro->getGlosa('asunto', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				}

				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				if ($opc_ver_horas_trabajadas) {
					$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_tarificable_hh, $PrmExcelCobro->getGlosa('duracion', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				//if($opc_ver_cobrable)
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_valor_trabajo, $PrmExcelCobro->getGlosa('valor', 'Listado de trámites', $lang), $CellFormat->get('titulo'));
				++$filas;
				$fila_inicio_tramites = $filas + 1;

				// Contenido de la tabla de trámites
				for ($i = 0; $i < $lista_tramites->num; $i++) {
					$tramite = $lista_tramites->Get($i);
					list($h, $m, $s) = explode(':', $tramite->fields['duracion']);
					if ($h + $m > 0) {
						if ($h > 9) {
							$duracion = $h . ':' . $m;
						} else {
							$duracion = substr($h, 1, 1) . ':' . $m;
						}
					} else {
						$duracion = '-';
					}

					$ws->write($filas, $col_fecha, Utiles::sql2fecha($tramite->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_abogado, $tramite->fields['username'], $CellFormat->get('normal'));

					if (!$opc_ver_asuntos_separados) {
						$ws->write($filas, $col_abogado + 1, substr($tramite->fields['codigo_asunto'], -4), $CellFormat->get('descripcion'));
					}

					$ws->write($filas, $col_descripcion, $tramite->fields['glosa_tramite'] . ' - ' . $tramite->fields['descripcion'], $CellFormat->get('descripcion'));
					if ($opc_ver_horas_trabajadas) {
						$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('tiempo'));
					}
					$ws->write($filas, $col_tarificable_hh, $duracion, $CellFormat->get('tiempo'));
					//if($opc_ver_cobrable)
					if ($tramite->fields['id_moneda_tramite'] == $opc_moneda_total) {
						$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('normal'));
					}	else {
						$ws->writeNumber($filas, $col_tarifa_hh, $tramite->fields['tarifa'], $CellFormat->get('moneda'));
					}
					$ws->writeNumber($filas, $col_valor_trabajo, ($tramite->fields['tarifa'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']) / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $CellFormat->get('moneda_gastos'));

					++$filas;
				}

				// Totales de los trámites:
				$ws->write($filas, $col_fecha, __('Total'), $CellFormat->get('total'));
				$ws->write($filas, $col_abogado, '', $CellFormat->get('total'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_abogado + 1, '', $CellFormat->get('total'));
				}

				$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
				if ($opc_ver_horas_trabajadas && $col_duracion_trabajada != $col_descripcion + 1) {
					$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('tiempo_total'));
				}

				$col_formula_tem = Utiles::NumToColumnaExcel($col_tarificable_hh);
				$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_tem$fila_inicio_tramites:$col_formula_tem$filas)", $CellFormat->get('tiempo_total'));
				if ($col_tarificable_hh != $col_descripcion + 1) $ws->write($filas, $col_tarificable_hh, $tiempo_final, $CellFormat->get('tiempo_total'));
				//if($opc_ver_cobrable)
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_valor_trabajo);
				$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_temp$fila_inicio_tramites:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));

				$filas += 2;
			}
		}
		// Si se ven los asuntos por separado avanza al proximo
		// Si no salga del while
		if ($opc_ver_asuntos_separados) {
			$cont_asuntos++;
			//FFF guardo la fila de los subtotales, la voy a necesitar al final de la planilla
			$lineas_total_asunto[$asunto->fields['glosa_asunto']] = array($filas - 1, $cont_trabajos);
			//$ws->write($filas - 1, 15, ' SUBTOTAL', $CellFormat->get('resumen_rentabilidad'));
		} else {
			break;
		}
	}

	if (count($cobro->asuntos) == 0 || !$cobro_tiene_trabajos) {
		$ws->write($filas++, $col_descripcion, 'No existen trabajos asociados a este cobro.', $CellFormat->get('instrucciones10'));

		$filas += 2;

	} else {
		// Construir formula para sumar totales de asuntos ...
		$arraytemporal = array();
		$formula_total_hh = array();
		$formula_total_ff = array();
		foreach ($lineas_total_asunto as $label => $numfila) {
			if ($numfila[1] > 0) {
				$formula_total_hh[] = $col_formula_valor_trabajo . $numfila[0];
				$formula_total_ff[] = $col_formula_valor_trabajo_flat_fee . $numfila[0];
			}
		}

		if ($cobro->fields['forma_cobro'] == "TASA") {
			$arraytemporal = $formula_total_hh;
			$formula_total_hh = $formula_total_ff;
			$formula_total_ff = $arraytemporal;
		}

		$filas += 2;
		$ws->mergeCells($filas, $col_tarifa_hh, $filas, $col_valor_trabajo);
		$ws->write($filas, $col_tarifa_hh, __('TOTAL POR PACTO:'), $CellFormat->get('resumen_rentabilidad'));
		$ws->write($filas, $col_valor_trabajo, '', $CellFormat->get('resumen_rentabilidad'));
		$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, '=' . implode('+', $formula_total_ff), $CellFormat->get('rentabilidad_moneda_total'));
		$filas++;
		$ws->mergeCells($filas, $col_tarifa_hh, $filas, $col_valor_trabajo);
		$ws->write($filas, $col_tarifa_hh, __('TOTAL POR HORAS:'), $CellFormat->get('resumen_rentabilidad'));
		$ws->write($filas, $col_valor_trabajo, '', $CellFormat->get('resumen_rentabilidad'));
		$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, '=' . implode('+', $formula_total_hh), $CellFormat->get('rentabilidad_moneda_total'));
		$filas++;
		$ws->mergeCells($filas, $col_tarifa_hh, $filas, $col_valor_trabajo);
		$ws->write($filas, $col_tarifa_hh, __('Write off / Mark up:'), $CellFormat->get('resumen_rentabilidad'));
		$ws->write($filas, $col_valor_trabajo, '', $CellFormat->get('resumen_rentabilidad'));
		$ws->writeFormula($filas, $col_valor_trabajo_flat_fee, "=100*($col_formula_valor_trabajo_flat_fee" . ($filas - 1) . "/$col_formula_valor_trabajo_flat_fee$filas)-100", $CellFormat->get('porcentaje_rentabilidad'));

		$filas += 2;
	}
	/********PRINT DEBUG OUTPUT HERE************/
	if (($opc_ver_profesional || $cobro->fields['opc_ver_profesional']) && is_array($detalle_profesional)) {
		// Si el resumen va al principio cambiar el índice de las filas.
		if ($mostrar_resumen_de_profesionales) {
			$filas2 = $filas;
			$filas = $fila_inicio_resumen_profesional;
		}

		// Escribir las condiciones (ocultas) para poder usar DSUM en las fórmulas
		$filas += 2;
		$contador = 0;
		foreach ($detalle_profesional as $id => $data) {
			$ws->write($filas, $col_fecha + $contador, __('NO MODIFICAR ESTA COLUMNA'));
			$ws->write($filas + 1, $col_fecha + $contador, "$id");
			++$contador;
		}

		// Con esto se ocultan las filas con los id de los abogados.
		$ws->setRow($filas, 0, 0, 1);
		$ws->setRow($filas + 1, 0, 0, 1);

		// Encabezado
		$filas += 2;
		$ws->write($filas++, $col_descripcion, $PrmExcelCobro->getGlosa('titulo', 'Detalle profesional', $lang), $CellFormat->get('encabezado'));
		$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('nombre', 'Detalle profesional', $lang), $CellFormat->get('titulo'));
		if ($opc_ver_horas_trabajadas) {
			$ws->write($filas, $col_duracion_trabajada, $PrmExcelCobro->getGlosa('horas_trabajadas', 'Detalle profesional', $lang), $CellFormat->get('titulo'));
			$ws->write($filas, $col_duracion_cobrable, $PrmExcelCobro->getGlosa('horas_cobrables', 'Detalle profesional', $lang), $CellFormat->get('titulo'));
		} else
			$ws->write($filas, $col_duracion_cobrable, $PrmExcelCobro->getGlosa('horas_trabajadas', 'Detalle profesional', $lang), $CellFormat->get('titulo'));
		if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
			$ws->write($filas, $col_duracion_retainer, __('Hr. Retainer'), $CellFormat->get('titulo'));
			$ws->write($filas, $col_tarificable_hh, __('Hr. Exceso'), $CellFormat->get('titulo'));
		}
		++$filas;

		// Para las fórmulas en los totales
		$fila_inicio_detalle_profesional = $filas + 1;

		// Rellenar la tabla visible
		// Se basa en la fórmula de excel DSUM(datos; columna a sumar; condiciones)
		// Para usar esta formula hay que definir el tamaño de la matriz en cual se encuentran los datos
		$contador = 0;
		if ($opc_ver_horas_trabajadas) {
			$inicio_datos = "$col_formula_duracion_trabajada" . ($primera_fila_primer_asunto + 1);
		} else {
			$inicio_datos = "$col_formula_duracion_cobrable" . ($primera_fila_primer_asunto + 1);
		}
		$fin_datos = "$col_formula_id_abogado" . ($ultima_fila_ultimo_asunto + 1);
		if (is_array($detalle_profesional)) {
			foreach ($detalle_profesional as $id => $data) {
				if (UtilesApp::GetConf($sesion, 'GuardarTarifaAlIngresoDeHora')) {
					$query_tarifa = "SELECT
													SUM( ( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) * tarifa_hh ) / SUM( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) as tarifa
													FROM trabajo
													WHERE id_cobro = '" . $cobro->fields['id_cobro'] . "'
													AND id_usuario = '$id'
													AND cobrable = 1";
					$resp_tarifa = mysql_query($query_tarifa, $sesion->dbh) or Utiles::errorSQL($query_tarifa, __FILE__, __LINE__, $sesion->dbh);
					list($data['tarifa']) = mysql_fetch_array($resp_tarifa);
				}
				$ws->write($filas, $col_descripcion, $data['nombre'], $CellFormat->get('normal'));
				$col1 = Utiles::NumToColumnaExcel($col_fecha + $contador) . ($fila_inicio_detalle_profesional - 4);
				$col2 = Utiles::NumToColumnaExcel($col_fecha + $contador) . ($fila_inicio_detalle_profesional - 3);
				$duracion_trabajada = $PrmExcelCobro->getGlosa('duracion_trabajada', 'Listado de trabajos', $lang);
				$duracion_cobrable = $PrmExcelCobro->getGlosa('duracion_cobrable', 'Listado de trabajos', $lang);
				if ($opc_ver_horas_trabajadas) {
					$ws->writeFormula(
						$filas,
						$col_duracion_trabajada,
						"=DSUM({$inicio_datos}:{$fin_datos};\"{$duracion_trabajada}\";{$col1}:{$col2})", $CellFormat->get('tiempo')
					);
				}
				$ws->writeFormula(
					$filas,
					$col_duracion_cobrable,
					"=DSUM({$inicio_datos}:{$fin_datos}; \"{$duracion_cobrable}\"; {$col1}:{$col2})",
					$CellFormat->get('tiempo')
				);

				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
					$ws->writeFormula(
						$filas,
						$col_duracion_retainer,
						"=DSUM({$inicio_datos}:{$fin_datos};\"" . __('Duración Retainer') . "\";{$col1}:{$col2})", $CellFormat->get('tiempo')
					);
					$ws->writeFormula(
						$filas,
						$col_tarificable_hh,
						"=DSUM({$inicio_datos}:{$fin_datos};\"" . __('Hr. Exceso') . "\";{$col1}:{$col2})", $CellFormat->get('tiempo')
					);
				}

				++$filas;
				++$contador;
			}
		}

		// Fórmulas para los totales
		$ws->write($filas, $col_descripcion, __('Total'), $CellFormat->get('total'));
		if ($opc_ver_horas_trabajadas) {
			$ws->writeFormula(
				$filas,
				$col_duracion_trabajada,
				"=SUM({$col_formula_duracion_trabajada}{$fila_inicio_detalle_profesional}:{$col_formula_duracion_trabajada}{$filas})",
				$CellFormat->get('tiempo_total')
			);
		}
		$ws->writeFormula(
			$filas,
			$col_duracion_cobrable,
			"=SUM({$col_formula_duracion_cobrable}{$fila_inicio_detalle_profesional}:{$col_formula_duracion_cobrable}{$filas})",
			$CellFormat->get('tiempo_total')
		);
		if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
			$ws->writeFormula(
				$filas,
				$col_duracion_retainer,
				"=SUM({$col_formula_duracion_retainer}{$fila_inicio_detalle_profesional}:{$col_formula_duracion_retainer}{$filas})",
				$CellFormat->get('tiempo_total')
			);
			$ws->writeFormula(
				$filas,
				$col_tarificable_hh,
				"=SUM({$col_formula_tarificable_hh}{$fila_inicio_detalle_profesional}:{$col_formula_tarificable_hh}{$filas})",
				$CellFormat->get('tiempo_total')
			);
		}

		// Si el resumen va al principio cambiar el índice de las filas.
		if ($mostrar_resumen_de_profesionales) {
			$filas = $filas2;
		}
	}
	// Borrar la variable primera_fila_primer asunto para que se define de nuevo en el siguiente cobro
	unset($primera_fila_primer_asunto);

	if ($cont_gastos_cobro > 0 && $cobro->fields['opc_ver_gastos']) {
		// Encabezado de la tabla de gastos
		$filas++;
		if (UtilesApp::GetConf($sesion, 'FacturaAsociada')) {
			if (UtilesApp::GetConf($sesion, 'PrmGastos')
				&& $cobro->fields['opc_ver_concepto_gastos']
				&& !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))
			) {
				$ws->write($filas++, $col_descripcion - 3, $PrmExcelCobro->getGlosa('titulo', 'Listado de gastos', $lang), $CellFormat->get('encabezado'));
				$ws->write($filas, $col_descripcion - 3, $PrmExcelCobro->getGlosa('fecha', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion - 2, __('Concepto'), $CellFormat->get('titulo'));
				$ws->mergeCells($filas, $col_descripcion - 2, $filas, $col_descripcion - 1);
				$ws->write($filas, $col_descripcion - 1, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 1, __('Documento Asociado'), $CellFormat->get('titulo'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

				$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
				$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 4, $PrmExcelCobro->getGlosa('monto', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
			} else {
				$ws->write($filas++, $col_descripcion - 1, $PrmExcelCobro->getGlosa('titulo', 'Listado de gastos', $lang), $CellFormat->get('encabezado'));
				$ws->write($filas, $col_descripcion - 1, $PrmExcelCobro->getGlosa('fecha', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 1, __('Tipo Documento'), $CellFormat->get('titulo'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

				$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
				$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 4, $PrmExcelCobro->getGlosa('monto', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
			}
		} else {
			if (UtilesApp::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				$ws->write($filas++, $col_descripcion - 3, $PrmExcelCobro->getGlosa('titulo', 'Listado de gastos', $lang), $CellFormat->get('encabezado'));
				$ws->write($filas, $col_descripcion - 3, $PrmExcelCobro->getGlosa('fecha', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion - 2, __('Concepto'), $CellFormat->get('titulo'));
				$ws->mergeCells($filas, $col_descripcion - 2, $filas, $col_descripcion - 1);
				$ws->write($filas, $col_descripcion - 1, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de gastos', $lang), $CellFormat->get('titulo'));

				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 2, $PrmExcelCobro->getGlosa('monto', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
			} else {
				$ws->write($filas++, $col_descripcion - 1, $PrmExcelCobro->getGlosa('titulo', 'Listado de gastos', $lang), $CellFormat->get('encabezado'));
				$ws->write($filas, $col_descripcion - 1, $PrmExcelCobro->getGlosa('fecha', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion, $PrmExcelCobro->getGlosa('descripcion', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_descripcion + 2, $PrmExcelCobro->getGlosa('monto', 'Listado de gastos', $lang), $CellFormat->get('titulo'));
			}
		}

		++$filas;
		$fila_inicio_gastos = $filas + 1;

		$_columnas_adicionales = '';
		$_joins = '';
		if (UtilesApp::GetConf($sesion, 'FacturaAsociada')) {
			$_columnas_adicionales .= ', ptda.glosa, codigo_factura_gasto';
			$_joins .= ' LEFT JOIN prm_tipo_documento_asociado ptda ON ( cta_corriente.id_tipo_documento_asociado = ptda.id_tipo_documento_asociado ) ';
		}

		if (UtilesApp::GetConf($sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
			$_columnas_adicionales .= ', pgg.glosa_gasto ';
			$_joins .= ' LEFT JOIN prm_glosa_gasto pgg ON ( cta_corriente.id_glosa_gasto = pgg.id_glosa_gasto ) ';
		}


		// Contenido de gastos
		$query = "SELECT SQL_CALC_FOUND_ROWS
											ingreso,
											egreso,
											monto_cobrable,
											fecha,
											id_moneda,
											descripcion
											$_columnas_adicionales
										FROM cta_corriente
											$_joins
										WHERE id_cobro='" . $cobro->fields['id_cobro'] . "'
										ORDER BY fecha ASC";

		$lista_gastos = new ListaGastos($sesion, '', $query);
		for ($i = 0; $i < $lista_gastos->num; $i++) {
			$gasto = $lista_gastos->Get($i);

			if (UtilesApp::GetConf($sesion, 'FacturaAsociada')) {
				if (UtilesApp::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
					$ws->write($filas, $col_descripcion - 3, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_descripcion - 2, $gasto->fields['glosa_gasto'], $CellFormat->get('descripcion'));
					$ws->mergeCells($filas, $col_descripcion - 2, $filas, $col_descripcion - 1);
					$ws->write($filas, $col_descripcion - 1, '', $CellFormat->get('descripcion'));
					$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $CellFormat->get('descripcion'));
					$ws->write($filas, $col_descripcion + 1, $gasto->fields['glosa'] . " N° " . $gasto->fields['codigo_factura_gasto'], $CellFormat->get('descripcion'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('descripcion'));
					if ($gasto->fields['egreso']) {
						$ws->writeNumber($filas, $col_descripcion + 3, $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					} else {
						$ws->writeNumber($filas, $col_descripcion + 3, -$gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					}
					$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
				} else {
					$ws->write($filas, $col_descripcion - 1, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $CellFormat->get('descripcion'));
					$ws->write($filas, $col_descripcion + 1, $gasto->fields['glosa'] . " N° " . $gasto->fields['codigo_factura_gasto'], $CellFormat->get('descripcion'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('descripcion'));
					if ($gasto->fields['egreso']) {
						$ws->writeNumber($filas, $col_descripcion + 3, $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					} else {
						$ws->writeNumber($filas, $col_descripcion + 3, -$gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					}
					$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
				}
			} else {
				if (UtilesApp::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
					$ws->write($filas, $col_descripcion - 3, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_descripcion - 2, $gasto->fields['glosa_gasto'], $CellFormat->get('descripcion'));
					$ws->mergeCells($filas, $col_descripcion - 2, $filas, $col_descripcion - 1);
					$ws->write($filas, $col_descripcion - 1, '', $CellFormat->get('descripcion'));
					$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $CellFormat->get('descripcion'));
					if ($gasto->fields['egreso']) {
						$ws->writeNumber($filas, $col_descripcion + 1, $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					} else {
						$ws->writeNumber($filas, $col_descripcion + 1, -$gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					}
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				} else {
					$ws->write($filas, $col_descripcion - 1, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $CellFormat->get('descripcion'));
					if ($gasto->fields['egreso']) {
						$ws->writeNumber($filas, $col_descripcion + 1, $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					} else {
						$ws->writeNumber($filas, $col_descripcion + 1, -$gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos'));
					}
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				}
			}

			++$filas;
		}

		// Total de gastos

		if (UtilesApp::GetConf($sesion, 'FacturaAsociada')) {
			if (UtilesApp::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				$ws->write($filas, $col_descripcion - 3, __('Total'), $CellFormat->get('total'));

				$ws->write($filas, $col_descripcion - 2, '', $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
				$ws->writeFormula($filas, $col_descripcion - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
				$ws->mergeCells($filas, $col_descripcion - 2, $filas, $col_descripcion + 4);
				$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
			} else {
				$ws->write($filas, $col_descripcion - 1, __('Total'), $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
				$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 4);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('total'));
				$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
			}
		} else {
			if (UtilesApp::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				$ws->write($filas, $col_descripcion - 3, __('Total'), $CellFormat->get('total'));

				$ws->write($filas, $col_descripcion - 2, '', $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
				$ws->writeFormula($filas, $col_descripcion - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
			} else {
				$ws->write($filas, $col_descripcion - 1, __('Total'), $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
				$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
			}
		}
	}

	if (Conf::read('UsarResumenExcel')) {
		// Resumen con información la forma de cobro y en caso de CAP una lista de los otros cobro que estan adentro de este CAP.
		$filas += 3;
		// Informacion general sobre el cobro:
		$ws->mergeCells($filas, $col_fecha, $filas, $col_fecha + 1);
		$ws->write($filas, $col_fecha, 'Información según ' . __('Cobro') . ':', $CellFormat->get('resumen_text_titulo'));
		$ws->write($filas++, $col_fecha + 1, '', $CellFormat->get('resumen_text'));
		$ws->write($filas, $col_fecha, 'Número ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->writeNumber($filas++, $col_fecha + 1, $cobro->fields['id_cobro'], $CellFormat->get('numeros'));
		$ws->write($filas, $col_fecha, 'Número Factura', $CellFormat->get('resumen_text_amarillo'));
		$ws->writeNumber($filas++, $col_fecha + 1, $cobro->fields['documento'], $CellFormat->get('numeros_amarillo'));
		$ws->write($filas, $col_fecha, 'Forma ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->write($filas++, $col_fecha + 1, $cobro->fields['forma_cobro'], $CellFormat->get('resumen_text_derecha'));
		$ws->write($filas, $col_fecha, 'Periodo ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->write($filas++, $col_fecha + 1, $cobro->fields['fecha_ini'] . ' - ' . $cobro->fields['fecha_fin'], $CellFormat->get('resumen_text_derecha'));
		$ws->write($filas, $col_fecha, 'Total ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->writeNumber($filas++, $col_fecha + 1, $cobro->fields['monto'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'] + $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen_cobro'));

		// Si la forma del cobro es cap imprime una lista de todos los cobros anteriores incluido en este CAP:
		if ($cobro->fields['forma_cobro'] == 'CAP') {
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('monto_cap_inicial', 'Resumen', $lang), $CellFormat->get('resumen_text'));
			$ws->writeNumber($filas++, $col_fecha + 1, $contrato->fields['monto'], $CellFormat->get('moneda_monto_resumen'));
			$fila_inicial = $filas;
			$query_cob = "SELECT cobro.id_cobro, cobro.documento, ((cobro.monto_subtotal-cobro.descuento)*cm2.tipo_cambio)/cm1.tipo_cambio
											FROM cobro
											JOIN contrato ON cobro.id_contrato=contrato.id_contrato
											JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
											JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
										 	WHERE cobro.id_contrato=" . $cobro->fields['id_contrato'] . "
										 	AND cobro.forma_cobro='CAP'";
			$resp_cob = mysql_query($query_cob, $sesion->dbh) or Utiles::errorSQL($query_cob, __FILE__, __LINE__, $sesion->dbh);
			while (list($id_cobro, $id_factura, $monto_cap) = mysql_fetch_array($resp_cob)) {
				$monto_cap = number_format($monto_cap, $moneda_monto->fields['cifras_decimales'], '.', '');
				$ws->write($filas, $col_fecha, __('Factura N°') . ' ' . $id_factura, $CellFormat->get('resumen_text'));
				$ws->writeNumber($filas++, $col_fecha + 1, $monto_cap, $CellFormat->get('moneda_monto_resumen'));
			}
			$ws->write($filas, $col_fecha, $PrmExcelCobro->getGlosa('monto_cap_restante', 'Resumen', $lang), $CellFormat->get('resumen_text'));
			$formula_cap_restante = "$col_formula_abogado$fila_inicial - SUM($col_formula_abogado" . ($fila_inicial + 1) . ":$col_formula_abogado$filas)";
			$ws->writeFormula($filas++, $col_fecha + 1, "=IF($formula_cap_restante>0, $formula_cap_restante, 0)", $CellFormat->get('moneda_monto_resumen'));
		}
	}
	// Si el cobro es RETAINER o PROPORCIONAL vuelve la definición de las columnas al
	// estado normal para patir en cero en el siguiente cobro

}
// fin bucle cobros
if (isset($ws)) {
	// Se manda el archivo aquí para que no hayan errores de headers al no haber resultados.
	if (!$guardar_respaldo) {
		header('Set-Cookie: fileDownload=true; path=/');
		$wb->send('Resumen_rentabilidad_liquidacion_' . $cobro->fields['id_cobro'] . '.xls');
	}
}
$wb->close();
exit;
