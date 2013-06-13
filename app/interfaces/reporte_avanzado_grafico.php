<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('REP'));

$agrupadores = explode('-', $vista);

########## VER GRAFICO ##########
if ($tipo_dato_comparado) {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' vs. ' . __($tipo_dato_comparado) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
} else {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
}

$datos_grafico = '';
$total = 0;


/** REPORTE PRINCIPAL * */
$reporte = new Reporte($sesion);
/* USUARIOS */
$users = is_array($usuarios) ? $usuarios : explode(",", $usuarios);
if (!is_array($users)) {
	$users = array($users);
}
foreach ($users as $usuario) {
	if ($usuario) {
		$reporte->addFiltro('usuario', 'id_usuario', $usuario);
	}
}

/* ENCARGADOS */
$encargados = is_array($usuarios) ? $en_com : explode(",", $en_com);
if (!is_array($encargados)) {
	$encargados = array($encargados);
}
foreach ($encargados as $encargado) {
	if ($encargado) {
		$reporte->addFiltro('contrato', 'id_usuario_responsable', $encargado);
	}
}
/* ESTADOS */
$estados = is_array($es_cob) ? $es_cob : explode(",", $es_cob);
if (!is_array($estados)) {
	$estados = array($estados);
}
foreach ($estados as $estado) {
	if ($estado) {
		$reporte->addFiltro('cobro', 'estado', $estado);
	}
}

/* CLIENTES */
$clients = is_array($clientes) ? $clientes : explode(",", $clientes);
if (!is_array($clients)) {
	$clients = array($clients);
}
foreach ($clients as $cliente) {
	if ($cliente) {
		$reporte->addFiltro('cliente', 'codigo_cliente', $cliente);
	}
}

/* AREAS */
$areas = is_array($areas_asunto) ? $areas_asunto : explode(",", $areas_asunto);
if (!is_array($areas)) {
	$areas = array($areas);
}
foreach ($areas as $area) {
	if ($area) {
		$reporte->addFiltro('asunto', 'id_area_proyecto', $area);
	}
}

/* AREAS USUARIO */
$areas_usuario = is_array($areas_usuario) ? $areas_usuario : explode(",", $areas_usuario);
if (!is_array($areas_usuario)) {
	$areas_usuario = array($areas_usuario);
}
foreach ($areas_usuario as $area_usuario) {
	if ($area_usuario) {
		$reporte->addFiltro('usuario', 'id_area_usuario', $area_usuario);
	}
}

/* CATEGORIAS USUARIO */
$categorias_usuario = is_array($categorias_usuario) ? $categorias_usuario : explode(",", $categorias_usuario);
if (!is_array($categorias_usuario)) {
	$categorias_usuario = array($categorias_usuario);
}
foreach ($categorias_usuario as $categoria_usuario) {
	if ($categoria_usuario) {
		$reporte->addFiltro('usuario', 'id_categoria_usuario', $categoria_usuario);
	}
}

/* TIPOS */
$tipos = is_array($tipos_asunto) ? $tipos_asunto : explode(",", $tipos_asunto);
if (!is_array($tipos)) {
	$tipos = array($tipos);
}
foreach ($tipos as $tipo) {
	if ($tipo) {
		$reporte->addFiltro('asunto', 'id_tipo_asunto', $tipo);
	}
}


$reporte->id_moneda = $id_moneda;
$reporte->addRangoFecha($fecha_ini, $fecha_fin);
$reporte->setTipoDato($tipo_dato);
$reporte->setVista($vista);
$reporte->setProporcionalidad($prop);

if ($campo_fecha) {
	$reporte->setCampoFecha($campo_fecha);
}

$reporte->Query();

$r = $reporte->toBars();
/** FIN PRINCIPAL * */
/* * REPORTE COMPARADO* */
if ($tipo_dato_comparado) {

	$reporte->setTipoDato($tipo_dato_comparado);
	$reporte->Query();

	$r_c = $reporte->toBars();
	$r = $reporte->fixBar($r, $r_c);
	$r_c = $reporte->fixBar($r_c, $r);

	if ($orden == 'max2min') {
		arsort($r);
	}

	$datos_grafico .= "&compara=1&unidadC=" . $tipo_dato_comparado;

	$valores_comparados = array();
	foreach ($r as $id => $fila) {
		if (is_array($fila)) {
			$valores_comparados[] = str_replace(',', '.', $r_c[$id]['valor']);
		}
	}
} else {
	if ($orden == 'max2min') {
		arsort($r);
	}
}
/* * FIN COMPARADO* */

$existen_datos = false;
$valores = array();
$labels = array();
foreach ($r as $id => $fila)
	if (is_array($fila)) {
		$labels[] = urlencode($fila['label']);
		$valores[] = str_replace(',', '.', $fila['valor']);
		$existen_datos = true;
	}

if ($limite) {
	$lim_labels = array();
	$lim_valores = array();
	if ($tipo_dato_comparado) {
		$lim_valores_comparados = array();
	}

	if ($limite > 0 && $limite < 1000)
		for ($i = 0; ($i < $limite && $i < sizeof($labels)); $i++) {
			$lim_labels[] = $labels[$i];
			$lim_valores[] = $valores[$i];
			if ($tipo_dato_comparado) {
				$lim_valores_comparados[] = $valores_comparados[$i];
			}
		}

	if ($agrupar)
		if ($limite < sizeof($labels)) {
			$valor_otros = 0;
			for ($i = $limite; $i < sizeof($labels); $i++) {
				$valor_otros += $valores[$i];
			}
			$lim_labels[] = "Otros";
			$lim_valores[] = $valor_otros;
		}

	$valores = $lim_valores;
	$labels = $lim_labels;
	if ($tipo_dato_comparado) {
		$valores_comparados = $lim_valores_comparados;
	}
}

foreach ($labels as $label) {
	$datos_grafico .= "&n[]=" . ($label);
}
$datos_grafico .= "&t=" . implode(',', $valores);
if ($tipo_dato_comparado) {
	$datos_grafico .= "&c=" . implode(',', $valores_comparados);
}


$html_info = '<style type="text/css">
		@media print {
		div#print_link {
		display: none;
		}
		}
		</style>';

switch ($tipo_grafico) {
	case 'barras': {
			$datos_grafico .= "&p=" . $r['promedio'];
			$url = "graficos/barras_reporte_avanzado.php?titulo=" . $titulo_reporte . $datos_grafico . "&unidad=" . $tipo_dato . "&moneda=" . $id_moneda;
			$elemento = "<iframe name=planilla id=planilla src='$url'  frameborder=0 width=720px height=460px></iframe>";
			break;
		}
	case 'dispersion': {
			$url = "graficos/dispersion_reporte_avanzado.php?titulo=" . $titulo_reporte . $datos_grafico . "&unidad=" . $tipo_dato . "&moneda=" . $id_moneda;
			$elemento .= "<iframe name=planilla id=planilla src='$url'  frameborder=0 width=720px height=560px></iframe> ";
			break;
		}
	case 'circular': {
			$url = "graficos/circular_reporte_avanzado.php?titulo=" . $titulo_reporte . $datos_grafico . "&unidad=" . $tipo_dato . "&moneda=" . $id_moneda;
			$elemento .= "<img name=planilla id=planilla src='$url' alt='' />";
			break;
		}
}


$html_info .= "<div id='print_link' align=right>";
$html_info .= "<a href='javascript:void(0)' onclick='window.print()'>" . __('Imprimir') . "</a> | ";
$html_info .= "<a href='$url" . '&imp_pdf=1' . "'>" . __('Guardar PDF') . "</a>";
$html_info .= "</div>";

$html_info .= $elemento;

if (!$existen_datos) {
	$html_info = " <h2> No se existen Datos del Tipo elegido para este Periodo. </h2> ";
}

echo $html_info;
