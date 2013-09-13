<?php

require_once dirname(__FILE__) . '/../conf.php';
$sesion = new Sesion(array('REP'));

for($x = 0; $x < $_POST['numero_agrupadores']; ++$x) {
	$agrupadores[] = $agrupador[$x];
}

$tipo_dato_comparado = $comparar ? $tipo_dato_comparado : null;
$limite = $limitar ? $limite : null;

########## VER GRAFICO ##########
if ($tipo_dato_comparado) {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' vs. ' . __($tipo_dato_comparado) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
} else {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
}

$datos_grafico = '';
$total = 0;


if (!$filtros_check) {
	$fecha_ultimo_dia = date('t', mktime(0, 0, 0, $fecha_mes, 5, $fecha_anio));
	$fecha_m = '' . $fecha_mes;
} else {
	$clientes = null;
	$usuarios = null;

	if ($check_clientes) {
		$clientes = $clientesF;
	}
	if ($check_profesionales) {
		$usuarios = $usuariosF;
	}
	if ($check_area_prof) {
		$areas_usuario = $areas;
	}
	if ($check_cat_prof) {
		$categorias_usuario = $categorias;
	}
	if (!$check_area_asunto) {
		$areas_asunto = null;
	}
	if (!$check_tipo_asunto) {
		$tipos_asunto = null;
	}
	if (!$check_estado_cobro) {
		$estado_cobro = null;
	}
	if (!$check_encargados) {
		$encargados = null;
	}
}

/* Se crea el reporte según el Input del usuario */
$reporte = new Reporte($sesion);
$dato = $tipo_dato;
$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
	'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
	'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');
$reporte->setFiltros($filtros);
$reporte->Query();
$r = $reporte->toBars();
/** FIN PRINCIPAL * */

/* * REPORTE COMPARADO* */
if ($tipo_dato_comparado) {
	$reporte_c = new Reporte($sesion);
	$dato = $tipo_dato_comparado;
	$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
		'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
		'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');
	$reporte_c->setFiltros($filtros);
	$reporte_c->setTipoDato($tipo_dato_comparado);

	$reporte_c->Query();

	$r_c = $reporte_c->toBars();
	$r = $reporte_c->fixBar($r, $r_c);
	$r_c = $reporte_c->fixBar($r_c, $r);

	if ($orden_barras_max2min) {
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
	if ($orden_barras_max2min) {
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
