<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM', 'COB'));

set_time_limit(0);
ini_set('memory_limit', '2024M');

// Search Criteria para realizar la búsqueda de todos los cobros a incluir
$searchCriteria = new SearchCriteria('Charge');
$searchCriteria->related_with('Contract')->on_property('id_contrato')->with_direction('INNER');
$searchCriteria->related_with('Matter')->joined_with('Contract')->on_property('id_contrato');
$searchCriteria->related_with('Client')->joined_with('Matter')->on_property('codigo_cliente');
$searchCriteria->add_scope('orderByClientGlossAndClientCode');

if ($id_cobro) {
	$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($id_cobro);
}

if (!isset($forzar_username)) {
	$forzar_username = false;
}

$ingreso_via_decimales = false;
$formato_duraciones = '[h]:mm';
if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
	$ingreso_via_decimales = true;
	$formato_duraciones = '0.0';
}

function fecha_valor($fecha) {
	$fecha = explode('-', $fecha);
	if (sizeof($fecha) != 3) {
		return 0;
	}
	// number of seconds in a day
	$seconds_in_a_day = 86400;
	// Unix timestamp to Excel date difference in seconds
	$ut_to_ed_diff = $seconds_in_a_day * 25569;

	$time = mktime(0, 0, 0, $fecha[1], $fecha[2], $fecha[0]);
	$time_max = mktime(0, 0, 0, $fecha[1], $fecha[2], '2037');

	if ($fecha[0] != '0000') {
		if (floatval($fecha[0]) <= 2040) {
			return floor(($time + $ut_to_ed_diff) / $seconds_in_a_day);
		} else {
			return floor(($time_max + $ut_to_ed_diff) / $seconds_in_a_day);
		}
	}
	return 0;
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
	$searchCriteria->filter('activo')->restricted_by('equals')->compare_with("'SI'")->for_entity('Contract');
} else if ($no_activo) {
	$searchCriteria->filter('activo')->restricted_by('equals')->compare_with("'NO'")->for_entity('Contract');
}

if ($forma_cobro) {
	$searchCriteria->filter('forma_cobro')->restricted_by('equals')->compare_with("'$forma_cobro'")->for_entity('Contract');
}

if ($id_usuario) {
	$searchCriteria->filter('id_usuario_responsable')->restricted_by('equals')->compare_with("'$id_usuario'")->for_entity('Contract');
}

if ($codigo_cliente) {
	$searchCriteria->filter('codigo_cliente')->restricted_by('equals')->compare_with("'$codigo_cliente'")->for_entity('Client');
}

if ($id_grupo_cliente) {
	$searchCriteria->filter('id_grupo_cliente')->restricted_by('equals')->compare_with("'$id_grupo_cliente'")->for_entity('Client');
}

if ($forma_cobro) {
	$searchCriteria->filter('forma_cobro')->restricted_by('equals')->compare_with("'$forma_cobro'")->for_entity('Contract');
}

if ($tipo_liquidacion) { //1:honorarios, 2:gastos, 3:mixtas
	$incluye_honorarios = $tipo_liquidacion & 1 ? true : false;
	$incluye_gastos = $tipo_liquidacion & 2 ? true : false;
	$searchCriteria->filter('incluye_gastos')->restricted_by('equals')->compare_with("'$incluye_gastos'");
	$searchCriteria->filter('incluye_honorarios')->restricted_by('equals')->compare_with("'$incluye_honorarios'");
}

if ($codigo_asunto) {
	$searchCriteria->filter('codigo_asunto')->restricted_by('equals')->compare_with("'$codigo_asunto'")->for_entity('Matter');
}

if (!$id_cobro) {
	$borradores = true;
	$opc_ver_gastos = 1;
}
$mostrar_resumen_de_profesionales = 1;

if ($guardar_respaldo) {
	$wb = new Spreadsheet_Excel_Writer(Conf::ServerDir() . '/respaldos/ResumenCobros' . date('ymdHis') . '.xls');
	$wb->setVersion(8);
} else {
	$wb = new Spreadsheet_Excel_Writer();
	$wb->setVersion(8);
	// No se hace $wb->send() todavía por si acaso no hay horas en el cobro.
}

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);
$wb->setCustomColor(37, 255, 255, 0);
$wb->setCustomColor(38, 234, 234, 234);

/*
 *	FORMATO DE CELDAS PARA EL DOCUMENTO
 */
$CellFormat = new CellFormat($wb);
$CellFormat->serDefault(
	array('Size' => 7, 'VAlign' => 'top', 'Color' => 'black', 'Align' => 'left'),
	array('FgColor' => '38'),
	array('FgColor' => 'white')
);
$CellFormat->add('encabezado', array('Size' => 10, 'VAlign' => 'middle', 'Bold' => 1));
$CellFormat->add('encabezado2', array('Size' => 10, 'VAlign' => 'middle'));
$CellFormat->add('encabezado_derecha', array('Size' => 10, 'Align' => 'right', 'Bold' => 1));
$CellFormat->add('titulo', array('Size' => 10, 'Bold' => 1, 'Locked' => 1, 'Bottom' => 1, 'FgColor' => 35));
$CellFormat->add('titulo_vcentrado', array('Size' => 10, 'VAlign' => 'vjustify', 'FgColor' => 35, 'Bottom' => 1, 'Locked' => 1, 'Bold' => 1));
$CellFormat->add('normal_centrado', array('Align' => 'center'));
$CellFormat->add('normal');
$CellFormat->add('descripcion', array('TextWrap' => 1));
$CellFormat->add('observacion', array('Bold' => 1, 'Italic' => 1));
$CellFormat->add('tiempo', array('NumFormat' => $formato_duraciones));
$CellFormat->add('total', array('Size' => 10, 'Bold' => 1, 'Top' => 1));
$CellFormat->add('tiempo_total', array('Size' => 10, 'Bold' => 1, 'Top' => 1, 'NumFormat' => $formato_duraciones));
$CellFormat->add('instrucciones12', array('Size' => 12, 'Bold' => 1));
$CellFormat->add('instrucciones10', array('Size' => 10, 'Bold' => 1));
$CellFormat->add('resumen_text', array('Border' => 1, 'TextWrap' => 1));
$CellFormat->add('resumen_text_derecha', array('Align' => 'right', 'Border' => 1));
$CellFormat->add('resumen_text_titulo', array('Size' => 9, 'Bold' => 1, 'Border' => 1));
$CellFormat->add('resumen_text_amarillo', array('Border' => 1, 'FgColor' => '37', 'TextWrap' => 1));
$CellFormat->add('numeros', array('Border' => 1, 'Align' => 'right', 'NumFormat' => '0'));
$CellFormat->add('numeros_amarillo', array('Border' => 1, 'Align' => 'right', 'TextWrap' => '0', 'FgColor' => '37'));

/*
 *	FIN FORMATO DE CELDAS PARA EL DOCUMENTO
 */

/*
 *	Definimos las columnas, mantenerlas así permite agregar nuevas columnas sin tener que rehacer todo.
 */

/*
 *	IMPORTANTE:
 *	Se asume que las columnas $col_id_trabajo, $col_fecha y $col_abogado son las primeras tres y que
 *	$col_tarifa_hh, $col_valor_trabajo y $col_id_abogado son las últimas tres (aunque $col_id_abogado es
 *	una columan oculta), en ese orden.
 *
 *	Estos valores se usan para definir dónde se escribe el encabezado, resumen y otros, conviene no modificarlas.
 *	Se puede modificar el orden de las otras columnas.
 */


$col = 0;
$col_id_trabajo = $col++;

$col_fecha_ini = $col++;
$col_fecha_med = $col++;
$col_fecha_fin = $col++;

$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma->Load($cobro->fields['codigo_idioma']);

if ($idioma->fields['formato_fecha'] == '%d/%m/%y' || $borradores) {
	$col_fecha_dia = $col_fecha_ini;
	$col_fecha_mes = $col_fecha_med;
	$col_fecha_anyo = $col_fecha_fin;
} else {
	$col_fecha_mes = $col_fecha_ini;
	$col_fecha_dia = $col_fecha_med;
	$col_fecha_anyo = $col_fecha_fin;
}

$col_abogado = $col++;
if ($opc_ver_columna_cobrable) {
	$col_es_cobrable = $col++;
}
if (!$opc_ver_asuntos_separados) {
	$col_asunto = $col++;
}
$col_solicitante = $col++;
$col_descripcion = $col++;

if ($opc_ver_horas_trabajadas) {
	$col_duracion_trabajada = $col++;
}
$col_tarificable_hh = $col++;
$col_cobrable = $col++;
$col_tarifa_hh = $col++;
$col_valor_trabajo = $col++;
$col_id_abogado = max(array($col++, $col_descripcion + 6));
unset($col);

/*
 *	Valores para usar en las fórmulas de la hoja
 */

$col_formula_descripcion = Utiles::NumToColumnaExcel($col_descripcion);
if ($opc_ver_horas_trabajadas) {
	$col_formula_duracion_trabajada = Utiles::NumToColumnaExcel($col_duracion_trabajada);
}
$col_formula_duracion_cobrable = Utiles::NumToColumnaExcel($col_tarificable_hh);
$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
$col_formula_tarificable_hh = Utiles::NumToColumnaExcel($col_tarificable_hh);
$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
$col_formula_abogado = Utiles::NumToColumnaExcel($col_abogado);

if ($col_asunto) {
	$col_formula_asunto = Utiles::NumToColumnaExcel($col_asunto);
}
if ($col_duracion_retainer) {
	$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
}

$col_formula_tarificable_hh_detalle = Utiles::NumToColumnaExcel($col_cobrable);

/*
 *   Esta variable se usa para que cada página tenga un nombre único.
 */

$numero_pagina = 0;
$PrmExcelCobro = new PrmExcelCobro($sesion);
if ($borradores) {
	$ws = & $wb->addWorksheet('Inicio');
	$ws->setPaper(1);
	$ws->hideScreenGridlines();
	$ws->setMargins(0.01);
	if (Conf::GetConf($sesion, 'ImprimirExcelCobrosUnaPagina')) {
		$ws->fitToPages(1, 1);
	} else {
		$ws->fitToPages(1, 0);
	}

	/*
	 *	Seteamas el ancho de las columnas, se definen en la tabla prm_excel_cobro
	 */

	$ws->setColumn($col_id_trabajo, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	if (Conf::GetConf($sesion, 'UsarResumenExcel')) {
		$ws->setColumn($col_fecha_ini, $col_fecha_ini, 7);
		$ws->setColumn($col_fecha_med, $col_fecha_med, 7);
		$ws->setColumn($col_fecha_fin, $col_fecha_fin, 7);

		$ws->setColumn($col_abogado, $col_abogado, 15);
	} else {
		$ws->setColumn($col_fecha_dia, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
		$ws->setColumn($col_fecha_mes, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
		$ws->setColumn($col_fecha_anyo, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));


		$ws->setColumn($col_abogado, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	if (!$opc_ver_asuntos_separados) {
		$ws->setColumn($col_asunto, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}
	$ws->setColumn($col_descripcion, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_tarificable_hh, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_valor_trabajo, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_id_abogado, $col_id_abogado, 0, 0, 1);

	/*
	 *	Es necesario setear estos valores para que la emisión masiva funcione.
	 */
	$filas = 0;

	/*
	 * Indicaciones correspondiente a la modificacion de trabajos desde el excel
	 */

	++$filas;
	$ws->write($filas, $col_descripcion, 'INSTRUCCIONES:', $CellFormat->get('instrucciones12'));
	$filas+=2;
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "Número del trabajo (no modificar). En el caso de dejar en blanco se ingresará un nuevo trabajo", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "La fecha se puede modificar, en el caso de ser modificada debe estar en el formato dd-mm-aaaa", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "Se puede modificar el usuario que realizó el trabajo, es importante que el nombre utilizado sea el que tiene el sistema. En el caso de que el sistema no reconozca el nombre no se hará la modificación de usuario.", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "Se puede hacer cualquier modificación. No hay restricciones de formato.", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "Se puede modificar la duración cobrable. Debe tener el formato hh:mm:ss (formato de tiempo de Excel). Si se deja en 00:00:00 el trabajo será 'castigado', no aparecerá en el cobro.", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "No se puede modificar la tarifa. Esto debe ser hecho desde la información del cliente o asunto.", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
	++$filas;
	$ws->write($filas, $col_id_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('normal'));
	$ws->write($filas, $col_fecha_ini, "Fórmula que equivale a la multiplicación de la tarifa por la duración cobrable.", $CellFormat->get('normal'));
	$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);

	++$filas;
	++$filas;

	/*
	 *  Encabezado de tabla vacia
	 */

	$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));   //si no se quiere ver esto se oculta al final de cada hoja

	if (!$opc_ver_asuntos_separados) {
		$ws->write($filas, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	}

	$ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	if ($opc_ver_horas_trabajadas) {
		$ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	}

	$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
	$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
	$ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
	$ws->write($filas, $col_id_abogado, __('NO MODIFICAR ESTA COLUMNA'));

	/*
	 *	Deje un poco de espacio entre encabezado y los totales
	 */
	$filas += 3;

	/*
	 *	 Imprimir el total del último asunto.
	 */

	$ws->write($filas, $col_id_trabajo, __('Total'), $CellFormat->get('total'));
	$ws->write($filas, $col_fecha_ini, '', $CellFormat->get('total'));
	$ws->write($filas, $col_fecha_med, '', $CellFormat->get('total'));
	$ws->write($filas, $col_fecha_fin, '', $CellFormat->get('total'));
	$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
	$ws->write($filas, $col_solicitante, '', $CellFormat->get('total'));
	$ws->write($filas, $col_abogado, '', $CellFormat->get('total'));

	if (!$opc_ver_asuntos_separados) {
		$ws->write($filas, $col_asunto, '', $CellFormat->get('total'));
	}
	if ($opc_ver_horas_trabajadas) {
		$ws->write($filas, $col_duracion_trabajada, "0:00", $CellFormat->get('tiempo_total'));
	}

	$ws->write($filas, $col_tarificable_hh, "0:00", $CellFormat->get('tiempo_total'));
	$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
	$ws->write($filas, $col_valor_trabajo, '', $CellFormat->get('total'));

	$ws->setRow($filas - 4, 0, 0, 1);
	$ws->setRow($filas - 3, 0, 0, 1);
	$ws->setRow($filas - 2, 0, 0, 1);
	$ws->setRow($filas - 1, 0, 0, 1);
	$ws->setRow($filas, 0, 0, 1);
}

if ($borradores) {
	$searchCriteria->filter('estado')->restricted_by('in')->compare_with(array("CREADO", "EN REVISION"));
} else {
	$estado_cobro = $cobro->fields['estado'];
	$searchCriteria->filter('estado')->restricted_by('equals')->compare_with("'$estado_cobro'");
}

$SearchingBusiness = new SearchingBusiness($sesion);
$results = $SearchingBusiness->searchByGenericCriteria($searchCriteria, array('DISTINCT(Charge.id_cobro)'));
$chargeIds = array_map(function($element) {
	return $element->get('charge_id_cobro');
}, $results->toArray());

// Search criteria que obtiene las entidades Cobro para no hacer
// select  por cada uno, además trae la información necesaria de Document.
$chargeSearchCriteria = new SearchCriteria('Charge');
$chargeSearchCriteria->related_with('Client')->with_direction('LEFT')->on_property('codigo_cliente');
$chargeSearchCriteria->add_scope('withDocument');
$chargeSearchCriteria->filter('id_cobro')->restricted_by('in')->compare_with($chargeIds);
$chargeSearchCriteria->add_scope_for('Client', 'orderByClientGloss');
$chargeResults = $SearchingBusiness->searchByCriteria(
	$chargeSearchCriteria,
	array('*', 'Document.subtotal_honorarios as document_subtotal_honorarios')
);

$trabajos_duracion = array();
$Moneda = new Moneda($sesion);

foreach ($chargeResults as $charge) {
	$id_cobro = $charge->get('id_cobro');

	$cobro = new Cobro($sesion);
	$cobro->fields = $charge->fields;
	$cobro->LoadAsuntos();

	/*
	*	Si es que el cobro es RETAINER o PROPORCIONAL modifica las columnas del excel
	*/

	if (($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' || $cobro->fields['forma_cobro'] == 'FLAT FEE')) {
		$col_duracion_retainer = $col_tarificable_hh + 1;
		$col_cobrable++;
		$col_tarifa_hh++;
		$col_valor_trabajo++;
		$col_id_abogado++;

		$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
		$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
		$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
		$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);

		if ($col_duracion_retainer) {
			$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
		}
	}

	/*
	 *	Estas variables son necesario para poder decidir si se imprima una tabla o no,
	 *	generalmente si no tiene data no se escribe
	 */

	$query_cont_trabajos_cobro = "SELECT COUNT(*) FROM trabajo WHERE id_cobro='{$cobro->fields['id_cobro']}'";
	$resp_cont_trabajos_cobro = mysql_query($query_cont_trabajos_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_trabajos_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_trabajos_cobro) = mysql_fetch_array($resp_cont_trabajos_cobro);

	$query_cont_tramites_cobro = "SELECT COUNT(*) FROM tramite WHERE id_cobro='{$cobro->fields['id_cobro']}'";
	$resp_cont_tramites_cobro = mysql_query($query_cont_tramites_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_tramites_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_tramites_cobro) = mysql_fetch_array($resp_cont_tramites_cobro);

	$query_cont_gastos_cobro = "SELECT COUNT(*) FROM cta_corriente WHERE id_cobro='{$cobro->fields['id_cobro']}'";
	$resp_cont_gastos_cobro = mysql_query($query_cont_gastos_cobro, $sesion->dbh) or Utiles::errorSQL($query_cont_gastos_cobro, __FILE__, __LINE__, $sesion->dbh);
	list($cont_gastos_cobro) = mysql_fetch_array($resp_cont_gastos_cobro);

	$cobro_moneda = new CobroMoneda($sesion);
	$cobro_moneda->Load($cobro->fields['id_cobro']);

	$contrato = new Contrato($sesion);
	$contrato->Load($cobro->fields['id_contrato']);

	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);

	$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'simbolo', 'prm_moneda', 'id_moneda');
	if ($glosa_moneda == "Euro") {
		$simbolo_moneda = "EUR";
	}

	$formato_opc_moneda_total = $Moneda->getExcelFormat($cobro->fields['opc_moneda_total']);
	$formato_id_moneda_monto = $Moneda->getExcelFormat($contrato->fields['id_moneda_monto']);
	$formato_id_moneda = $Moneda->getExcelFormat($cobro->fields['id_moneda']);

	$CellFormat->add('moneda_resumen', array('Size' => 10, 'Align' => 'right', 'Bold' => '1', 'NumFormat' => $formato_opc_moneda_total));
	$CellFormat->add('moneda_gastos', array('Align' => 'right', 'NumFormat' => $formato_opc_moneda_total));
	$CellFormat->add('moneda_gastos_total', array('Size' => 10, 'Align' => 'right', 'Bold' => 1, 'Top' => 1, 'NumFormat' => $formato_opc_moneda_total));
	$CellFormat->add('moneda_resumen_cobro', array('Align' => 'right', 'Border' => '1', 'NumFormat' => $formato_opc_moneda_total));

	$CellFormat->add('moneda_monto', array('Align' => 'right', 'Border' => 1, 'NumFormat' => $formato_id_moneda_monto));

	$CellFormat->add('moneda', array('Align' => 'right', 'NumFormat' => $formato_id_moneda));
	$CellFormat->add('moneda_total', array('Size' => 10, 'Align' => 'right', 'Bold' => 1, 'Top' => 1, 'NumFormat' => $formato_id_moneda));
	$CellFormat->add('moneda_encabezado', array('Size' => 10, 'Align' => 'right', 'Bold' => 1, 'NumFormat' => $formato_id_moneda));

	/*
	 *	Imprime el encabezado de la hoja
	 *	El largo máximo para el nombre de una hoja son 31 caracteres, reservamos 4 para el número de página y un espacio.
	 *	Es importante notar que los nombres se truncan automáticamente con el primer caracter con tilde o ñ.
	 */

	$nombre_pagina = ++$numero_pagina . ' ';
	if (strlen($cliente->fields['glosa_cliente']) > 27) {
		$nombre_pagina .= substr($cliente->fields['glosa_cliente'], 0, 24) . '...';
	} else {
		$nombre_pagina .= $cliente->fields['glosa_cliente'];
	}

	$nombre_pagina = str_replace(array('/', '&', '\\'), '', $nombre_pagina);
	$ws = & $wb->addWorksheet($nombre_pagina);
	$ws->setPaper(1);
	$ws->hideScreenGridlines();
	$ws->setMargins(0.01);
	if (Conf::GetConf($sesion, 'ImprimirExcelCobrosUnaPagina')) {
		$ws->fitToPages(1, 1);
	} else {
		$ws->fitToPages(1, 0);
	}

	/*
	 *	 Seteamas el ancho de las columnas
	 */

	$ws->setColumn($col_id_trabajo, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));

	if (Conf::GetConf($sesion, 'UsarResumenExcel')) {
		$ws->setColumn($col_fecha_ini, $col_fecha_ini, 7);
		$ws->setColumn($col_fecha_med, $col_fecha_med, 7);
		$ws->setColumn($col_fecha_fin, $col_fecha_fin, 7);
		$ws->setColumn($col_abogado, $col_abogado, 15);
	} else {
		$ws->setColumn($col_fecha_dia, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
		$ws->setColumn($col_fecha_mes, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
		$ws->setColumn($col_fecha_anyo, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
		$ws->setColumn($col_abogado, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	if (!$opc_ver_asuntos_separados) {
		$ws->setColumn($col_asunto, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	$ws->setColumn($col_descripcion, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));

	if ($opc_ver_horas_trabajadas) {
		$ws->setColumn($col_duracion_trabajada, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	$ws->setColumn($col_tarificable_hh, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));

	if ($col_duracion_retainer) {
		$ws->setColumn($col_duracion_retainer, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_valor_trabajo, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	$ws->setColumn($col_id_abogado, $col_id_abogado, 0, 0, 1);

	if (Conf::GetConf($sesion, 'OcultarHorasTarificadasExcel')) {
		$ws->setColumn($col_cobrable, $col_cobrable, 0, 0, 1);
	} else {
		$ws->setColumn($col_cobrable, $col_cobrable, Utiles::GlosaMult($sesion, 'cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}

	/*
	 *  Agregar la imagen del logo
	 */

	$altura_logo = UtilesApp::AlturaLogoExcel();
	if ($altura_logo) {
		$ws->setRow(0, .8 * $altura_logo);
		$ws->insertBitmap(0, 0, Conf::GetConf($sesion, 'LogoExcel'), 0, 0, .8, .8);
	}

	/*
	 *  Es necesario setear estos valores para que la emisión masiva funcione.
	 */

	$primer_asunto = true;
	$creado = false;
	$filas = 0;
	$filas_totales_asuntos = array();

	$cliente = new Cliente($sesion);

	if (Conf::GetConf($sesion, 'CodigoSecundario')) {
		$codigo_cliente = $cliente->CodigoACodigoSecundario($cobro->fields['codigo_cliente']);
	} else {
		$codigo_cliente = $cobro->fields['codigo_cliente'];
	}

	$ws->write($filas, $col_descripcion + 1, Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo') . ' ' . $cobro->fields['id_cobro'], $CellFormat->get('encabezado'));
	$filas++;
	$ws->write($filas, $col_descripcion + 1, __('Código Cliente') . ': ' . $codigo_cliente, $CellFormat->get('encabezado'));
	$filas++;

	/*
	 *  Escribir el encabezado con los datos del cliente
	 */

	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'cliente', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$ws->write($filas, $col_abogado, $contrato->fields['codigo_cliente'] . ' - ' . str_replace('\\', '', $contrato->fields['factura_razon_social']), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
	++$filas;

	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'rut', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$ws->write($filas, $col_abogado, $contrato->fields['rut'], $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
	++$filas;


	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'direccion', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$direccion = str_replace("\r", " ", $contrato->fields['direccion_contacto']);
	$direccion = str_replace("\n", " ", $direccion);
	$ws->write($filas, $col_abogado, $direccion, $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
	++$filas;

	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'contacto', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$ws->write($filas, $col_abogado, $contrato->fields['contacto'], $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
	++$filas;

	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'telefono', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$ws->write($filas, $col_abogado, $contrato->fields['fono_contacto'], $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
	++$filas;

	$usuario = new Usuario($sesion);
	$usuario->LoadId($contrato->fields['id_usuario_responsable']);
	$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'encargado_comercial', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
	$ws->write($filas, $col_abogado, $usuario->fields['nombre'] . ' ' . $usuario->fields['apellido1'] . ' ' . $usuario->fields['apellido2'], $CellFormat->get('encabezado'));
	$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);

	$filas += 2;

	if ($opc_ver_resumen_cobro || $borradores) {
		$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));

		/*
		 *  Esto es para poder escribir la segunda columna más fácilmente.
		 */

		$filas2 = $filas;

		$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));

		if($trabajo->fields['fecha_emision'] == '0000-00-00' or $trabajo->fields['fecha_emision'] == '') {
			$fecha_emision = Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']);
		} else {
			$fecha_emision = Utiles::sql2fecha($trabajo->fields['fecha_emision'], $idioma->fields['formato_fecha']);
		}
		$ws->write($filas++, $col_abogado, $fecha_emision, $CellFormat->get('encabezado'));

		/*
		 *	Se saca la fecha inicial según el primer trabajo
		 *	esto es especial para LyR
		 */

		if ( $cobro->fields['fecha_ini'] == '0000-00-00' ) {
			$query_fecha_primer_trabajo = "SELECT MIN( DATE( fecha ) ) FROM trabajo WHERE id_cobro ='".$cobro->fields['id_cobro']."'";
			$resp_fecha_primer_trabajo = mysql_query($query_fecha_primer_trabajo, $sesion->dbh) or Utiles::errorSQL($query_fecha_primer_trabajo, __FILE__, __LINE__, $sesion->dbh);
			list($primer_trabajo) = mysql_fetch_array($resp_fecha_primer_trabajo);
			$fecha_primer_trabajo = $primer_trabajo;
		} else {
			$fecha_primer_trabajo = $cobro->fields['fecha_ini'];
		}

		$fecha_ultimo_trabajo = $cobro->fields['fecha_fin'];

		$fecha_inicial_primer_trabajo = date('Y-m-d', strtotime($fecha_primer_trabajo));
		$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

		if ($fecha_primer_trabajo && $fecha_primer_trabajo != '0000-00-00') {
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha_desde', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));

			$ws->write($filas++, $col_abogado, Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $CellFormat->get('encabezado'));
		}

		$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha_hasta', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
		$ws->write($filas++, $col_abogado, Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $CellFormat->get('encabezado'));

		/*
		 *  Si hay una factura asociada mostramos su número.
		 */

		if ($cobro->fields['documento']) {
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'factura', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
			$ws->write($filas++, $col_abogado, $cobro->fields['documento'], $CellFormat->get('encabezado'));
		}

		if ($opc_ver_modalidad) {
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'forma_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
			$mje_detalle_forma_cobro = '';
			if (($cobro->fields['forma_cobro'] == 'PROPORCIONAL') || ($cobro->fields['forma_cobro'] == 'RETAINER')) {
				//$mje_detalle_forma_cobro = "de " . $cobro->fields['retainer_horas'] . " Hr. por " . $simbolo_moneda_opc_moneda_monto . " " . number_format($cobro->fields['monto_contrato'], $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales'], ',', '.');
				$mje_detalle_forma_cobro = "de {$cobro->fields['retainer_horas']} Hr. por " . $Moneda->currencyFormat($cobro->fields['monto_contrato'], $contrato->fields['id_moneda_monto']);
			}
			$ws->write($filas++, $col_abogado, __($cobro->fields['forma_cobro']) . " " . $mje_detalle_forma_cobro, $CellFormat->get('encabezado'));
		}

		if ($trabajo->fields['forma_cobro'] == 'PROPORCIONAL' || $trabajo->fields['forma_cobro'] == 'RETAINER') {
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'horas_retainer', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
			$ws->write($filas++, $col_abogado, $cobro->fields['retainer_horas'], $CellFormat->get('encabezado_derecha'));
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'monto_retainer', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
			$ws->writeNumber($filas++, $col_abogado, $cobro->fields['monto_contrato'], $CellFormat->get('moneda_monto'));
		}

		/*
		 *  Segunda columna
		 */

		$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_horas', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));

		$horas_cobrables = floor($cobro->fields['total_minutos'] / 60);
		$minutos_cobrables = sprintf("%02d", $cobro->fields['total_minutos'] % 60);

		$ws->write($filas2++, $col_valor_trabajo, "$horas_cobrables:$minutos_cobrables", $CellFormat->get('encabezado_derecha'));

		if ($cobro->fields['forma_cobro'] == 'ESCALONADA') {
			$chargingBusiness = new ChargingBusiness($sesion);
			$id_cobro = $cobro->fields['id_cobro'];
			$scales = $chargingBusiness->getSlidingScales($id_cobro);
			$bruto = 0;
			$descuento = 0;
			$neto = 0;
			foreach ($scales as $scale) {
				$bruto += $scale->get('amount');
				$descuento += $scale->get('discount');
				$neto += $scale->get('netAmount');
			}
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'subtotal', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
			$ws->writeNumber($filas2++, $col_valor_trabajo, $bruto, $CellFormat->get('moneda_encabezado'));
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'descuento', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
			$ws->writeNumber($filas2++, $col_valor_trabajo, $descuento, $CellFormat->get('moneda_encabezado'));
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
			$ws->writeNumber($filas2++, $col_valor_trabajo, $neto, $CellFormat->get('moneda_encabezado'));
		}

		$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'honorarios', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
		$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_subtotal'], $CellFormat->get('moneda_encabezado'));

		if ($cobro->fields['id_moneda'] != $cobro->fields['opc_moneda_total']) {
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'equivalente', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION') {
				$monto_subtotal = number_format($cobro->fields['monto_subtotal'],2, '.', '');
				$id_moneda = $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'];
				$opc_moneda_total = $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
				$monto = $monto_subtotal * $id_moneda / $opc_moneda_total;
			} else {
				$monto = $cobro->fields['document_subtotal_honorarios'];
			}
			$ws->writeNumber($filas2++, $col_valor_trabajo, $monto, $CellFormat->get('moneda_resumen'));
		}

		if ($cobro->fields['descuento'] > 0 && $opc_ver_descuento) {
			$porcentaje_descuento = " ({$cobro->fields['porcentaje_descuento']}%)";
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'descuento', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo') . $porcentaje_descuento, $CellFormat->get('encabezado_derecha'));
			$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['descuento'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $CellFormat->get('moneda_resumen'));
			$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'subtotal', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
			$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo" . ($filas2 - 2) . "-$col_formula_valor_trabajo" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
		}

		if ($cobro->fields['porcentaje_impuesto'] > 0 && Conf::GetConf($sesion, 'ValorImpuesto') > 0) {
			$porcentaje_impuesto = " ({$cobro->fields['porcentaje_impuesto']}%)";
			if ($cobro->fields['porcentaje_impuesto_gastos'] > 0 && Conf::GetConf($sesion, 'ValorImpuestoGastos') > 0) {
				if ($opc_ver_gastos) {
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['subtotal_gastos'], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo') . $porcentaje_impuesto, $CellFormat->get('encabezado_derecha'));
					$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro']);
					$ws->write($filas2++, $col_valor_trabajo, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo" . ($filas2 - 3) . ":$col_formula_valor_trabajo" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
				} else {
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo') . $porcentaje_impuesto, $CellFormat->get('encabezado_derecha'));
					$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro']);
					$ws->write($filas2++, $col_valor_trabajo, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo" . ($filas2 - 2) . " + $col_formula_valor_trabajo" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
				}
			} else {
				$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo') . $porcentaje_impuesto, $CellFormat->get('encabezado_derecha'));
				$x_resultados_tmp = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro']);
				$ws->write($filas2++, $col_valor_trabajo, $x_resultados_tmp['monto_iva'][$cobro->fields['opc_moneda_total']], $CellFormat->get('moneda_resumen'));
				if ($opc_ver_gastos) {
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen'));
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo" . ($filas2 - 3) . ":$col_formula_valor_trabajo" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
				} else {
					$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
					$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo" . ($filas2 - 2) . " + $col_formula_valor_trabajo" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
				}
			}
		} else {
			if ($opc_ver_gastos) {
				$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
				$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen'));
				$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
				$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo" . ($filas2 - 2) . ":$col_formula_valor_trabajo" . ($filas2 - 1) . ")", $CellFormat->get('moneda_resumen'));
			} else {
				$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado_derecha'));
				$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo" . ($filas2 - 1), $CellFormat->get('moneda_resumen'));
			}
		}

		/*
		 *  Para seguir imprimiendo datos hay definir en que linea será
		 */

		$filas = max($filas, $filas2);
		++$filas;
	}

	if (in_array($cobro->fields['estado'], array('EMITIDO', 'FACTURADO'))) {
		if ($lang == 'es') {
			$mes_concepto = ucfirst(Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B %Y'));
		} else {
			$mes_concepto = date('F Y', strtotime($cobro->fields['fecha_fin']));
		}

		$concepto = Utiles::GlosaMult($sesion, 'concepto_glosa', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo');
		$concepto = sprintf($concepto, $mes_concepto);

		$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'concepto', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
		$ws->write($filas, $col_abogado, $concepto, $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
		++$filas;

		$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'glosa_factura', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
		$ws->write($filas, $col_abogado, $contrato->fields['glosa_contrato'], $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
		++$filas;
	}

	if (in_array($cobro->fields['estado'], array('CREADO', 'EN REVISION'))) {
		$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'detalle_cobranza', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
		$ws->write($filas, $col_abogado, $contrato->fields['observaciones'], $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
		++$filas;
	}

	if ($cobro->fields['forma_cobro'] == 'ESCALONADA') {
		$cobro_moneda = new CobroMoneda($sesion);
		$cobro_moneda->Load($id_cobro);
		$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($lang);
		$cobro_valores = array();
		// Obtener datos escalonados
		$chargingBusiness = new ChargingBusiness($sesion);
		$slidingScales = $chargingBusiness->getSlidingScalesArrayDetail($id_cobro);

		$cobro_valores['totales'] = array();
		$cobto_valores['datos_escalonadas'] = array();

		$cobro->CargarEscalonadas();
		$cobro_valores['datos_escalonadas'] = $cobro->escalonadas;

		$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";

		// Se seleccionan todos los trabajos del cobro, se incluye que sea cobrable ya que a los trabajos visibles
		// tambien se consideran dentro del cobro, tambien se incluye el valor del retainer del trabajo.

		if ($lang == 'es') {
			$query_categoria_lang = "prm_categoria_usuario.glosa_categoria as categoria";
		} else {
			$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria) as categoria";
		}
		$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada,
						trabajo.descripcion,
						trabajo.fecha,
						trabajo.id_usuario,
						$dato_monto_cobrado as monto_cobrado,
						trabajo.id_moneda as id_moneda_trabajo,
						trabajo.id_trabajo,
						trabajo.tarifa_hh,
						trabajo.cobrable,
						trabajo.visible,
						trabajo.codigo_asunto,
						CONCAT_WS(' ', nombre, apellido1) as usr_nombre,
						$query_categoria_lang
				FROM trabajo
				JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
				LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario
				WHERE trabajo.id_cobro = '{$id_cobro}'
				AND trabajo.id_tramite=0
				ORDER BY trabajo.fecha ASC";
		$lista_trabajos = new ListaTrabajos($sesion, '', $query);

		list($cobro_total_honorario_cobrable, $total_minutos_tmp, $detalle_trabajos) = $cobro->MontoHonorariosEscalonados($lista_trabajos);

		$cobro_valores['totales']['valor'] = $cobro_total_honorario_cobrable;
		$cobro_valores['totales']['duracion'] = ($total_minutos_tmp / 60);
		$cobro_valores['detalle'] = $slidingScales['detalle'];
		$cantidad_escalonadas = $cobro_valores['datos_escalonadas']['num'];

		$resumen_encabezado = "";

		$ws->write(++$filas, 6, __('Detalle Tarifa Escalonada'), $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, 6, $filas++, 10);

		for ($i = 1; $i <= $cantidad_escalonadas; $i++) {

			$detalle_escala = "";
			$detalle_escala .= $cobro->escalonadas[$i]['tiempo_inicial'] . " - ";
			$detalle_escala .=!empty($cobro->escalonadas[$i]['tiempo_final']) && $cobro->escalonadas[$i]['tiempo_final'] != 'NULL' ? $cobro->escalonadas[$i]['tiempo_final'] . " hrs. " : " " . __('más hrs') . " ";
			$detalle_escala .=!empty($cobro->escalonadas[$i]['id_tarifa']) && $cobro->escalonadas[$i]['id_tarifa'] != 'NULL' ? " " . __('Tarifa HH') . " " : " " . __('monto fijo') . " ";

			if (!empty($cobro->fields['esc' . $i . '_descuento']) && $cobro->fields['esc' . $i . '_descuento'] != 'NULL') {
				$detalle_escala .= " " . __('con descuento') . " {$cobro->fields['esc' . $i . '_descuento']}% ";
			}

			if (!empty($cobro->fields['esc' . $i . '_monto']) && $cobro->fields['esc' . $i . '_monto'] != 'NULL') {
				$query_glosa_moneda = "SELECT simbolo FROM prm_moneda WHERE id_moneda='{$cobro->escalonadas[$i]['id_moneda']}' LIMIT 1";
				$resp = mysql_query($query_glosa_moneda, $cobro->sesion->dbh) or Utiles::errorSQL($query_glosa_moneda, __FILE__, __LINE__, $cobro->sesion->dbh);
				list( $simbolo_moneda ) = mysql_fetch_array($resp);
				$monto_escala = number_format($cobro->escalonadas[$i]['monto'], $cobro_moneda->moneda[$cobro->escalonadas[$i]['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$detalle_escala .= ": $simbolo_moneda {$monto_escala}";
			}

			$ws->write(++$filas, 6, $detalle_escala, $CellFormat->get('normal', $i));
			$ws->mergeCells($filas, 6, $filas, 10);
		}

		if ($lang == 'es') {
			$resumen_detalle = __('Resumen Detalle Profesional');
		} else {
			$resumen_detalle = __('TIMEKEEPER SUMMARY');
		}

		$filas = $filas + 2;

		$ws->write($filas, 6, $resumen_detalle, $CellFormat->get('encabezado'));
		$ws->mergeCells($filas, 6, $filas++, 10);
		$celda_subtotales_horas = 0;
		$celda_subtotales_totales = 0;

		$esc = 0;
		while (++$esc <= $cantidad_escalonadas) {
			if (is_array($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'])) {
				if ($cobro_valores['datos_escalonadas'][$esc]['monto'] > 0) {
					$ws->write(++$filas, 6, "Escalón {$esc}: Monto Fijo " . $cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo'] . ' ' . $cobro_valores['datos_escalonadas'][$esc]['monto'], $CellFormat->get('encabezado'));
				} else {
					if ($cobro_valores['datos_escalonadas'][$esc]['descuento'] > 0) {
						$ws->write(++$filas, 6, "Escalón {$esc}: Tarifa HH con " . $cobro_valores['datos_escalonadas'][$esc]['descuento'] . '% de descuento', $CellFormat->get('encabezado'));
					} else {
						$ws->write(++$filas, 6, "Escalón {$esc}: Tarifa HH", $CellFormat->get('encabezado'));
					}
				}
				$ws->mergeCells($filas, 6, $filas++, 10);

				$ws->write(++$filas, 6, __('Nombre'), $CellFormat->get('titulo'));

				if ($cobro->fields['opc_ver_profesional_categoria']) {
					$ws->write($filas, 7, __($idioma->fields['codigo_idioma'] . '_CATEGORÍA'), $CellFormat->get('titulo'));
				}

				$ws->write($filas, 8, __('Hrs. Tarificadas'), $CellFormat->get('titulo'));
				$ws->write($filas, 9, __('TARIFA'), $CellFormat->get('titulo'));
				$ws->write($filas, 10, __($idioma->fields['codigo_idioma'] . '_IMPORTE'), $CellFormat->get('titulo'));

				foreach ($cobro_valores['detalle']['detalle_escalonadas'][$esc]['usuarios'] as $id_usuario => $usuarios) {
					if (round($usuarios['duracion']) > 0) {
						$ws->write(++$filas, 6, $usuarios['usuario'], $CellFormat->get('normal'));

						if ($cobro->fields['opc_ver_profesional_categoria']) {
							if ($lang == 'es') {
								$ws->write($filas, 7, $usuarios['categoria'], $CellFormat->get('normal'));
							} else {
								$ws->write($filas, 7, !empty($usuarios['categoria_lang']) ? $usuarios['categoria_lang'] : $usuarios['categoria'] , $CellFormat->get('normal'));
							}
						}

						$ws->write($filas, 8, Utiles::Decimal2GlosaHora(round($usuarios['duracion']/60, 2)), $CellFormat->get('moneda'));
						$ws->write($filas, 9, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo'] . ' ' . number_format($usuarios['tarifa'], $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', ''), $CellFormat->get('moneda'));
						$ws->write($filas, 10, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo'] . ' ' . number_format($usuarios['valor'], $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', ''), $CellFormat->get('moneda'));
					}
				}

				// Sub Total
				$filas++;
				$celda_subtotales_horas += $cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['duracion'];
				$celda_subtotales_totales += number_format($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['valor'], $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', '');
				$ws->write($filas, 6, __('Sub Total'), $CellFormat->get('total'));
				$ws->write($filas, 7, '', $CellFormat->get('total'));
				$ws->write($filas, 8, Utiles::Decimal2GlosaHora(round($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['duracion']/60, 2)), $CellFormat->get('moneda_total'));
				$ws->write($filas, 9, '', $CellFormat->get('total'));
				$ws->write($filas++, 10, $Moneda->currencyFormat($cobro_valores['detalle']['detalle_escalonadas'][$esc]['totales']['valor'], $cobro->fields['id_moneda']), $CellFormat->get('moneda_total'));
			}
		}

		// Sub Total
		$ws->write(++$filas, 6, __('Total'), $CellFormat->get('total'));
		$ws->write($filas, 7, '', $CellFormat->get('total'));
		$ws->write($filas, 8, Utiles::Decimal2GlosaHora(round($celda_subtotales_horas/60, 2)), $CellFormat->get('moneda_total'));
		$ws->write($filas, 9, '', $CellFormat->get('total'));
		$ws->write($filas++, 10, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo'] . ' ' . $celda_subtotales_totales, $CellFormat->get('moneda_total'));
	}

	$filas += 2;

	$query_num_usuarios = "SELECT DISTINCT id_usuario FROM trabajo WHERE id_cobro='{$cobro->fields['id_cobro']}'";
	$resp_num_usuarios = mysql_query($query_num_usuarios, $sesion->dbh) or Utiles::errorSQL($query_num_usuarios, __FILE__, __LINE__, $sesion->dbh);
	$num_usuarios = mysql_num_rows($resp_num_usuarios);

	/*
	 *  Dejar espacio para el resumen profesional si es necesario.
	 */

	if ((( $opc_ver_profesional && $mostrar_resumen_de_profesionales ) || $cobro->fields['opc_ver_profesional']) && ($cobro->fields['forma_cobro'] != 'ESCALONADA')) {
		$fila_inicio_resumen_profesional = $filas - 1;
		if ($num_usuarios > 0) {
			$filas += $num_usuarios + 7;
		} else {
			$filas += 3;
		}
	}

	$cont_asuntos = 0;

	# Sección para determinar si es necesario mostrar los asuntos que son cobrados sin horas
	$detalle_en_asuntos = FALSE;
	$criteria = new Criteria($sesion);
	$criteria->add_select('COUNT(*)', 'total')
			->add_from('tramite')
			->add_restriction(CriteriaRestriction::equals('id_cobro', $cobro->fields['id_cobro']))
			->add_restriction(CriteriaRestriction::in('codigo_asunto', $cobro->asuntos));

	try {
		$result = $criteria->run();
		$detalle_en_asuntos = ($result[0]['total'] == 0 ? FALSE : TRUE);
	} catch (Exception $e) {
		echo "Error: {$e} {$criteria->__toString()}";
	}

	$criteria = new Criteria($sesion);
	$criteria->add_select('COUNT(*)', 'total')
			->add_from('trabajo')
			->add_restriction(CriteriaRestriction::equals('id_cobro', $cobro->fields['id_cobro']))
			->add_restriction(CriteriaRestriction::equals('id_tramite', 0))
			->add_restriction(CriteriaRestriction::in('codigo_asunto', $cobro->asuntos));

	try {
		$result = $criteria->run();
		$detalle_en_asuntos = (($result[0]['total'] == 0 ? FALSE : TRUE) || $detalle_en_asuntos);
	} catch (Exception $e) {
		echo "Error: {$e} {$criteria->__toString()}";
	}

	$criteria = new Criteria($sesion);
	$criteria->add_select('COUNT(*)', 'total')
			->add_from('cta_corriente')
			->add_restriction(CriteriaRestriction::equals('id_cobro', $cobro->fields['id_cobro']))
			->add_restriction(CriteriaRestriction::in('codigo_asunto', $cobro->asuntos));

	try {
		$result = $criteria->run();
		$detalle_en_asuntos = (($result[0]['total'] == 0 ? FALSE : TRUE) || $detalle_en_asuntos);
	} catch (Exception $e) {
		echo "Error: {$e} {$criteria->__toString()}";
	}
	# Fin

	/*
	 *  Bucle sobre todos los asuntos de este cobro
	 */

	$cobro_tiene_trabajos = false;
	while ($cobro->asuntos[$cont_asuntos]) {
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($cobro->asuntos[$cont_asuntos]);
		$codigo_asunto_secundario = $asunto->CodigoACodigoSecundario($cobro->asuntos[$cont_asuntos]);

		$where_trabajos = " 1 ";
		if ($opc_ver_asuntos_separados || ($opc_mostrar_asuntos_cobrables_sin_horas && ! $detalle_en_asuntos)) {
			$where_trabajos .= " AND trabajo.codigo_asunto ='" . $asunto->fields['codigo_asunto'] . "' ";
		}
		if (!$opc_ver_columna_cobrable) {
			$where_trabajos .= " AND trabajo.visible = 1 ";
		}
		if (!$opc_ver_horas_trabajadas) {
			$where_trabajos .= " AND trabajo.duracion_cobrada != '00:00:00' ";
		}

		$query_cont_trabajos = "SELECT COUNT(*) FROM trabajo WHERE $where_trabajos AND id_cobro='" . $cobro->fields['id_cobro'] . "' AND id_tramite = 0";
		$resp_cont_trabajos = mysql_query($query_cont_trabajos, $sesion->dbh) or Utiles::errorSQL($query_cont_trabajos, __FILE__, __LINE__, $sesion->dbh);
		list($cont_trabajos) = mysql_fetch_array($resp_cont_trabajos);

		$where_tramites = " 1 ";
		if ($opc_ver_asuntos_separados || ($opc_mostrar_asuntos_cobrables_sin_horas && ! $detalle_en_asuntos)) {
			$where_tramites .= " AND tramite.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "' ";
		}

		$query_cont_tramites = "SELECT COUNT(*) FROM tramite WHERE $where_tramites AND id_cobro='{$cobro->fields['id_cobro']}'";
		$resp_cont_tramites = mysql_query($query_cont_tramites, $sesion->dbh) or Utiles::errorSQL($query_cont_tramites, __FILE__, __LINE__, $sesion->dbh);
		list($cont_tramites) = mysql_fetch_array($resp_cont_tramites);

		/*
		 * Si el asunto tiene trabajos y/o trámites imprime su resumen,
		 * sino muestra el asunto con un texto indicando que no existen cobros asociados al asunto.
		 */

		if (($cont_trabajos + $cont_tramites) > 0) {
			$cobro_tiene_trabajos = true;
			if ($opc_ver_asuntos_separados || ($opc_mostrar_asuntos_cobrables_sin_horas && ! $detalle_en_asuntos)) {
				/*
				 *	Indicar en una linea que los asuntos se muestran por separado y lluego
				 *	esconder la columna para que no ensucia la vista.
				 */

				$ws->write($filas, $col_fecha_ini, 'asuntos_separado', $CellFormat->get('encabezado'));
				$ws->write(++$filas, $col_abogado, $asunto->fields['codigo_asunto'], $CellFormat->get('encabezado'));
				$ws->setRow($filas - 1, 0, 0, 1);
				$ws->write($filas, $col_fecha_ini, __('Asunto') . ': ', $CellFormat->get('encabezado'));
				if (Conf::GetConf($sesion, 'CodigoSecundario')) {
					$ws->write($filas, $col_descripcion, $asunto->fields['codigo_asunto_secundario'] . ' - ' . $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
				} else {
					$ws->write($filas, $col_descripcion, $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
				}
				$filas += 2;
			}

			/*
			 *	Si existen trabajos imprime la tabla
			 */

			if ($cont_trabajos > 0) {

				/*
				 *	Buscar todos los trabajos de este asunto/cobro
				 */

				$query_trabajos = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
										trabajo.id_cobro,
										trabajo.id_trabajo,
										trabajo.codigo_asunto,
										trabajo.descripcion,
										trabajo.solicitante,
										trabajo.fecha,
										trabajo.id_usuario,
										trabajo.cobrable,
										prm_moneda.simbolo AS simbolo,
										asunto.codigo_cliente AS codigo_cliente,
										asunto.id_asunto AS id,
										trabajo.fecha_cobro AS fecha_cobro_orden,
										IF( trabajo.cobrable = 1, 'SI', 'NO') AS glosa_cobrable,
										trabajo.visible,
										username AS usr_nombre,
										usuario.username,
										CONCAT(usuario.nombre, ' ', usuario.apellido1, ' ', usuario.apellido2) as nombre_usuario,
										DATE_FORMAT(duracion, '%H:%i') AS duracion,
										DATE_FORMAT(duracion_cobrada, '%H:%i') AS duracion_cobrada,
										TIME_TO_SEC(duracion_cobrada) AS duracion_cobrada_decimal,
										DATE_FORMAT(duracion_retainer, '%H:%i') AS duracion_retainer,
										TIME_TO_SEC(duracion)/3600 AS duracion_horas,
										IF( trabajo.cobrable = 1, trabajo.tarifa_hh, '0') AS tarifa_hh,
										DATE_FORMAT(trabajo.fecha_cobro, '%e-%c-%x') AS fecha_cobro,
										asunto.codigo_asunto_secundario as codigo_asunto_secundario,
										prm_categoria_usuario.orden as orden,
										trabajo.monto_cobrado as monto_cobrado
									FROM trabajo
										JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
										LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
										JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
										LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
										LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
										LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario
										LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
									WHERE $where_trabajos AND trabajo.id_tramite=0 AND trabajo.id_cobro='{$cobro->fields['id_cobro']}'";

				if (Conf::GetConf($sesion, 'OrdenarPorCategoriaUsuario')) {
					$orden = " prm_categoria_usuario.orden ASC, usuario.id_usuario ASC, trabajo.fecha ASC, trabajo.descripcion ";
				} else {
					$orden = "trabajo.fecha, trabajo.descripcion";
				}

				$b1 = new Buscador($sesion, $query_trabajos, "Trabajo", $desde, '', $orden);
				$lista_trabajos = $b1->lista;

				/*
				 *  Encabezado de la tabla de trabajos
				 */

				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

				$ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

				$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

				if ($opc_ver_horas_trabajadas) {
					$ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

				if ($col_duracion_retainer) {
					$ws->write($filas, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}
				if ($opc_ver_columna_cobrable) {
					$ws->write($filas, $col_es_cobrable, Utiles::GlosaMult($sesion, 'cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}

				$ws->write($filas, $col_cobrable, Utiles::GlosaMult($sesion, 'horas_tarificadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
				$ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
				$ws->write($filas, $col_id_abogado, __('NO MODIFICAR ESTA COLUMNA'));

				if (!$primera_fila_primer_asunto) {
					$primera_fila_primer_asunto = $filas;
				}

				++$filas;
				$primera_fila_asunto = $filas + 1;
				$diferencia_proporcional = 0;
				$suma_total_inexacto = 0;
				$suma_total_exacto = 0;

				/*
				 *  Contenido de la tabla de trabajos
				 */

				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);

					$f = explode('-', $trabajo->fields['fecha']);
					$ws->write($filas, $col_fecha_dia, $f[2], $CellFormat->get('normal_centrado', $i));
					$ws->write($filas, $col_fecha_mes, $f[1], $CellFormat->get('normal_centrado', $i));
					$ws->write($filas, $col_fecha_anyo, $f[0], $CellFormat->get('normal_centrado', $i));

					if (!$opc_ver_asuntos_separados) {
						$sub = (Conf::GetConf($sesion, 'TipoCodigoAsunto') == 2) ? -3 : -4;
						$ws->write($filas, $col_asunto, substr($trabajo->fields['codigo_asunto_secundario'], $sub), $CellFormat->get('descripcion', $i));
					}
					$ws->write($filas, $col_descripcion, str_replace("\r", '', stripslashes($trabajo->fields['descripcion'])), $CellFormat->get('descripcion', $i));

					/*
					 *  Se guarda el nombre en una variable porque se usa en el detalle profesional.
					 */

					if ($cobro->fields['opc_ver_detalles_por_hora_iniciales'] || Conf::GetConf($sesion, 'UsarUsernameTodoelSistema') || $forzar_username === true) {
						$nombre = $trabajo->fields['username'];
					} else {
						$nombre = $trabajo->fields['nombre_usuario'];
					}

					if ($cobro->fields['opc_ver_profesional_iniciales'] || Conf::GetConf($sesion, 'UsarUsernameTodoelSistema') || $forzar_username === true) {
						$nombreresumen = $trabajo->fields['username'];
					} else {
						$nombreresumen = $trabajo->fields['nombre_usuario'];
					}

					$ws->write($filas, $col_abogado, $nombre, $CellFormat->get('normal', $i));
					$ws->write($filas, $col_solicitante, $trabajo->fields['solicitante'], $CellFormat->get('normal', $i));
					$duracion = $trabajo->fields['duracion'];
					list($h, $m) = split(':', $duracion);

					if ($ingreso_via_decimales) {
						$duracion = $h + $m / 60;
					} else {
						$duracion = $h / 24 + $m / (24 * 60);
					}
					if ($opc_ver_horas_trabajadas) {
						$ws->writeNumber($filas, $col_duracion_trabajada, $duracion, $CellFormat->get('tiempo', $i));
					}
					$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
					list($h, $m) = split(':', $duracion_cobrada);
					if ($trabajo->fields['glosa_cobrable'] == 'SI') {
						if ($ingreso_via_decimales) {
							$duracion_cobrada = $h + $m / 60;
						} else {
							$duracion_cobrada = $h / 24 + $m / (24 * 60);
						}
					} else {
						$duracion_cobrada = 0;
					}
					$ws->writeNumber($filas, $col_tarificable_hh, $duracion_cobrada, $CellFormat->get('tiempo', $i));

					if (!array_key_exists($cobro->fields['id_cobro'], $trabajos_duracion)) {
						$query_total_cobrable = "SELECT
									IF(SUM(TIME_TO_SEC(duracion_cobrada)/3600) <= 0, 1, SUM(TIME_TO_SEC(duracion_cobrada)/3600)) AS duracion_total
									FROM trabajo
								   WHERE id_cobro = '{$cobro->fields['id_cobro']}'
									 AND cobrable = 1";
						$resp_total_cobrable = mysql_query($query_total_cobrable, $sesion->dbh) or Utiles::errorSQL($query_total_cobrable, __FILE__, __LINE__, $sesion->dbh);
						list($xtotal_hh_cobrable) = mysql_fetch_array($resp_total_cobrable);
						$trabajos_duracion[$cobro->fields['id_cobro']] = $xtotal_hh_cobrable;
					} else {
						$xtotal_hh_cobrable = $trabajos_duracion[$cobro->fields['id_cobro']];
					}

					if ($xtotal_hh_cobrable > 0) {
						$factor = $cobro->fields['retainer_horas'] / $xtotal_hh_cobrable;
					} else {
						$factor = 1;
					}

					if ($cobro->fields['forma_cobro'] == 'PROPORCIONAL') {
						$duracion_retainer = $duracion_cobrada * $factor;
					} else {
						$duracion_retainer = $trabajo->fields['duracion_retainer'];
						list($h, $m, $s) = split(':', $duracion_retainer);
						if ($ingreso_via_decimales) {
							$duracion_retainer = $h + $m / 60 + $s / 3600;
						} else {
							$duracion_retainer = $h / 24 + $m / (24 * 60) + $s / (24 * 60 * 60);
						}
					}

					if ($duracion_retainer > $duracion_cobrada || $cobro->fields['forma_cobro'] == 'FLAT FEE') {
						$duracion_retainer = $duracion_cobrada;
					}

					if ($col_duracion_retainer) {
						$ws->writeNumber($filas, $col_duracion_retainer, $duracion_retainer, $CellFormat->get('tiempo', $i));
					}
					$duracion_tarificable = max(($duracion_cobrada - $duracion_retainer), 0);

					if ($opc_ver_columna_cobrable) {
						$ws->write($filas, $col_es_cobrable, $trabajo->fields['cobrable'] == 1 ? __("Sí") : __("No"), $CellFormat->get('normal', $i));
					}
					$ws->writeNumber($filas, $col_cobrable, $duracion_tarificable, $CellFormat->get('tiempo', $i));
					$ws->writeNumber($filas, $col_tarifa_hh, $trabajo->fields['tarifa_hh'], $CellFormat->get('moneda', $i));

					if ($cobro->fields['forma_cobro'] == 'ESCALONADA') {
						$ws->writeNumber($filas, $col_valor_trabajo, $trabajo->fields['monto_cobrado'], $CellFormat->get('moneda', $i));
					} else {

						if ($col_duracion_retainer) {
							$ws->writeFormula($filas, $col_valor_trabajo, "=MAX(" . ($ingreso_via_decimales ? "" : "24*" ) . "($col_formula_duracion_cobrable" . ($filas + 1) . "-$col_formula_duracion_retainer" . ($filas + 1) . ")*$col_formula_tarifa_hh" . ($filas + 1) . ";0)", $CellFormat->get('moneda', $i));
						} else {
							$ws->writeFormula($filas, $col_valor_trabajo, "=" . ($ingreso_via_decimales ? "" : "24*" ) . "$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda', $i));
						}
					}

					$ws->write($filas, $col_id_trabajo, $trabajo->fields['id_trabajo'], $CellFormat->get('normal', $i));
					$ws->write($filas, $col_id_abogado, $trabajo->fields['id_usuario'], $CellFormat->get('normal', $i));

					/*
					 *  Si hay que mostrar el detalle profesional guardamos una lista con los profesionales que trabajaron en este asunto.
					 */

					if ($opc_ver_profesional || $cobro->fields['opc_ver_profesional']) {
						if ($trabajo->fields['cobrable'] > 0 && !isset($detalle_profesional[$trabajo->fields['id_usuario']])) {
							$detalle_profesional[$trabajo->fields['id_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
							$detalle_profesional[$trabajo->fields['id_usuario']]['nombre'] = $nombreresumen;
						}
					}

					++$filas;
				}

				/*
				 *  Hay que eliminar la variable $diferencia_proporcional para que la proxima vez parte con zero
				 */

				$diferencia_proporcional = 0;

				/*
				 *  Guardar ultima linea para tener esta información por el resumen profesional
				 */

				$ultima_fila_ultimo_asunto = $filas - 1;

				/*
				 *  Totales de la tabla de trámites
				 */

				$ws->write($filas, $col_id_trabajo, __('Total'), $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_ini, '', $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_med, '', $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_fin, '', $CellFormat->get('total'));
				$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
				$ws->write($filas, $col_abogado, '', $CellFormat->get('total'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_asunto, '', $CellFormat->get('total'));
				}
				$ws->write($filas, $col_solicitante, '', $CellFormat->get('total'));

				if ($opc_ver_horas_trabajadas) {
					$ws->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$primera_fila_asunto:$col_formula_duracion_trabajada$filas)", $CellFormat->get('tiempo_total'));
				}

				if ($col_duracion_retainer) {
					$ws->writeFormula($filas, $col_duracion_retainer, "=SUM($col_formula_duracion_retainer$primera_fila_asunto:$col_formula_duracion_retainer$filas)", $CellFormat->get('tiempo_total'));
				}

				$ws->writeFormula($filas, $col_tarificable_hh, "=SUM($col_formula_duracion_cobrable$primera_fila_asunto:$col_formula_duracion_cobrable$filas)", $CellFormat->get('tiempo_total'));

				if ($opc_ver_columna_cobrable) {
					$ws->write($filas, $col_es_cobrable, '', $CellFormat->get('total'));
				}

				$ws->writeFormula($filas, $col_cobrable, "=SUM($col_formula_cobrable$primera_fila_asunto:$col_formula_cobrable$filas)", $CellFormat->get('tiempo_total'));
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
				$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo$primera_fila_asunto:$col_formula_valor_trabajo$filas)", $CellFormat->get('moneda_total'));
				$filas += 2;
			}

			if ($cont_tramites > 0) {

				/*
				 *	 Buscar todos los trámites de este cobro/asunto
				 */

				$where_tramites = " 1 ";
				if ($opc_ver_asuntos_separados) {
					$where_tramites .= " AND tramite.codigo_asunto ='" . $asunto->fields['codigo_asunto'] . "' ";
				}

				$query_tramites_si_trabajos = "SELECT
										tramite.id_tramite,
										tramite.fecha,
										glosa_tramite,
										tramite.id_moneda_tramite,
										username as usr_nombre,
										CONCAT( usuario.nombre, ' ', usuario.apellido1, ' ', usuario.apellido2 ) as nombre_usuario,
										tramite.descripcion,
										'1' as cantidad_repeticiones,
										tramite.trabajo_si_no,
										 tramite.tarifa_tramite as tarifa,
										tramite.id_cobro,
										tramite.codigo_asunto,
										cliente.glosa_cliente,
										prm_moneda.simbolo AS simbolo,
										cobro.id_moneda AS id_moneda_asunto,
										asunto.id_asunto AS id,
										asunto.codigo_asunto_secundario as codigo_asunto_secundario,
										cobro.estado AS estado_cobro,
										tramite.duracion,
										TIME_TO_SEC(duracion/3600 ) AS duracion_horas,
										DATE_FORMAT(cobro.fecha_cobro, '%e-%c-%x') AS fecha_cobro
									FROM tramite
										JOIN asunto ON tramite.codigo_asunto=asunto.codigo_asunto
										JOIN contrato ON asunto.id_contrato=contrato.id_contrato
										JOIN prm_moneda ON contrato.id_moneda_tramite=prm_moneda.id_moneda
										JOIN usuario ON tramite.id_usuario=usuario.id_usuario
										JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
										LEFT JOIN cliente ON asunto.codigo_cliente=cliente.codigo_cliente
										LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
									WHERE $where_tramites
										AND tramite.id_cobro='" . $cobro->fields['id_cobro'] . "'
										AND tramite.trabajo_si_no = 1
										AND tramite.fecha BETWEEN '".$cobro->fields['fecha_ini']."' AND '".$cobro->fields['fecha_fin']."'";

				$query_tramites_no_trabajos = "SELECT
											tramite.id_tramite,
											tramite.fecha,
											glosa_tramite,
											tramite.id_moneda_tramite,
											username as usr_nombre,
											CONCAT( usuario.nombre, ' ', usuario.apellido1, ' ', usuario.apellido2 ) as nombre_usuario,
											tramite.descripcion,
											COUNT(*) as cantidad_repeticiones,
											tramite.trabajo_si_no,
											SUM( tramite.tarifa_tramite ) as tarifa,
											tramite.id_cobro,
											tramite.codigo_asunto,
											cliente.glosa_cliente,
											prm_moneda.simbolo AS simbolo,
											cobro.id_moneda AS id_moneda_asunto,
											asunto.id_asunto AS id,
											asunto.codigo_asunto_secundario as codigo_asunto_secundario,
											cobro.estado AS estado_cobro,
											'0' as duracion,
											'0 ' AS duracion_horas,
											DATE_FORMAT(cobro.fecha_cobro, '%e-%c-%x') AS fecha_cobro
										FROM tramite
											JOIN asunto ON tramite.codigo_asunto=asunto.codigo_asunto
											JOIN contrato ON asunto.id_contrato=contrato.id_contrato
											JOIN prm_moneda ON contrato.id_moneda_tramite=prm_moneda.id_moneda
											JOIN usuario ON tramite.id_usuario=usuario.id_usuario
											JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
											LEFT JOIN cliente ON asunto.codigo_cliente=cliente.codigo_cliente
											LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
										WHERE $where_tramites
											AND tramite.id_cobro='" . $cobro->fields['id_cobro'] . "'
											AND tramite.trabajo_si_no = 0
											AND tramite.fecha BETWEEN '".$cobro->fields['fecha_ini']."' AND '".$cobro->fields['fecha_fin']."'
										GROUP BY tramite_tipo.glosa_tramite, tramite.descripcion, tramite.codigo_asunto, prm_moneda.simbolo";

				$query_tramites = "SELECT SQL_CALC_FOUND_ROWS * FROM (" . $query_tramites_si_trabajos . "  UNION ALL " . $query_tramites_no_trabajos . "  ) a ORDER BY fecha ASC";
				$lista_tramites = new ListaTramites($sesion, '', $query_tramites);

				/*
				 *  Encabezado de la tabla de trámites
				 */

				$filas++;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				$ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_abogado + 1, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}
				if ($cobro->fields['opc_ver_solicitante'] == 1) {
					$ws->write($filas, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				}

				$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				if ($opc_ver_horas_trabajadas) {
					$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('titulo'));
				}
				$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' || $cobro->fields['forma_cobro'] == 'FLAT FEE') {
					$ws->write($filas, $col_duracion_retainer, '', $CellFormat->get('titulo'));
				}

				$ws->write($filas, $col_cobrable, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('titulo'));
				$ws->write($filas, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
				++$filas;
				$fila_inicio_tramites = $filas + 1;

				/*
				 *  Contenido de la tabla de trámites
				 */

				for ($i = 0; $i < $lista_tramites->num; $i++) {
					$tramite = $lista_tramites->Get($i);
					list($h, $m, $s) = split(':', $tramite->fields['duracion']);
					if ($h + $m > 0) {
						if ($ingreso_via_decimales) {
							$duracion = $h + $m / 60 + $s / 3600;
						} else {
							if ($h > 9)
								$duracion = $h . ':' . $m;
							else
								$duracion = substr($h, 1, 1) . ':' . $m;
						}
					} else {
						$duracion = '-';
					}

					$ws->write($filas, $col_id_trabajo, $tramite->fields['id_tramite'], $CellFormat->get('normal', $i));
					$f = explode('-', $tramite->fields['fecha']);
					$ws->write($filas, $col_fecha_dia, $f[2], $CellFormat->get('normal', $i));
					$ws->write($filas, $col_fecha_mes, $f[1], $CellFormat->get('normal', $i));
					$ws->write($filas, $col_fecha_anyo, $f[0], $CellFormat->get('normal', $i));

					if ($cobro->fields['opc_ver_detalles_por_hora_iniciales'] || Conf::GetConf($sesion, 'UsarUsernameTodoelSistema') || $forzar_username === true) {
						$nombre = $tramite->fields['usr_nombre'];
					} else {
						$nombre = $tramite->fields['nombre_usuario'];
					}

					$ws->write($filas, $col_abogado, $nombre, $CellFormat->get('normal', $i));

					if ($cobro->fields['opc_ver_solicitante'] == 1) {
						$ws->write($filas, $col_abogado + 1, $trabajo->fields['solicitante'], $CellFormat->get('descripcion', $i));
					} else if (!$opc_ver_asuntos_separados) {
						$ws->write($filas, $col_abogado + 1, substr($tramite->fields['codigo_asunto'], -4), $CellFormat->get('descripcion', $i));
					}

					$ws->write($filas, $col_solicitante, '', $CellFormat->get('normal', $i));
					$descripcion_tramite_con_cantidad = "";

					if (strlen($tramite->fields['descripcion']) > 0) {
						$descripcion_tramite_con_cantidad = " - {$tramite->fields['descripcion']} ";
					} else {
						$descripcion_tramite_con_cantidad = $tramite->fields['cantidad_repeticiones'] > 1 ? " - " : "";
					}

					$descripcion_tramite_con_cantidad .= ( $tramite->fields['cantidad_repeticiones'] > 1 ? " ( {$tramite->fields['cantidad_repeticiones']} veces )" : '' );

					$ws->write($filas, $col_descripcion, $tramite->fields['glosa_tramite'] . ' ' . $descripcion_tramite_con_cantidad, $CellFormat->get('descripcion', $i));

					if ($opc_ver_horas_trabajadas) {
						$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('tiempo', $i));
					}

					$ws->write($filas, $col_tarificable_hh, $duracion, $CellFormat->get('tiempo', $i));
					if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' || $cobro->fields['forma_cobro'] == 'FLAT FEE') {
						$ws->write($filas, $col_duracion_retainer, '', $CellFormat->get('normal', $i));
					}

					$ws->write($filas, $col_cobrable, '', $CellFormat->get('normal', $i));

					if ($tramite->fields['id_moneda_tramite'] == $opc_moneda_total) {
						$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('normal', $i));
					} else {
						$ws->writeNumber($filas, $col_tarifa_hh, $tramite->fields['tarifa'], $CellFormat->get('moneda', $i));
					}

					$valor_trabajado = ($tramite->fields['tarifa'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']) / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
					$ws->writeNumber($filas, $col_valor_trabajo, $valor_trabajado, $CellFormat->get('moneda_gastos', $i));

					++$filas;
				}

				/*
				 *  Totales de los trámites:
				 */

				$ws->write($filas, $col_id_trabajo, __('Total'), $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_dia, '', $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_mes, '', $CellFormat->get('total'));
				$ws->write($filas, $col_fecha_anyo, '', $CellFormat->get('total'));
				$ws->write($filas, $col_abogado, '', $CellFormat->get('total'));

				if (!$opc_ver_asuntos_separados) {
					$ws->write($filas, $col_abogado + 1, '', $CellFormat->get('total'));
				}

				$ws->write($filas, $col_solicitante, '', $CellFormat->get('total'));
				$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));

				if ($opc_ver_horas_trabajadas && $col_duracion_trabajada != $col_descripcion + 1) {
					$ws->write($filas, $col_duracion_trabajada, '', $CellFormat->get('tiempo_total'));
				}

				$col_formula_tem = Utiles::NumToColumnaExcel($col_tarificable_hh);
				$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_tem$fila_inicio_tramites:$col_formula_tem$filas)", $CellFormat->get('tiempo_total'));
				if ($col_tarificable_hh != $col_descripcion + 1) {
					$ws->write($filas, $col_tarificable_hh, $tiempo_final, $CellFormat->get('tiempo_total'));
				}

				if ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' || $cobro->fields['forma_cobro'] == 'FLAT FEE') {
					$ws->write($filas, $col_duracion_retainer, '', $CellFormat->get('tiempo_total'));
				}

				$ws->write($filas, $col_cobrable, '', $CellFormat->get('total'));
				$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
				$col_formula_temp = Utiles::NumToColumnaExcel($col_valor_trabajo);
				$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_temp$fila_inicio_tramites:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));

				$filas += 2;
			}
		} else if ($opc_mostrar_asuntos_cobrables_sin_horas && ! $detalle_en_asuntos){
			$cobro_tiene_trabajos = true;
			$ws->write($filas, $col_fecha_ini, 'asuntos_separado', $CellFormat->get('encabezado'));
			$ws->write(++$filas, $col_abogado, $asunto->fields['codigo_asunto'], $CellFormat->get('encabezado'));
			$ws->setRow($filas - 1, 0, 0, 1);
			$ws->write($filas, $col_fecha_ini, __('Asunto') . ': ', $CellFormat->get('encabezado'));
			if (Conf::GetConf($sesion, 'CodigoSecundario')) {
				$ws->write($filas, $col_descripcion, $asunto->fields['codigo_asunto_secundario'] . ' - ' . $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
			} else {
				$ws->write($filas, $col_descripcion, $asunto->fields['glosa_asunto'], $CellFormat->get('encabezado'));
			}
			$filas += 2;
			$ws->write($filas++, $col_descripcion, 'No existen trabajos asociados a este asunto.', $CellFormat->get('encabezado2'));
			$filas += 2;
		}
		/*
		 *	Si se ven los asuntos por separado avanza al proximo
		 *	Si no salga del while
		 */

		if ($opc_ver_asuntos_separados || ($opc_mostrar_asuntos_cobrables_sin_horas && ! $detalle_en_asuntos)) {
			$cont_asuntos++;
		} else {
			break;
		}
	}

	if (count($cobro->asuntos) == 0 || !$cobro_tiene_trabajos) {
		$ws->write($filas++, $col_descripcion, 'No existen trabajos asociados a este cobro.', $CellFormat->get('instrucciones10'));
		$filas += 2;
	}

	if ((( $opc_ver_profesional || $cobro->fields['opc_ver_profesional'] ) && is_array($detalle_profesional)) && ($cobro->fields['forma_cobro'] != 'ESCALONADA')) {

		/*
		 *  Si el resumen va al principio cambiar el índice de las filas.
		 */

		if ($mostrar_resumen_de_profesionales) {
			$filas2 = $filas;
			$filas = $fila_inicio_resumen_profesional;
		}

		/*
		 *  Escribir las condiciones (ocultas) para poder usar DSUM en las fórmulas
		 */

		$filas+=2;
		$contador = 0;
		foreach ($detalle_profesional as $id => $data) {
			$ws->write($filas, $col_fecha_ini + $contador, __('NO MODIFICAR ESTA COLUMNA'));
			$ws->write($filas + 1, $col_fecha_ini + $contador, "$id");
			++$contador;
		}

		/*
		 *  Con esto se ocultan las filas con los id de los abogados.
		 */

		$ws->setRow($filas, 0, 0, 1);
		$ws->setRow($filas + 1, 0, 0, 1);

		/*
		 *  Encabezado
		 */

		$filas+=2;
		$ws->write($filas++, $col_descripcion, Utiles::GlosaMult($sesion, 'titulo', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
		$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'nombre', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

		if ($opc_ver_horas_trabajadas) {
			$ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'horas_trabajadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
			$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'horas_cobrables', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
		} else {
			$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'horas_trabajadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
		}

		if ($col_duracion_retainer) {
			$ws->write($filas, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'horas_retainer', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
		}

		$ws->write($filas, $col_cobrable, Utiles::GlosaMult($sesion, 'horas_tarificadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
		$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
		$ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'total', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $CellFormat->get('titulo'));
		++$filas;

		/*
		 *  Para las fórmulas en los totales
		 */

		$fila_inicio_detalle_profesional = $filas + 1;

		/*
		 *	Rellenar la tabla visible
		 *	Se basa en la fórmula de excel DSUM(datos; columna a sumar; condiciones)
		 *	Para usar esta formula hay que definir el tamaño de la matriz en cual se encuentran los datos
		 */

		if ($opc_ver_horas_trabajadas) {
			$inicio_datos = "$col_formula_duracion_trabajada" . ($primera_fila_primer_asunto + 1);
		} else {
			$inicio_datos = "$col_formula_duracion_cobrable" . ($primera_fila_primer_asunto + 1);
		}

		$fin_datos = "$col_formula_id_abogado" . ($ultima_fila_ultimo_asunto + 1);
		if (is_array($detalle_profesional)) {
			$contador = 0;
			foreach ($detalle_profesional as $id => $data) {
				if (Conf::GetConf($sesion, 'GuardarTarifaAlIngresoDeHora')) {
					$query_tarifa = "SELECT
										SUM( ( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) * tarifa_hh ) / SUM( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) as tarifa
									FROM trabajo
									WHERE id_cobro = '" . $cobro->fields['id_cobro'] . "'
										AND id_usuario = '$id'
										AND cobrable = 1";
					$resp_tarifa = mysql_query($query_tarifa, $sesion->dbh) or Utiles::errorSQL($query_tarifa, __FILE__, __LINE__, $sesion->dbh);
					list($data['tarifa']) = mysql_fetch_array($resp_tarifa);
				}
				$ws->write($filas, $col_descripcion, $data['nombre'], $CellFormat->get('normal', $contador));
				if ($opc_ver_horas_trabajadas) {
					$duracion_trabajada = Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo');
					$col1 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 4);
					$col2 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 3);
					$ws->writeFormula(
						$filas,
						$col_duracion_trabajada,
						"=DSUM({$inicio_datos}:{$fin_datos}; \"{$duracion_trabajada}\"; {$col1}:{$col2})",
						$CellFormat->get('tiempo', $contador)
					);
				}

				$duracion_cobrable = Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo');
				$col1 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 4);
				$col2 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 3);
				$ws->writeFormula(
					$filas,
					$col_tarificable_hh,
					"=DSUM({$inicio_datos}:{$fin_datos}; \"{$duracion_cobrable}\"; {$col1}:{$col2})",
					$CellFormat->get('tiempo', $contador)
				);
				if ($col_duracion_retainer) {
					$duracion_retainer = Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo');
					$col1 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 4);
					$col2 = Utiles::NumToColumnaExcel($col_fecha_ini + $contador) . ($fila_inicio_detalle_profesional - 3);
					$ws->writeFormula(
						$filas,
						$col_duracion_retainer,
						"=DSUM({$inicio_datos}:{$fin_datos}; \"{$duracion_retainer}\"; {$col1}:{$col2})",
						$CellFormat->get('tiempo', $contador)
					);
				}

				$formula_resta = '';
				if ($col_duracion_retainer) {
					$formula_resta = ' - $col_formula_duracion_retainer' . ($filas + 1);
				}
				$ws->writeFormula($filas, $col_cobrable, "=MAX($col_formula_duracion_cobrable" . ($filas + 1) . "{$formula_resta};0)", $CellFormat->get('tiempo', $contador));
				$ws->writeNumber($filas, $col_tarifa_hh, $data['tarifa'], $CellFormat->get('moneda', $contador));
				if ($col_duracion_retainer) {
					$ws->writeFormula($filas, $col_valor_trabajo, "=MAX(" . ($ingreso_via_decimales ? "" : "24*" ) . "$col_formula_duracion_cobrable" . ($filas + 1) . "-" . ($ingreso_via_decimales ? "" : "24*" ) . "$col_formula_duracion_retainer" . ($filas + 1) . ";0)*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda', $contador));
				} else {
					$ws->writeFormula($filas, $col_valor_trabajo, "=" . ($ingreso_via_decimales ? "" : "24*" ) . "$col_formula_duracion_cobrable" . ($filas + 1) . "*$col_formula_tarifa_hh" . ($filas + 1), $CellFormat->get('moneda', $contador));
				}
				++$filas;
				++$contador;
			}
		}
		// Fórmulas para los totales
		$ws->write($filas, $col_descripcion, __('Total'), $CellFormat->get('total'));
		if ($opc_ver_horas_trabajadas) {
			$ws->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$fila_inicio_detalle_profesional:$col_formula_duracion_trabajada$filas)", $CellFormat->get('tiempo_total'));
		}

		$ws->writeFormula($filas, $col_tarificable_hh, "=SUM($col_formula_duracion_cobrable$fila_inicio_detalle_profesional:$col_formula_duracion_cobrable$filas)", $CellFormat->get('tiempo_total'));
		if ($col_duracion_retainer) {
			$ws->write($filas, $col_duracion_retainer, "=SUM($col_formula_duracion_retainer$fila_inicio_detalle_profesional:$col_formula_duracion_retainer$filas)", $CellFormat->get('tiempo_total'));
		}
		//if($opc_ver_columna_cobrable)
		$ws->writeFormula($filas, $col_cobrable, "=SUM($col_formula_cobrable$fila_inicio_detalle_profesional:$col_formula_cobrable$filas)", $CellFormat->get('tiempo_total'));
		$ws->write($filas, $col_tarifa_hh, '', $CellFormat->get('total'));
		$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo$fila_inicio_detalle_profesional:$col_formula_valor_trabajo$filas)", $CellFormat->get('moneda_total'));
		if ($cobro->fields['forma_cobro'] == 'FLAT FEE') {
			$ws->write($filas, $col_valor_trabajo + 2, '', $CellFormat->get('normal'));
		}
		/*
		 *  Borrar el resumen para que el siguiente asunto parta de cero
		 */

		unset($detalle_profesional);

		/*
		 *  Si el resumen va al principio cambiar el índice de las filas.
		 */

		if ($mostrar_resumen_de_profesionales) {
			$filas = $filas2;
		}
	}

	/*
	 *  Borrar la variable primera_fila_primer asunto para que se define de nuevo en el siguiente cobro
	 */

	unset($primera_fila_primer_asunto);

	/*
	 * SI oculto la columna solicitante, debo correr la fecha de los gastos en 1 columna hacia la izq
	 */

	if (!$cobro->fields['opc_ver_solicitante']) {
		$offsetcolumna = 1;
	} else {
		$offsetcolumna = 0;
	}

	/*
	 * FFF guardo la fila de los subtotales, la voy a necesitar al final de la planilla
	 */

	$lineas_total_asunto_gasto = array();
	$formula_total_gg = array();

	if ($cont_gastos_cobro > 0 && $cobro->fields['opc_ver_gastos']) {



		if ($cobro->fields['opc_ver_asuntos_separados'] == 0) {

			/*
			 *  SECCION ENCABEZADOS GASTOS CUANDO NO ES SEPARADO POR GASTOS
			 */

			$filas++;
			if (Conf::GetConf($sesion, 'FacturaAsociada')) {

				if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {

					if ($cobro->fields['opc_ver_solicitante'] == 1) {
						$ws->write($filas++, $col_descripcion - $offsetcolumna - 4, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 4, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 1, __('Documento Asociado'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
						$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
					} else {
						$ws->write($filas++, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 1, __('Documento Asociado'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
						$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
					}

				} else {
					if ($cobro->fields['opc_ver_solicitante'] == 1) {
						$ws->write($filas++, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 1, __('Tipo Documento'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
						$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
					} else {
						$ws->write($filas++, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 1, __('Tipo Documento'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
						$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
					}
				}

			} else {

				if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {

					if ($cobro->fields['opc_ver_solicitante'] == 1) {

						$ws->write($filas++, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

					} else {

						$ws->write($filas++, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

					}
				} else {

					if ($cobro->fields['opc_ver_solicitante'] == 1) {

						$ws->write($filas++, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

					} else {

						$ws->write($filas++, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo'));
						$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo'));

					}
				}
			}

		}

		/*
		 *	INFORMACION CONTENIDA  EN LAS FILAS DE LA SECCION GASTO
		 */

		++$filas;
		$fila_inicio_gastos = $filas + 1;
		$_columnas_adicionales = '';
		$_select_solicitante = '';
		$_joins = '';
		$_join_solicitante = '';
		$offsetfactura = 0;
		$order = "ORDER BY fecha ASC";
		if ($cobro->fields['opc_ver_asuntos_separados']) {
			$order = " ORDER BY asunto.codigo_asunto ASC";
		}
		if (Conf::GetConf($sesion, 'FacturaAsociada')) {
			$_columnas_adicionales .= ', ptda.glosa, codigo_factura_gasto';
			$_joins .= ' LEFT JOIN prm_tipo_documento_asociado ptda ON ( cta_corriente.id_tipo_documento_asociado = ptda.id_tipo_documento_asociado ) ';
			$offsetfactura = 2;
		}

		if (Conf::GetConf($sesion, 'PrmGastos') && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
			$_columnas_adicionales .= ', pgg.glosa_gasto ';
			$_joins .= ' LEFT JOIN prm_glosa_gasto pgg ON ( cta_corriente.id_glosa_gasto = pgg.id_glosa_gasto ) ';
		}
		if ($cobro->fields['opc_ver_solicitante'] == 1) {
			$_select_solicitante .= "CONCAT_WS(' ', nombre, apellido1, apellido2) as solicitante,
							usuario.username as iniciales,";
			$_join_solicitante .= "LEFT JOIN usuario ON ( cta_corriente.id_usuario_orden = usuario.id_usuario)";
		}


		$query = 	"SELECT
						$_select_solicitante
						cta_corriente.ingreso,
						cta_corriente.egreso,
						cta_corriente.monto_cobrable,
						CAST( IF( fecha_factura IS NULL OR cta_corriente.fecha_factura = '' OR cta_corriente.fecha_factura = 00000000,
						cta_corriente.fecha,
						cta_corriente.fecha_factura) as DATE) as fecha,
						cta_corriente.id_moneda,
						asunto.codigo_asunto,
						asunto.glosa_asunto,
						cta_corriente.descripcion
						$_columnas_adicionales
					FROM cta_corriente
						JOIN asunto using (codigo_asunto)
						$_join_solicitante
						$_joins
					WHERE id_cobro='" . $cobro->fields['id_cobro'] . "'
					$order";

		$lista_gastos = new ListaGastos($sesion, '', $query);
		$columna_gastos_fecha = $col_descripcion - $offsetcolumna - 1;
		$columna_gastos_solicitante = $col_solicitante;
		$columna_gastos_descripcion = $col_descripcion;
		$columna_gastos_montos = $col_descripcion + 2 + $offsetfactura;
		$col_formula_gastos_montos = Utiles::NumToColumnaExcel($columna_gastos_montos);

		for ($i = 0; $i < $lista_gastos->num; $i++) {
			$gasto = $lista_gastos->Get($i);

			// CABECERAS PARA CADA ASUNTO
			if ($cobro->fields['opc_ver_asuntos_separados'] && ($gasto->fields['codigo_asunto'] != $codigo_asunto_anterior)) {

				if (!empty($codigo_asunto_anterior)) {
					/**
					 * SUBTOTAL CADA ASUNTO
					 * FFF guardo la fila de los subtotales, la voy a necesitar al final de la planilla
					 */
					$lineas_total_asunto_gasto[$glosa_asunto_anterior] = ($filas + 1);

					if (Conf::GetConf($sesion, 'FacturaAsociada')) {
						if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
							$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion, '', $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total', $i));
							$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
							$coltotal = $col_descripcion - $offsetcolumna - 2;

							$ws->writeFormula($filas, $col_descripcion - $offsetcolumna - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total', $i));
							$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total', $i));
						} else {
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion, '', $CellFormat->get('total', $i));
							$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
							$coltotal = $col_descripcion + 1;

							$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total', $i));
						}
					} else {
						if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
							$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion, '', $CellFormat->get('total', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total', $i));
							$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
							$coltotal = $col_descripcion - $offsetcolumna - 2;
						} else {
							if ( $cobro->fields['opc_ver_solicitante'] == 1){
								$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Total'), $CellFormat->get('total', $i));
								$ws->write($filas, $col_descripcion -1, '', $CellFormat->get('total', $i));
							} else {
								$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total', $i));
							}

							$ws->write($filas, $col_descripcion, '', $CellFormat->get('total', $i));
							$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
							$coltotal = $col_descripcion + 1;
						}
						if ( $cobro->fields['opc_ver_solicitante'] == 1){
							$ws->writeFormula($filas, $col_descripcion - $offsetcolumna + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total', $i));
						} else {
							$ws->writeFormula($filas, $col_descripcion - $offsetcolumna + 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total', $i));
						}
						$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
						$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total', $i));
					}
				}

				$filas += 2;

				if ($cobro->fields['opc_ver_solicitante'] == 1) {
					$ws->write($filas, $columna_gastos_fecha - $offsetcolumna - 1, $gasto->fields['codigo_asunto'], $CellFormat->get('encabezado', $i));
					$ws->write($filas, $columna_gastos_descripcion - $offsetcolumna - 1  , $gasto->fields['glosa_asunto'], $CellFormat->get('encabezado', $i));
				} else {
					$ws->write($filas, $columna_gastos_descripcion, $gasto->fields['glosa_asunto'], $CellFormat->get('encabezado', $i));
					$ws->write($filas, $columna_gastos_fecha, $gasto->fields['codigo_asunto'], $CellFormat->get('encabezado', $i));
				}

				$ws->mergeCells($filas, 1, $filas, 2);

				$filas++;
				if (Conf::GetConf($sesion, 'FacturaAsociada')) {
					if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
						if ($cobro->fields['opc_ver_solicitante'] == 1) {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 4, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 1, __('Documento Asociado'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						} else {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 3, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 1, __('Documento Asociado'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						}
					} else {
						if ($cobro->fields['opc_ver_solicitante'] == 1) {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 1, __('Tipo Documento'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						} else {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 1, __('Tipo Documento'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 3, $filas, $col_descripcion + 4);
							$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 4, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						}
					}
				} else {
					if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
						if ($cobro->fields['opc_ver_solicitante'] == 1) {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						} else {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - $offsetfecha, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Concepto'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));

							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						}

					} else {
						// GASTOS FILAS CUANDO VER ASNUTOS POR SEPARADOS ESTA ACTIVO
						if ($cobro->fields['opc_ver_solicitante'] == 1) {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						} else {
							if (!$cobro->fields['opc_ver_asuntos_separados']){
								$ws->write($filas++, $col_descripcion - $offsetcolumna - 2, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('encabezado', $i));
							}
							$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
							$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
							$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('titulo', $i));
							$ws->write($filas, $col_descripcion + 2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('titulo', $i));
						}
					}
				}

				$filas++;
				$fila_inicio_gastos = $filas + 1;
			}

			/*
			 *		SECCION FILAS GASTOS SIN ASUNTOS POR SEPARADO
			 */

			if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				if ($cobro->fields['opc_ver_solicitante'] == 1) {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 4, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$solicitante = $cobro->fields['opc_ver_profesional_iniciales'] ? $gasto->fields['iniciales'] : $gasto->fields['solicitante'];
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, $solicitante, $CellFormat->get('descripcion', $i));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, $gasto->fields['glosa_gasto'], $CellFormat->get('descripcion', $i));
					$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('descripcion', $i));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, $gasto->fields['glosa_gasto'], $CellFormat->get('descripcion', $i));
					$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion - $offsetcolumna - 1);
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('descripcion', $i));
				}
			} else {
				if ($cobro->fields['opc_ver_solicitante'] == 1){
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
					$solicitante = $cobro->fields['opc_ver_profesional_iniciales'] ? $gasto->fields['iniciales'] : $gasto->fields['solicitante'];
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, $solicitante, $CellFormat->get('descripcion', $i));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $CellFormat->get('normal'));
				}
			}

			$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $CellFormat->get('descripcion', $i));

			if (Conf::GetConf($sesion, 'FacturaAsociada')) {
				$ws->write($filas, $col_descripcion + 1, $gasto->fields['glosa'] . " N° " . $gasto->fields['codigo_factura_gasto'], $CellFormat->get('descripcion', $i));
				$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
				$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('descripcion', $i));
			}

			if ($gasto->fields['egreso']) {
				$ws->writeNumber($filas, $col_descripcion + 1 + $offsetfactura, $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos', $i));
			} else {
				$ws->writeNumber($filas, $col_descripcion + 1 + $offsetfactura, -$gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $CellFormat->get('moneda_gastos', $i));
			}

			$ws->mergeCells($filas, $col_descripcion + 1 + $offsetfactura, $filas, $col_descripcion + 2 + $offsetfactura);

			++$filas;

			$codigo_asunto_anterior = $gasto->fields['codigo_asunto'];
			$glosa_asunto_anterior = $gasto->fields['glosa_asunto'];
		}

		/*
		 *	Total de gastos
		 */

		$lineas_total_asunto_gasto[$gasto->fields['glosa_asunto']] = $filas + 1;

		if (Conf::GetConf($sesion, 'FacturaAsociada')) {
			if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				if ($cobro->fields['opc_ver_solicitante'] == 1){
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
					$coltotal = $col_descripcion - $offsetcolumna - 2;
					$ws->writeFormula($filas, $col_descripcion - $offsetcolumna - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion + 4);
					$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
					$coltotal = $col_descripcion - $offsetcolumna - 2;
					$ws->writeFormula($filas, $col_descripcion - $offsetcolumna - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion + 4);
					$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
				}
			} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 3);
					$coltotal = $col_descripcion + 1;
					$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 4);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
			}
		} else {
			if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
				if ($cobro->fields['opc_ver_solicitante'] == 1){
					$ws->write($filas, $col_descripcion - $offsetcolumna - 4, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
					$coltotal = $col_descripcion - $offsetcolumna - 2;
					$ws->writeFormula($filas, $col_descripcion - $offsetcolumna - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
					$coltotal = $col_descripcion - $offsetcolumna - 2;
					$ws->writeFormula($filas, $col_descripcion - $offsetcolumna - 2, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				}
			} else {
				if ($cobro->fields['opc_ver_solicitante'] == 1){
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
					$coltotal = $col_descripcion + 1;
					$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion + 1);
					$coltotal = $col_descripcion + 1;
					$ws->writeFormula($filas, $col_descripcion + 1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $CellFormat->get('moneda_gastos_total'));
					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				}
			}
		}

		if ($cobro->fields['opc_ver_asuntos_separados']) {
			$filas+=2;

			if (Conf::GetConf($sesion, 'FacturaAsociada')) {
				if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));

					$coltotal = $col_descripcion - $offsetcolumna - 2;
					$col_formula_temp = Utiles::NumToColumnaExcel($coltotal);
					foreach ($lineas_total_asunto_gasto as $label => $numfila) {
						$formula_total_gg[] = $col_formula_temp . $numfila;
					}
					$ws->writeFormula($filas, $coltotal, '=' . implode('+', $formula_total_gg), $CellFormat->get('moneda_gastos_total'));

					$ws->mergeCells($filas, $col_descripcion - $offsetcolumna - 2, $filas, $col_descripcion + 4);
					$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
				} else {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));

					$coltotal = $col_descripcion + 1;
					$col_formula_temp = Utiles::NumToColumnaExcel($coltotal);

					foreach ($lineas_total_asunto_gasto as $label => $numfila) {
						$formula_total_gg[] = $col_formula_temp . $numfila;
					}
					$ws->writeFormula($filas, $coltotal, '=' . implode('+', $formula_total_gg), $CellFormat->get('moneda_gastos_total'));

					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 4);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion + 3, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion + 4, '', $CellFormat->get('total'));
				}
			} else {
				if (Conf::GetConf($sesion, 'PrmGastos') && $cobro->fields['opc_ver_concepto_gastos'] && !(Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
					$ws->write($filas, $col_descripcion - $offsetcolumna - 3, __('Total'), $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					$ws->write($filas, $col_descripcion - $offsetcolumna - 2, '', $CellFormat->get('total'));
					$coltotal = $col_descripcion - $offsetcolumna - 2;

					$col_formula_temp = Utiles::NumToColumnaExcel($coltotal);
					foreach ($lineas_total_asunto_gasto as $label => $numfila) {
						$formula_total_gg[] = $col_formula_temp . $numfila;
					}
					$ws->writeFormula($filas, $coltotal, '=' . implode('+', $formula_total_gg), $CellFormat->get('moneda_gastos_total'));

					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				} else {
					if ($cobro->fields['opc_ver_solicitante'] == 1){
						$ws->write($filas, $col_descripcion - $offsetcolumna - 2, __('Total'), $CellFormat->get('total'));
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, '', $CellFormat->get('total'));
						$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					} else {
						$ws->write($filas, $col_descripcion - $offsetcolumna - 1, __('Total'), $CellFormat->get('total'));
						$ws->write($filas, $col_descripcion, '', $CellFormat->get('total'));
					}

					$coltotal = $col_descripcion + 1;

					$col_formula_temp = Utiles::NumToColumnaExcel($coltotal);
					foreach ($lineas_total_asunto_gasto as $label => $numfila) {
						$formula_total_gg[] = $col_formula_temp . $numfila;
					}
					$ws->writeFormula($filas, $coltotal, '=' . implode('+', $formula_total_gg), $CellFormat->get('moneda_gastos_total'));

					$ws->mergeCells($filas, $col_descripcion + 1, $filas, $col_descripcion + 2);
					$ws->write($filas, $col_descripcion + 2, '', $CellFormat->get('total'));
				}
			}
		}
	}

	/*
	 *	FIN BLOQUE GASTOS
	 */

	if (Conf::GetConf($sesion, 'UsarResumenExcel')) {

		/*
		 *  Resumen con información la forma de cobro y en caso de CAP una lista de los otros cobro que estan adentro de este CAP.
		 */

		$filas += 3;

		/*
		 *  Informacion general sobre el cobro:
		 */

		$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_fecha_ini + 1);
		$ws->write($filas, $col_fecha_ini, 'Información según ' . __('Cobro') . ':', $CellFormat->get('resumen_text_titulo'));
		$ws->write($filas++, $col_fecha_ini + 1, '', $CellFormat->get('resumen_text'));
		$ws->write($filas, $col_fecha_ini, 'Número ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->writeNumber($filas++, $col_fecha_ini + 1, $cobro->fields['id_cobro'], $CellFormat->get('numeros'));
		$ws->write($filas, $col_fecha_ini, 'Número Factura', $CellFormat->get('resumen_text_amarillo'));
		$ws->writeNumber($filas++, $col_fecha_ini + 1, $cobro->fields['documento'], $CellFormat->get('numeros_amarillo'));
		$ws->write($filas, $col_fecha_ini, 'Forma ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->write($filas++, $col_fecha_ini + 1, $cobro->fields['forma_cobro'], $CellFormat->get('resumen_text_derecha'));
		$ws->write($filas, $col_fecha_ini, 'Periodo ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->write($filas++, $col_fecha_ini + 1, $cobro->fields['fecha_ini'] . ' - ' . $cobro->fields['fecha_fin'], $CellFormat->get('resumen_text_derecha'));
		$ws->write($filas, $col_fecha_ini, 'Total ' . __('Cobro') . '', $CellFormat->get('resumen_text'));
		$ws->writeNumber($filas++, $col_fecha_ini + 1, $cobro->fields['monto'] * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'] + $cobro->fields['monto_gastos'], $CellFormat->get('moneda_resumen_cobro'));

		/*
		 *  Si la forma del cobro es cap imprime una lista de todos los cobros anteriores incluido en este CAP:
		 */

		if ($cobro->fields['forma_cobro'] == 'CAP') {
			$ws->write($filas, $col_fecha_ini, Utiles::GlosaMult($sesion, 'monto_cap_inicial', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('resumen_text'));
			$ws->writeNumber($filas++, $col_fecha_ini + 1, $contrato->fields['monto'], $CellFormat->get('moneda_monto'));
			$fila_inicial = $filas;

			$query_cob = "SELECT
							cobro.id_cobro,
							cobro.documento,
							((cobro.monto_subtotal-cobro.descuento) * cm2.tipo_cambio) / cm1.tipo_cambio
						FROM cobro
							JOIN contrato ON cobro.id_contrato=contrato.id_contrato
							JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
							JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
						WHERE cobro.id_contrato=" . $cobro->fields['id_contrato'] . "
							AND cobro.forma_cobro='CAP'";

			$resp_cob = mysql_query($query_cob, $sesion->dbh) or Utiles::errorSQL($query_cob, __FILE__, __LINE__, $sesion->dbh);
			while (list($id_cobro, $id_factura, $monto_cap) = mysql_fetch_array($resp_cob)) {
				$monto_cap = number_format($monto_cap, $moneda_monto->fields['cifras_decimales'], '.', '');
				$ws->write($filas, $col_fecha_ini, __('Factura N°') . ' ' . $id_factura, $CellFormat->get('resumen_text'));
				$ws->writeNumber($filas++, $col_fecha_ini + 1, $monto_cap, $CellFormat->get('moneda_monto'));
			}
			$ws->write($filas, $col_fecha_ini, Utiles::GlosaMult($sesion, 'monto_cap_restante', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $CellFormat->get('resumen_text'));
			$formula_cap_restante = "$col_formula_abogado$fila_inicial - SUM($col_formula_abogado" . ($fila_inicial + 1) . ":$col_formula_abogado$filas)";
			$ws->writeFormula($filas++, $col_fecha_ini + 1, "=IF($formula_cap_restante>0, $formula_cap_restante, 0)", $CellFormat->get('moneda_monto'));
		}
	}
	/*
	 *	Si el cobro es RETAINER o PROPORCIONAL vuelve la definición de las columnas al
	 *	estado normal para patir en cero en el siguiente cobro
	 */

	if (($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' || $cobro->fields['forma_cobro'] == 'FLAT FEE')) {
		unset($col_duracion_retainer);
		unset($col_formula_duracion_retainer);
		$col_cobrable--;
		$col_tarifa_hh--;
		$col_valor_trabajo--;
		$col_id_abogado--;
		$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
		$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
		$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
		$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
	}

	if (!$cobro->fields['opc_ver_solicitante']) {
		$ws->setColumn($col_solicitante, $col_solicitante, 10, $CellFormat->get('total'), 1);
	} else {
		$ws->setColumn($col_solicitante, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
	}
}

$query_hitos = "SELECT count(*) from cobro_pendiente where hito=1 and id_contrato='{$cobro->fields['id_contrato']}'";
$resp_hitos = mysql_query($query_hitos, $sesion->dbh) or Utiles::errorSQL($query_hitos, __FILE__, __LINE__, $sesion->dbh);
list($cont_hitos) = mysql_fetch_array($resp_hitos);

if ($cont_hitos > 0) {
	$query_hitos = "SELECT * FROM (
			SELECT
				id_cobro_pendiente,
				(
					SELECT COUNT(*) total FROM cobro_pendiente cp2
					WHERE cp2.id_contrato = cp.id_contrato
				) total,
				@a:=@a+1 as rowid,
				ROUND(IF(cbr.id_cobro = cp.id_cobro, @a, 0), 0) as thisid,
				DATE_FORMAT(CAST(IFNULL(cp.fecha_cobro,IFNULL(cbr.fecha_emision,'00000000')) as DATE),'%d/%m/%y') as fecha_hito,
				cp.descripcion,
				cp.observaciones,
				cp.monto_estimado,
				pm.simbolo,
				pm.codigo,
				pm.tipo_cambio,
				cp.id_contrato,
				cp.id_cobro,
				IFNULL(cbr.estado,'PENDIENTE') as estado,
				cbr.monto_thh,
				cbr.monto_thh_estandar,
				cbr.total_minutos,
				cp.fecha_cobro fc2
			FROM `cobro_pendiente` cp
			INNER JOIN contrato c USING (id_contrato)
			INNER JOIN prm_moneda pm USING (id_moneda)
			LEFT JOIN cobro cbr on cbr.id_contrato = c.id_contrato and cbr.id_cobro = cp.id_cobro
			INNER JOIN (SELECT @a:=0) FFF
			WHERE cp.hito = 1
		) hitos
	WHERE
		id_contrato = {$cobro->fields['id_contrato']} ";

	$resp_hitos = mysql_query($query_hitos, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$filas+=2;

	$ws->write($filas, $col_descripcion, 'Hitos', $CellFormat->get('encabezado'));
	$ws->setRow($filas, 20);
	$filas++;

	$ws->write($filas, $col_descripcion, __('Descripción'), $CellFormat->get('titulo_vcentrado'));
	$ws->write($filas, $col_descripcion + 1, __('Estado'), $CellFormat->get('titulo_vcentrado'));
	$ws->write($filas, $col_descripcion + 2, __('Fecha de Emisión'), $CellFormat->get('titulo_vcentrado'));
	$ws->write($filas, $col_descripcion + 3, __('Número de Horas'), $CellFormat->get('titulo_vcentrado'));
	$ws->write($filas, $col_descripcion + 4, __('Monto del Hito'), $CellFormat->get('titulo_vcentrado'));
	$ws->write($filas, $col_descripcion + 5, __('Valor Real Actualizado'), $CellFormat->get('titulo_vcentrado'));

	$totalhito = 0;
	$totalthh = 0;
	$totalminutos = 0;

	$criteria = new Criteria($sesion);
	$criteria->add_select('pm.simbolo', 'simbolo')
			->add_select('pm.cifras_decimales', 'cifras_decimales')
			->add_select('pm.glosa_moneda', 'glosa_moneda')
			->add_from('cobro_pendiente cp')
	 		->add_inner_join_with('contrato c', 'cp.id_contrato = c.id_contrato')
	 		->add_inner_join_with('prm_moneda pm', 'pm.id_moneda = c.id_moneda_monto')
			->add_restriction(CriteriaRestriction::equals('cp.hito', 1))
			->add_restriction(CriteriaRestriction::equals('cp.id_contrato', $cobro->fields['id_contrato']));
pr("$criteria");exit;
	$moneda_hitos = $criteria->run();
	$moneda_hitos = $moneda_hitos[0];

	if ($moneda_hitos['glosa_moneda'] == "Euro") {
		$simbolo_moneda = "EUR";
	} else {
		$simbolo_moneda = $moneda_hitos['simbolo'];
	}
	$cifras_decimales = $moneda_hitos['cifras_decimales'];

	if ($cifras_decimales) {
		$decimales = '.';
		while ($cifras_decimales-- > 0) {
			$decimales .= '0';
		}
	} else {
		$decimales = '';
	}

	$CellFormat->add('numeros_amarillo', array('Border' => 1, 'Align' => 'right', 'TextWrap' => '0', 'FgColor' => '37'));
	$formato_moneda_hito = & $wb->addFormat(array(
'Align' => 'right',
'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
	$formato_moneda_total_hito = & $wb->addFormat(array(
'Size' => 10,
'Align' => 'right',
'Bold' => 1,
'Top' => 1,
'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

	while ($fila_hitos = mysql_fetch_array($resp_hitos)) {
		$totalhito+=floatval($fila_hitos['monto_estimado']);
		$totalthh+=floatval($fila_hitos['monto_thh']);
		$monto_thh = ($fila_hitos['monto_thh'] == 0) ? '-' : $fila_hitos['monto_thh'];
		$fecha_hito = ($fila_hitos['fecha_hito'] == '00/00/00') ? '-' : $fila_hitos['fecha_hito'];
		$filas++;

		$ws->write($filas, $col_descripcion, $fila_hitos['descripcion'], $CellFormat->get('normal'));
		$ws->write($filas, $col_descripcion + 1, ucwords($fila_hitos['estado']), $CellFormat->get('normal'));
		$ws->write($filas, $col_descripcion + 2, $fecha_hito, $CellFormat->get('normal'));

		$totalminutos += $fila_hitos['total_minutos'];
		$horas_cobrables = floor($fila_hitos['total_minutos'] / 60);
		$minutos_cobrables = sprintf("%02d", $fila_hitos['total_minutos'] % 60);

		$ws->write($filas, $col_descripcion + 3, "$horas_cobrables:$minutos_cobrables", $CellFormat->get('normal'));
		$ws->write($filas, $col_descripcion + 4, $fila_hitos['monto_estimado'], $formato_moneda_hito);
		$ws->write($filas, $col_descripcion + 5, $monto_thh, $formato_moneda_hito);

		$filas++;
		$ws->write($filas, $col_descripcion, $fila_hitos['observaciones'], $CellFormat->get('observacion', $fila));
	}

	$filas++;
	$ws->write($filas, $col_descripcion, 'Total ', $CellFormat->get('total'));
	$ws->write($filas, $col_descripcion + 1, '', $CellFormat->get('total'));
	$ws->write($filas, $col_descripcion + 2, ' ', $CellFormat->get('total'));
	$horas_cobrables = floor($totalminutos / 60);
	$minutos_cobrables = sprintf("%02d", $totalminutos % 60);

	$ws->write($filas, $col_descripcion + 3, "$horas_cobrables:$minutos_cobrables", $CellFormat->get('total'));
	$ws->write($filas, $col_descripcion + 5, intval($totalthh), $formato_moneda_total_hito);
	$ws->write($filas, $col_descripcion + 4, $totalhito, $formato_moneda_total_hito);
}

/*
 *  fin bucle cobros
 */
if (isset($ws)) {
	// Se manda el archivo aquí para que no hayan errores de headers al no haber resultados.
	if (!$guardar_respaldo) {
		$wb->send('Resumen de ' . __('cobro') . '_' . $cobro->fields['id_cobro'] . '.xls');
	}
}

$wb->close();
