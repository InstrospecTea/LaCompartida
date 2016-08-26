<?php
require_once dirname(__FILE__).'/../../conf.php';

$sesion = new Sesion();
$Criteria = new Criteria($sesion);

$total_tiempo = 0;
$where = '1';
$join = '';
$where_comparada = '1';
$join_comparada = '';
$duracion_query_comparada = '';

//segun el tipo de duracion se ven los parametros de la duracion

if ($tipo_duracion == 'trabajada') {
	$duracion_query = "trabajo.duracion";
}

if ($tipo_duracion == 'cobrable') {
	$duracion_query = "trabajo.duracion_cobrada";
	$where .= " AND trabajo.cobrable=1";
}

if ($tipo_duracion == 'no_cobrable') {
	$duracion_query = "trabajo.duracion";
	$where .= " AND trabajo.cobrable=0";
}

if ($tipo_duracion == 'cobrada') {
	$duracion_query = "trabajo.duracion_cobrada";
	$join = "INNER JOIN cobro ON cobro.id_cobro=trabajo.id_cobro";
	$where .= " AND trabajo.cobrable=1 AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION'";
}

if ($comparar == '1') {
	$duracion_query_comparada = "trabajo.duracion";

	if ($tipo_duracion_comparada == 'cobrable') {
		$where_comparada .= " AND trabajo.cobrable=1";
	}

	if ($tipo_duracion_comparada == 'no_cobrable') {
		$where_comparada .= " AND trabajo.cobrable=0";
	}

	if ($tipo_duracion_comparada == 'cobrada') {
		$duracion_query_comparada = "trabajo.duracion_cobrada";
		$join_comparada = "INNER JOIN cobro ON cobro.id_cobro=trabajo.id_cobro";
		$where_comparada .= " AND trabajo.cobrable=1 AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION'";
	}
}

//segun el tipo de reporte se ven los parametros del query
if ($tipo_reporte != 'trabajos_por_estudio') {

	//si no es por estudio se agrupa y se acota por cliente o por empleado
	if ($tipo_reporte == 'trabajos_por_cliente') {

		if (!empty($codigo_cliente)) {
			$where .= " AND cliente.codigo_cliente=$codigo_cliente";
			$where_comparada .= " AND cliente.codigo_cliente=$codigo_cliente";
		}

		if (is_array($usuarios)) {
			$lista_usuarios = join("','", $usuarios);
			$where_usuario = " AND trabajo.id_usuario IN ('" . $lista_usuarios . "')";
		} else {
			$where_usuario = '';
		}
	}

	if ($tipo_reporte == 'trabajos_por_empleado') {

		if (!empty($id_usuario)) {
			$where .= " AND usuario.id_usuario=$id_usuario";
			$where_comparada .= " AND usuario.id_usuario=$id_usuario";
		}

		if (is_array($clientes)) {
			$lista_clientes = join("','", $clientes);
			$where_cliente = "	AND asunto.codigo_cliente IN ('" . $lista_clientes . "')";
		} else {
			$where_cliente = '';
		}
	}

	$nombre_general = "meses_duracion.nombre,";
} else {

	if (is_array($usuarios)) {
		$lista_usuarios = join("','", $usuarios);
		$where_usuario = " AND trabajo.id_usuario IN ('" . $lista_usuarios . "')";
	} else {
		$where_usuario = '';
	}

	if (is_array($clientes)) {
		$lista_clientes = join("','", $clientes);
		$where_cliente = "	AND asunto.codigo_cliente IN ('" . $lista_clientes . "')";
	} else {
		$where_cliente = '';
	}
}

$query_tpl = "SELECT
            DATE_FORMAT(fechas.mes,'%%Y-%%m') as mes_anio,
            IFNULL(meses_duracion.duracion,0) as duracion_mes
            FROM ( SELECT
                        DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),
                        INTERVAL n2.num+n1.num MONTH) AS 'mes'
                    FROM
                        (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) n1,
                        (SELECT 0 AS num UNION ALL SELECT 10 UNION ALL SELECT 20 UNION ALL SELECT 30 UNION ALL SELECT 40 UNION ALL SELECT 50 UNION ALL SELECT 60 UNION ALL SELECT 70 UNION ALL SELECT 80 UNION ALL SELECT 90) n2
                    WHERE
                        DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),INTERVAL n2.num+n1.num MONTH) >='%s'
                        AND DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),INTERVAL n2.num+n1.num MONTH) <='%s'
                        ORDER BY 'mes') as fechas

                    LEFT JOIN ( SELECT
                                    SUM( TIME_TO_SEC( %s ) ) /3600 AS duracion, DATE_FORMAT( trabajo.fecha,  '%%Y-%%m-01' ) AS periodo
                                FROM trabajo
                                JOIN usuario ON usuario.id_usuario=trabajo.id_usuario
                                JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
                                JOIN cliente ON cliente.codigo_cliente = asunto.codigo_cliente
                                %s
                                WHERE %s %s %s AND trabajo.fecha
                                BETWEEN '%s'
                                AND '%s'
                                GROUP BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha ) %s
                                ORDER BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha )
                    ) as meses_duracion ON meses_duracion.periodo=fechas.mes
            ORDER BY mes_anio ASC";

$query = sprintf($query_tpl, $fecha_desde, $fecha_hasta, $duracion_query, $join, $where, $where_usuario, $where_cliente, $fecha_desde, $fecha_hasta, $groupby);

$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

if ($comparar) {
	$query_comparada = sprintf($query_tpl, $fecha_desde, $fecha_hasta, $duracion_query_comparada, $join_comparada, $where_comparada, $where_usuario, $where_cliente, $fecha_desde, $fecha_hasta, $groupby);
	$resp_comparada = mysql_query($query_comparada, $sesion->dbh) or Utiles::errorSQL($query_comparada, __FILE__, __LINE__, $sesion->dbh);
}

for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
	$periodo[$i] = $fila['mes_anio'];
	$duracion[$i] = $fila['duracion_mes'];
	$total_duracion += $fila['duracion_mes'];
}

if ($i > 0) {
	$duracion[$i] = strval($total_duracion / $i);
	$periodo[$i] = 'Promedio Periodo';
}

if ($comparar) {

	for ($k = 0; $fila = mysql_fetch_assoc($resp_comparada); $k++) {
		$periodo_comparada[$k] = $fila['mes_anio'];
		$duracion_comparada[$k] = $fila['duracion_mes'];
		$total_duracion_comparada += $fila['duracion_mes'];
	}

	if ($k > 0) {
		$duracion_comparada[$k] = strval($total_duracion_comparada / $k);
	}
}

if ($tipo_reporte == 'trabajos_por_empleado') {
	$Criteria
		->add_select("CONCAT_WS(' ',nombre, apellido1, apellido2)", 'nombre')
		->add_from('usuario')
		->add_restriction(
				CriteriaRestriction::equals('id_usuario', $id_usuario)
			)
		->add_limit(1, 0);

	try{
		$nombre = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	$title = __('Horas trabajadas por ') . str_replace('-', ' ', $nombre[0]['nombre']);
}

if ($tipo_reporte == 'trabajos_por_cliente') {
	$Criteria
		->add_select("glosa_cliente", 'nombre')
		->add_from('cliente')
		->add_restriction(
			CriteriaRestriction::equals('codigo_cliente', $codigo_cliente)
		)
		->add_limit(1, 0);

	try{
		$nombre = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	$title = __('Horas trabajadas para ') . str_replace('-', ' ', $nombre[0]['nombre']);
}

if ($tipo_reporte == 'trabajos_por_estudio') {
	$title = __('Horas trabajadas');
}

function fixNumber(&$valor) {
	$valor = number_format($valor,2,'.','');
	return $valor;
}

$titulo_tipo = array(
	'trabajada' => __("Horas trabajadas"),
	'cobrable' => __("Horas cobrables"),
	'no_cobrable' => __("Horas no cobrables"),
	'cobrada' => __("Horas No cobradas")
);

array_walk($duracion,'fixNumber');

$y_axes = [];
$grafico = new TTB\Graficos\Grafico();
if (is_null($duracion)) {
	echo $grafico->getJsonError(3, 'No exiten datos para generar el gr�fico');
	return;
}
$dataset = new TTB\Graficos\Dataset();

$dataset->setLabel($titulo_tipo[$tipo_duracion])
	->setYAxisID('y-axis-1')
	->setData($duracion);

$grafico->setNameChart($title)
	->addDataset($dataset)
	->addLabels($periodo);

$y_axes[] = [
	'type' => 'linear',
	'display' => true,
	'position' => 'left',
	'id' => 'y-axis-1',
	'gridLines' => [
		'display' => false
	],
	'labels' => [
		'show' => true
	],
	'ticks' => [
		'beginAtZero' => true
	]
];

if ($comparar) {
	$dataset_comparar = new TTB\Graficos\Dataset();

	array_walk($duracion_comparada,'fixNumber');

	$dataset_comparar->setLabel($titulo_tipo[$tipo_duracion_comparada])
		->setBackgroundColor(39, 174, 96, 0.5)
		->setBorderColor(39, 174, 96, 0.8)
		->setHoverBackgroundColor(39, 174, 96, 0.75)
		->setHoverBorderColor(39, 174, 96, 1)
	  ->setData($duracion_comparada);

	$grafico->addDataset($dataset_comparar);
	$y_axes[] = [
		'type' => 'linear',
		'display' => true,
		'position' => 'left',
		'id' => 'y-axis-2',
		'gridLines' => [
			'display' => false
		],
		'labels' => [
			'show' => true
		],
		'ticks' => [
			'beginAtZero' => true
		]
	];
}
$options = [
	'responsive' => true,
	'tooltips' => [
		'mode' => 'label'
	],
	'scales' => [
		'xAxes' => [[
			'display' => true,
			'gridLines' => [
				'display' => false
			],
			'labels' => [
				'show' => true,
			]
		]],
		'yAxes' => $y_axes
	]
];

$grafico->setOptions($options);
echo $grafico->getJson();
