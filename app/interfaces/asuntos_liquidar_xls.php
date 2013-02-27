<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Funciones.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/Cliente.php';

$sesion = new Sesion(array('ADM', 'COB'));
set_time_limit(400);
ini_set("memory_limit", "256M");
$where_cobro = ' 1 ';

$contrato = new Contrato($sesion);

// Esta variable se usa para que cada página tenga un nombre único.

$numero_pagina = 0;

//FILTROS DESDE GENERA_COBRO.PHP

if ($codigo_cliente_secundario) {
	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
	$codigo_cliente = $cliente->fields['codigo_cliente'];
}

if ($codigo_cliente) {
	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($codigo_cliente);
	$codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
}
$where = 1;
$where_subquery = 1;
$where_trabajo = "";

if ($activo)
	$where_subquery .= " AND co.activo = 'SI' ";
else
	$where_subquery .= " AND co.activo = 'NO' ";
if ($id_usuario)
	$where_subquery .= " AND co.id_usuario_responsable = '$id_usuario' ";
if ($forma_cobro)
	$where_subquery .= " AND co.forma_cobro = '$forma_cobro' ";

//1-2 = honorarios-gastos, 3 = mixtas
if ($tipo_liquidacion)
	$where_subquery .= " AND co.separar_liquidaciones = '" . ($tipo_liquidacion == '3' ? 0 : 1) . "' ";

if ($codigo_cliente)
	$where_subquery .= " AND cl.codigo_cliente = '$codigo_cliente' ";
if ($id_grupo_cliente)
	$where_subquery .= " AND cl.id_grupo_cliente = '$id_grupo_cliente' ";

if ($fecha_ini)
	$where_trabajo .= " AND DATE_FORMAT(trabajo.fecha,'%Y-%m-%d') >= DATE_FORMAT('$fecha_ini','%Y-%m-%d') ";
if ($fecha_fin)
	$where_trabajo .= " AND DATE_FORMAT(trabajo.fecha,'%Y-%m-%d') <= DATE_FORMAT('$fecha_fin','%Y-%m-%d') ";

if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	$cod_asunto .= "";
	$cod_asunto_secundario .= ",cl.codigo_cliente_secundario";
	$cod_cliente .= "";
	$cod_cliente_secundario .= ",a.codigo_asunto_secundario";
	$sel_cod_asunto_sec .=",codigo_asunto_secundario";
	$sel_cod_cli_sec .=",codigo_cliente_secundario";
} else {
	$cod_asunto .= ",codigo_asunto";
	$cod_asunto_secundario .= "";
	$cod_cliente .= ",codigo_cliente";
	$cod_cliente_secundario .= "";
	$sel_cod_asunto_sec .="";
	$sel_cod_cli_sec .="";
}

// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir

$query_asuntos_liquidar = "
	
							SELECT
								idcontrato 
								,glosa_cliente
								$cod_cliente
								$sel_cod_cli_sec
								,listado_asuntos
								,listado_codigo_asuntos
								$cod_asunto
								$sel_cod_asunto_sec
								,carta_honorarios
								,abogado
								,simbolo_moneda
								,idmoneda
								,ultimo_id_cobro
								,'' AS ultimo_monto_cobro -- SE CALCULA CON ProcesaCobroIdMoneda(idcobra,idmoneda) --
								,if(DATE_FORMAT(cobro.fecha_fin,'%Y-%m-%d')>DATE_FORMAT('0000-00-00','%Y-%m-%d'),DATE_FORMAT(cobro.fecha_fin,'%Y-%m-%d'),'') as fecha_ultimo_cobro
								,cobro.se_esta_cobrando AS glosa_ultimo_cobro
								,'' AS horas_castigadas
								,(SELECT SUM(TIME_TO_SEC(trabajo.duracion)/3600) FROM trabajo WHERE trabajo.codigo_asunto IN (listado_codigo_asuntos) AND (trabajo.id_cobro = '' OR trabajo.id_cobro IS NULL) AND trabajo.fecha > cobro.fecha_fin AND trabajo.cobrable = 1 $where_trabajo ) AS hh_val_trabajo
								,(SELECT SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600) FROM trabajo WHERE trabajo.codigo_asunto IN (listado_codigo_asuntos) AND (trabajo.id_cobro = '' OR trabajo.id_cobro IS NULL) AND trabajo.fecha > cobro.fecha_fin AND trabajo.cobrable = 1 $where_trabajo ) AS hh_val_cobrado
								,'' AS monto_total
							FROM (
								SELECT
								co.id_contrato as idcontrato
								,cl.glosa_cliente
								,GROUP_CONCAT(a.glosa_asunto SEPARATOR '\n') listado_asuntos
								,GROUP_CONCAT(a.codigo_asunto) listado_codigo_asuntos
								,co.observaciones AS carta_honorarios
								,CONCAT(LEFT(u.nombre,1),LEFT(u.apellido1,1),LEFT(u.apellido2,1)) AS abogado
								,mo.simbolo as simbolo_moneda
								,mo.id_moneda as idmoneda
								,a.codigo_asunto

								$cod_asunto_secundario
								$cod_cliente_secundario
								
								,(SELECT id_cobro FROM cobro WHERE cobro.id_contrato = co.id_contrato AND cobro.estado NOT IN ('CREADO','REVISION') ORDER BY fecha_fin DESC LIMIT 1 ) AS ultimo_id_cobro
								FROM asunto a
								LEFT JOIN contrato co ON a.id_contrato = co.id_contrato
								LEFT JOIN cliente cl ON cl.codigo_cliente = co.codigo_cliente
								LEFT JOIN usuario u ON u.id_usuario = co.id_usuario_responsable
								LEFT JOIN prm_moneda mo ON mo.id_moneda = co.id_moneda
								WHERE $where_subquery
								GROUP BY co.id_contrato
							)zz
							LEFT JOIN cobro ON cobro.id_cobro = ultimo_id_cobro
							GROUP BY idcontrato, ultimo_id_cobro";

//echo $query_asuntos_liquidar; exit;

$lista_suntos_liquidar = new ListaAsuntos($sesion, "", $query_asuntos_liquidar);
if ($lista_suntos_liquidar->num == 0)
	$pagina->FatalError('No existe información con este criterio');

$fecha_actual = date('Y-m-d');

// Crear y preparar planilla

$wb = new Spreadsheet_Excel_Writer();

// Enviar headers a la pagina

$wb->send(__('Asuntos por') . ' ' . __('cobrar') . ' ' . $fecha_actual . __('.xls'));

// Definir colores

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

// Crear formatos de celda

$formato_encabezado = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'underline' => 1,
			'Color' => 'black'));
$formato_titulo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Locked' => 1,
			'Bottom' => 1,
			'FgColor' => 35,
			'Color' => 'black',
			'TextWrap' => 1));
$formato_normal = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black'));
$formato_descripcion = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Color' => 'black',
			'TextWrap' => 1));
$formato_texto = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Format' => 'text',
			'Color' => 'black'));
$formato_tiempo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'NumFormat' => '[h]:mm'));
$formato_fecha_tiempo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'TextWrap' => 1));
$formato_total = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Top' => 1,
			'Color' => 'black'));

// Crear worksheet

$ws1 = & $wb->addWorksheet(__('Asuntos por') . ' ' . __('cobrar'));

$col_name = array_keys($lista_suntos_liquidar->Get()->fields);
$col_num = count($lista_suntos_liquidar->Get()->fields);

// Definimos visible, css, ancho y titulo de cada celda

$arr_col = array();
$col = 0;
for ($i = 0; $i < $col_num; ++$i) {
	
// ocultar celdas

	if (in_array($col_name[$i], array('idcontrato', 'listado_codigo_asuntos', 'idmoneda', 'ultimo_id_cobro', 'hh_val_trabajo', 'monto_total'))) {
		$arr_col[$col_name[$i]]['hidden'] = 'SI';
	} else {
		$arr_col[$col_name[$i]]['celda'] = $col++;
	}

	// ancho celdas

	if ($col_name[$i] == 'glosa_cliente') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 25);
	} else if ($col_name[$i] == 'codigo_cliente') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 14);
	} else if ($col_name[$i] == 'codigo_cliente_secundario') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 14);
	} else if ($col_name[$i] == 'listado_asuntos') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 25);
	} else if ($col_name[$i] == 'codigo_asunto') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 14);
	} else if ($col_name[$i] == 'codigo_asunto_secundario') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 14);
	} else if ($col_name[$i] == 'abogado') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 9);
	} else if ($col_name[$i] == 'simbolo_moneda') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 9);
	} else if ($col_name[$i] == 'carta_honorarios') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 25);
	} else if ($col_name[$i] == 'glosa_ultimo_cobro') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 25);
	} else if ($col_name[$i] == 'ultimo_monto_cobro') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	} else if ($col_name[$i] == 'fecha_ultimo_cobro') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	} else if ($col_name[$i] == 'horas_castigadas') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	} else if ($col_name[$i] == 'hh_val_cobrado') {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	} else {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	}

	// css celdas

	if ($col_name[$i] == 'hh_val_trabajo') {
		$arr_col[$col_name[$i]]['css'] = $formato_tiempo;
	} else if ($col_name[$i] == 'horas_castigadas') {
		$arr_col[$col_name[$i]]['css'] = $formato_tiempo;
	} else if ($col_name[$i] == 'fecha_ultimo_cobro') {
		$arr_col[$col_name[$i]]['css'] = $formato_fecha_tiempo;
	} else if ($col_name[$i] == 'hh_val_cobrado') {
		$arr_col[$col_name[$i]]['css'] = $formato_tiempo;
	} else if ($col_name[$i] == 'monto_total') {
		$arr_col[$col_name[$i]]['css'] = $formato_total;
	} else if ($col_name[$i] == 'glosa_cliente') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else if ($col_name[$i] == 'codigo_cliente') {
		$arr_col[$col_name[$i]]['css'] = $formato_texto;
	} else if ($col_name[$i] == 'codigo_cliente_secundario') {
		$arr_col[$col_name[$i]]['css'] = $formato_texto;
	} else if ($col_name[$i] == 'listado_asuntos') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else if ($col_name[$i] == 'codigo_asunto') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else if ($col_name[$i] == 'codigo_asunto_secundario') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else if ($col_name[$i] == 'carta_honorarios') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else if ($col_name[$i] == 'glosa_ultimo_cobro') {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else {
		$arr_col[$col_name[$i]]['css'] = $formato_normal;
	}

	// titulos celdas

	if ($col_name[$i] == 'glosa_cliente') {
		$arr_col[$col_name[$i]]['titulo'] = __('Cliente');
	} else if ($col_name[$i] == 'codigo_cliente') {
		$arr_col[$col_name[$i]]['titulo'] = __('Codigo Cliente.');
	} else if ($col_name[$i] == 'codigo_cliente_secundario') {
		$arr_col[$col_name[$i]]['titulo'] = __('Codigo Cliente Secundario.');
	} else if ($col_name[$i] == 'listado_asuntos') {
		$arr_col[$col_name[$i]]['titulo'] = __('Asunto');
	} else if ($col_name[$i] == 'codigo_asunto') {
		$arr_col[$col_name[$i]]['titulo'] = __('Codigo Asunto');
	} else if ($col_name[$i] == 'codigo_asunto_secundario') {
		$arr_col[$col_name[$i]]['titulo'] = __('Codigo Asunto Secundario');
	} else if ($col_name[$i] == 'abogado') {
		$arr_col[$col_name[$i]]['titulo'] = __('Abogado');
	} else if ($col_name[$i] == 'simbolo_moneda') {
		$arr_col[$col_name[$i]]['titulo'] = __('Moneda');
	} else if ($col_name[$i] == 'carta_honorarios') {
		$arr_col[$col_name[$i]]['titulo'] = __('Carta Honorarios');
	} else if ($col_name[$i] == 'glosa_ultimo_cobro') {
		$arr_col[$col_name[$i]]['titulo'] = __('Glosa Ult.') . ' ' . __('Cobro');
	} else if ($col_name[$i] == 'ultimo_monto_cobro') {
		$arr_col[$col_name[$i]]['titulo'] = __('Monto Ult.') . ' ' . __('Cobro');
	} else if ($col_name[$i] == 'fecha_ultimo_cobro') {
		$arr_col[$col_name[$i]]['titulo'] = __('Fecha Ult.') . ' ' . __('Cobro');
	} else if ($col_name[$i] == 'horas_castigadas') {
		$arr_col[$col_name[$i]]['titulo'] = __('Horas Castigadas');
	} else if ($col_name[$i] == 'hh_val_cobrado') {
		$arr_col[$col_name[$i]]['titulo'] = __('Horas Cobrables (') . __('WIP') . __(')');
	} else {
		$arr_col[$col_name[$i]]['titulo'] = str_replace('_', ' ', $col_name[$i]);
	}
}

unset($col);

//fila inicial

$fila = 0;

// Escribir encabezado reporte

$ws1->write($fila, 0, __('Asuntos por') . ' ' . __('cobrar'), $formato_encabezado);
$fila++;
$ws1->write($fila, 0, $fecha_actual, $formato_encabezado);
$fila++;
$fila++;

// Escribir titulos

for ($i = 0; $i < $col_num; ++$i) {
	if ($arr_col[$col_name[$i]]['hidden'] != 'SI') {
		$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['titulo'], $formato_titulo);
	}
}
$ws1->writeNote($fila, $arr_col['hh_val_cobrado']['celda'], 'Work in Progress');
$fila++;
$ws1->freezePanes(array($fila));

// Escribir filas

for ($j = 0; $j < $lista_suntos_liquidar->num; ++$j, ++$fila) {
	$proc = $lista_suntos_liquidar->Get($j);
	$ws1->write($fila, $col_glosa_cliente, $proc->fields[$col_name[$i]], $formato_normal);
	for ($i = 0; $i < $col_num; $i++) {
		if ($arr_col[$col_name[$i]]['hidden'] != 'SI') {
			if ($col_name[$i] == 'ultimo_monto_cobro') {
				$x_saldo_honorarios = 0;
				if ($proc->fields['ultimo_id_cobro'] > 0) {
					$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $proc->fields['ultimo_id_cobro']);
					$x_saldo_honorarios = $x_resultados['monto'][$proc->fields['idmoneda']];
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $x_saldo_honorarios, $arr_col[$col_name[$i]]['css']);
				}
			}
			if ($col_name[$i] == 'fecha_ultimo_cobro') {
				$x_fecha_ult_cobro = '';
				if ($proc->fields['ultimo_id_cobro'] > 0) {
					$x_fecha_ult_cobro = $proc->fields['fecha_ultimo_cobro'];
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $x_fecha_ult_cobro, $arr_col[$col_name[$i]]['css']);
				}
			} else if ($col_name[$i] == 'hh_val_cobrado') {
				$wip = $contrato->ProximoCobroEstimado('', Utiles::fecha2sql($proc->fields['fecha_ultimo_cobro']), $proc->fields['idcontrato'], true);
				if ($wip[0] == '0:00:00') {
					$hh_cobradas = '';
				} else {

					//Excel calcula el tiempo en días

					$hh_cobradas = ($wip[0] / 24);
					$hh_cobradas = number_format($hh_cobradas, 5, '.', '');
				}
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $hh_cobradas, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'horas_castigadas') {
				$wip = $contrato->ProximoCobroEstimado('', Utiles::fecha2sql($proc->fields['fecha_ultimo_cobro']), $proc->fields['idcontrato'], true);
				if ($wip[2] == '0:00:00') {
					$hh_castigadas = '';
				} else {

					//Excel calcula el tiempo en días

					$hh_castigadas = ($wip[2] / 24);
					$hh_castigadas = number_format($hh_castigadas, 5, '.', '');
				}
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $hh_castigadas, $arr_col[$col_name[$i]]['css']);
			} else if ($arr_col[$col_name[$i]]['css'] == $formato_texto) {
				$ws1->writeString($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
			} else {
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
			}
			
		}
	}
}
$wb->close();
exit;
?>
